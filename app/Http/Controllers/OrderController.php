<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\StoreRequest;
use App\Http\Requests\Order\UpdateStatusRequest;
use App\Models\Customer;
use App\Models\GarmentType;
use App\Models\InventoryLocation;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\InventoryType;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    private const WORKER_PERMISSION_KEY = 'view-assigned-jobs';

    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $outletId = $this->currentOutletId();

        $ordersQuery = Order::query()
            ->with([
                'outlet:id,name',
                'customer:id,name,phone',
                'creator:id,name',
                'worker:id,name',
            ]);

        if ($outletId > 0) {
            $ordersQuery->where('outlet_id', $outletId);
        } else {
            $ordersQuery->whereRaw('1 = 0');
        }

        if ($q !== '') {
            $ordersQuery->where(function ($query) use ($q): void {
                $query->where('order_number', 'like', '%' . $q . '%')
                    ->orWhere('status', 'like', '%' . $q . '%')
                    ->orWhere('payment_status', 'like', '%' . $q . '%')
                    ->orWhereHas('customer', function ($customerQuery) use ($q): void {
                        $customerQuery->where('name', 'like', '%' . $q . '%')
                            ->orWhere('phone', 'like', '%' . $q . '%');
                });
            });
        }

        $reporting = [
            'total' => (clone $ordersQuery)->count(),
            'added_this_week' => (clone $ordersQuery)->where('ordered_at', '>=', now()->startOfWeek())->count(),
            'added_this_month' => (clone $ordersQuery)->whereBetween('ordered_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'added_last_30_days' => (clone $ordersQuery)->where('ordered_at', '>=', now()->subDays(30))->count(),
        ];

        $orders = $ordersQuery
            ->latest('ordered_at')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $workers = $this->assignableWorkers($outletId);

        $statusLabels = Order::statusLabels();
        $nextStatusesByOrderId = $orders->getCollection()
            ->mapWithKeys(function (Order $order) {
                return [$order->id => Order::nextStatusesFor((string) $order->status)];
            });

        return view('modules.order.index', compact(
            'orders',
            'workers',
            'statusLabels',
            'nextStatusesByOrderId',
            'reporting'
        ));
    }

    /**
     * Display orders assigned to the logged in user.
     */
    public function assignedJobs(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $userId = (int) auth()->id();
        $outletId = $this->currentOutletId();

        $ordersQuery = Order::query()
            ->with([
                'outlet:id,name',
                'customer:id,name,phone',
                'creator:id,name',
                'worker:id,name',
            ])
            ->where('worker_id', $userId)
            ->whereIn('status', [
                Order::STATUS_ASSIGNED,
                Order::STATUS_IN_PROGRESS,
                Order::STATUS_NEAR_COMPLETION,
                Order::STATUS_COMPLETED,
                Order::STATUS_DELIVERED,
            ]);

        if ($outletId > 0) {
            $ordersQuery->where('outlet_id', $outletId);
        } else {
            $ordersQuery->whereRaw('1 = 0');
        }

        if ($q !== '') {
            $ordersQuery->where(function ($query) use ($q): void {
                $query->where('order_number', 'like', '%' . $q . '%')
                    ->orWhere('status', 'like', '%' . $q . '%')
                    ->orWhereHas('customer', function ($customerQuery) use ($q): void {
                        $customerQuery->where('name', 'like', '%' . $q . '%')
                            ->orWhere('phone', 'like', '%' . $q . '%');
                });
            });
        }

        $reporting = [
            'total' => (clone $ordersQuery)->count(),
            'added_this_week' => (clone $ordersQuery)->where('ordered_at', '>=', now()->startOfWeek())->count(),
            'added_this_month' => (clone $ordersQuery)->whereBetween('ordered_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'added_last_30_days' => (clone $ordersQuery)->where('ordered_at', '>=', now()->subDays(30))->count(),
        ];

        $orders = $ordersQuery
            ->latest('worker_deadline_at')
            ->latest('ordered_at')
            ->paginate(10)
            ->withQueryString();

        $workers = $this->assignableWorkers($outletId);

        $statusLabels = Order::statusLabels();
        $nextStatusesByOrderId = $orders->getCollection()
            ->mapWithKeys(function (Order $order) {
                return [$order->id => Order::nextStatusesFor((string) $order->status)];
            });

        return view('modules.order.index', compact(
            'orders',
            'workers',
            'statusLabels',
            'nextStatusesByOrderId',
            'reporting'
        ));
    }

    /**
     * Printable customer-facing bill.
     */
    public function customerBill(Order $order)
    {
        $this->ensureOrderBelongsToCurrentOutlet($order);

        $data = $this->prepareBillData($order);

        return view('modules.order.bills.customer', $data);
    }

    /**
     * Printable worker job slip bill.
     */
    public function workerBill(Order $order)
    {
        $this->ensureOrderBelongsToCurrentOutlet($order);

        $user = auth()->user();
        $canManageOrders = (bool) $user?->hasPermission('manage-orders');
        $isOwnAssignedOrder = (int) ($order->worker_id ?? 0) === (int) ($user?->id ?? 0);

        if (!$canManageOrders && !$isOwnAssignedOrder) {
            abort(403);
        }

        $data = $this->prepareBillData($order);

        return view('modules.order.bills.worker', $data);
    }

    /**
     * Printable office/internal bill.
     */
    public function officeBill(Order $order)
    {
        $this->ensureOrderBelongsToCurrentOutlet($order);

        $data = $this->prepareBillData($order);

        return view('modules.order.bills.office', $data);
    }

    /**
     * Show order creation form.
     */
    public function create(Request $request)
    {
        $user = auth()->user();
        $outletId = (int) ($user?->current_outlet_id ?? 0);

        $products = Product::query()
            ->with([
                'category:id,slug',
            ])
            ->whereHas('category', function ($query) {
                $query->whereIn('slug', ['ready-made', 'accessories', 'fabrics']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'product_category_id']);

        $outletLocation = null;
        if ($outletId > 0) {
            $outletLocation = InventoryLocation::query()
                ->where('outlet_id', $outletId)
                ->where('type', InventoryLocation::TYPE_OUTLET)
                ->where('is_active', true)
                ->first(['id']);
        }

        $productDefaultPrices = [];
        $productAvailableQty = [];
        $productIds = $products->pluck('id')->values();

        $buildPriceMaps = function ($rows) use (&$productDefaultPrices): void {
            foreach ($rows as $row) {
                if ($row->special_price === null && $row->base_price === null) {
                    continue;
                }

                $price = $row->special_price !== null
                    ? (float) $row->special_price
                    : (float) $row->base_price;

                $productId = (int) $row->product_id;
                if (!array_key_exists($productId, $productDefaultPrices)) {
                    $productDefaultPrices[$productId] = $price;
                }
            }
        };

        if ($outletLocation) {
            $outletPriceRows = InventoryStock::query()
                ->where('location_id', (int) $outletLocation->id)
                ->whereIn('product_id', $productIds)
                ->orderByDesc('id')
                ->get(['product_id', 'base_price', 'special_price']);

            $buildPriceMaps($outletPriceRows);

            $outletStockRows = InventoryStock::query()
                ->where('location_id', (int) $outletLocation->id)
                ->whereIn('product_id', $productIds)
                ->where('on_hand_qty', '>', 0)
                ->get(['product_id', 'on_hand_qty']);

            foreach ($outletStockRows as $row) {
                $productId = (int) $row->product_id;
                $qty = (float) $row->on_hand_qty;

                if (!array_key_exists($productId, $productAvailableQty)) {
                    $productAvailableQty[$productId] = 0.0;
                }
                $productAvailableQty[$productId] += $qty;
            }
        }

        $allLocationPriceRows = InventoryStock::query()
            ->whereIn('product_id', $productIds)
            ->orderByDesc('id')
            ->get(['product_id', 'base_price', 'special_price']);

        $buildPriceMaps($allLocationPriceRows);

        $customers = Customer::query()
            ->with([
                'customerGarmentTypes.garmentType:id,title',
                'customerGarmentTypes.measurements',
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $garmentTypes = GarmentType::query()
            ->with([
                'measurements.unit:id,name,symbol',
                'tailoringPackages' => function ($query) {
                    $query->where('is_active', true)->orderBy('order')->orderBy('id');
                },
            ])
            ->orderBy('title')
            ->get(['id', 'title', 'amount', 'tax']);

        $selectedCustomerId = $request->query('customer_id');
        $workers = $this->assignableWorkers($outletId);

        return view('modules.order.create', compact(
            'products',
            'customers',
            'garmentTypes',
            'selectedCustomerId',
            'workers',
            'productDefaultPrices',
            'productAvailableQty'
        ));
    }

    public function resolveCustomer(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'customer_type' => ['nullable', 'in:retail,wholesale,custom'],
        ]);

        $phone = trim((string) $validated['phone']);

        $existingCustomer = Customer::query()
            ->where('phone', $phone)
            ->first(['id', 'name', 'phone', 'customer_type']);

        if ($existingCustomer) {
            return response()->json([
                'status' => 'existing',
                'customer' => $existingCustomer,
            ]);
        }

        $name = trim((string) ($validated['name'] ?? ''));
        $email = trim((string) ($validated['email'] ?? ''));
        $address = trim((string) ($validated['address'] ?? ''));
        $customerType = (string) ($validated['customer_type'] ?? 'retail');

        if ($name === '' || $email === '' || $address === '') {
            return response()->json([
                'message' => 'Name, email and address are required to create a new customer.',
            ], 422);
        }

        $emailExists = Customer::query()->where('email', $email)->exists();
        if ($emailExists) {
            return response()->json([
                'message' => 'Email already exists. Use a different email.',
            ], 422);
        }

        $customer = Customer::query()->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'customer_type' => $customerType,
            'address' => $address,
        ]);

        return response()->json([
            'status' => 'created',
            'customer' => $customer->only(['id', 'name', 'phone', 'customer_type']),
        ]);
    }

    /**
     * Store a newly created order.
     */
    public function store(StoreRequest $request)
    {
        $user = auth()->user();
        $outletId = (int) ($user?->current_outlet_id ?? 0);
        $printBill = $request->boolean('print_bill');
        $vatEnabled = $request->boolean('vat_enabled');
        $createdOrder = null;

        if ($outletId < 1) {
            return redirect()
                ->route('order.index')
                ->with('error', 'Set your current outlet before creating an order.');
        }

        $outletLocation = InventoryLocation::query()
            ->where('outlet_id', $outletId)
            ->where('type', InventoryLocation::TYPE_OUTLET)
            ->where('is_active', true)
            ->first();

        if (!$outletLocation) {
            return redirect()
                ->route('order.index')
                ->with('error', 'No active inventory location found for your current outlet.');
        }

        $inventoryTypeId = InventoryType::query()
            ->where('code', InventoryType::OUTLET)
            ->value('id');

        if (!$inventoryTypeId) {
            return redirect()
                ->route('order.index')
                ->with('error', 'Inventory type outlet is missing. Run inventory type seeder.');
        }

        $validated = $request->validated();
        $items = collect($validated['items'])->values();

        $productIds = $items
            ->flatMap(function ($item) {
                $normalProductId = (int) ($item['product_id'] ?? 0);
                $customFabricProductId = (int) data_get($item, 'custom.fabric_product_id', 0);

                return array_filter([$normalProductId, $customFabricProductId]);
            })
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get(['id'])
            ->keyBy('id');

        foreach ($items as $item) {
            $itemCategory = (string) ($item['item_category'] ?? 'readymade');
            $productId = $itemCategory === 'custom'
                ? (int) data_get($item, 'custom.fabric_product_id', 0)
                : (int) ($item['product_id'] ?? 0);

            if ($productId < 1) {
                continue;
            }

            $product = $products->get($productId);
            if (!$product) {
                return back()
                    ->withInput()
                    ->with('error', 'One or more selected products is invalid.');
            }
        }

        $requiredBySku = [];

        foreach ($items as $item) {
            $itemCategory = (string) ($item['item_category'] ?? 'readymade');

            if ($itemCategory === 'custom') {
                $fabricSource = (string) data_get($item, 'custom.fabric_source', 'own');
                if ($fabricSource !== 'stock') {
                    continue;
                }

                $productId = (int) data_get($item, 'custom.fabric_product_id', 0);
                $requiredQty = (float) data_get($item, 'custom.fabric_quantity', 0);

                if ($productId < 1 || $requiredQty <= 0) {
                    continue;
                }
            } else {
                $productId = (int) ($item['product_id'] ?? 0);
                $requiredQty = (float) ($item['quantity'] ?? 0);

                if ($productId < 1 || $requiredQty <= 0) {
                    continue;
                }
            }

            $stockKey = (string) $productId;
            if (!array_key_exists($stockKey, $requiredBySku)) {
                $requiredBySku[$stockKey] = [
                    'product_id' => $productId,
                    'required_qty' => 0.0,
                ];
            }

            $requiredBySku[$stockKey]['required_qty'] += $requiredQty;
        }

        if (!empty($requiredBySku)) {
            $requiredCollection = collect(array_values($requiredBySku));
            $requiredProductIds = $requiredCollection
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
            $availableStockRows = InventoryStock::query()
                ->where('location_id', (int) $outletLocation->id)
                ->whereIn('product_id', $requiredProductIds)
                ->where('on_hand_qty', '>', 0)
                ->get(['product_id', 'on_hand_qty']);

            $availableMap = [];
            foreach ($availableStockRows as $row) {
                $stockKey = (string) ((int) $row->product_id);
                if (!array_key_exists($stockKey, $availableMap)) {
                    $availableMap[$stockKey] = 0.0;
                }
                $availableMap[$stockKey] += (float) $row->on_hand_qty;
            }

            $productNameMap = Product::query()
                ->whereIn('id', $requiredProductIds)
                ->pluck('name', 'id');

            foreach ($requiredCollection as $requiredRow) {
                $stockKey = (string) ((int) $requiredRow['product_id']);
                $availableQty = (float) ($availableMap[$stockKey] ?? 0);
                $requiredQty = (float) ($requiredRow['required_qty'] ?? 0);

                if ($availableQty + 0.000001 >= $requiredQty) {
                    continue;
                }

                $productName = (string) ($productNameMap[(int) $requiredRow['product_id']] ?? 'Product');

                return back()
                    ->withInput()
                    ->with('error', sprintf(
                        'Insufficient stock at current outlet for %s. Required: %.2f, Available: %.2f.',
                        $productName,
                        $requiredQty,
                        $availableQty
                    ));
            }
        }

        try {
            $garmentTypes = GarmentType::query()
                ->whereIn(
                    'id',
                    $items->map(fn ($item) => (int) data_get($item, 'custom.garment_type_id', 0))
                        ->filter(fn ($id) => $id > 0)
                        ->unique()
                        ->values()
                )
                ->get(['id', 'title', 'amount', 'tax'])
                ->keyBy('id');

            $customStockFabricProductIds = $items
                ->filter(fn ($item) => (string) ($item['item_category'] ?? '') === 'custom')
                ->filter(fn ($item) => (string) data_get($item, 'custom.fabric_source', '') === 'stock')
                ->map(fn ($item) => (int) data_get($item, 'custom.fabric_product_id', 0))
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values();

            $productFabricPriceMap = [];
            if ($customStockFabricProductIds->isNotEmpty()) {
                $buildFabricPriceMap = function ($rows) use (&$productFabricPriceMap): void {
                    foreach ($rows as $row) {
                        if ($row->special_price === null && $row->base_price === null) {
                            continue;
                        }

                        $price = $row->special_price !== null
                            ? (float) $row->special_price
                            : (float) $row->base_price;

                        $productId = (int) $row->product_id;
                        if (!array_key_exists($productId, $productFabricPriceMap)) {
                            $productFabricPriceMap[$productId] = $price;
                        }
                    }
                };

                $outletFabricPriceRows = InventoryStock::query()
                    ->where('location_id', (int) $outletLocation->id)
                    ->whereIn('product_id', $customStockFabricProductIds)
                    ->orderByDesc('id')
                    ->get(['product_id', 'base_price', 'special_price']);
                $buildFabricPriceMap($outletFabricPriceRows);

                $allLocationFabricPriceRows = InventoryStock::query()
                    ->whereIn('product_id', $customStockFabricProductIds)
                    ->orderByDesc('id')
                    ->get(['product_id', 'base_price', 'special_price']);
                $buildFabricPriceMap($allLocationFabricPriceRows);
            }

            DB::transaction(function () use ($request, $validated, $items, $products, $outletLocation, $inventoryTypeId, $outletId, $garmentTypes, $productFabricPriceMap, $vatEnabled, &$createdOrder): void {
                $status = (string) $validated['status'];
                $workerId = (int) ($validated['worker_id'] ?? 0);
                $hasAssignedOrLaterStatus = in_array($status, [
                    Order::STATUS_ASSIGNED,
                    Order::STATUS_IN_PROGRESS,
                    Order::STATUS_NEAR_COMPLETION,
                    Order::STATUS_COMPLETED,
                ], true);
                $hasFabricIssuedOrLaterStatus = in_array($status, [
                    Order::STATUS_FABRIC_ISSUED,
                    Order::STATUS_ASSIGNED,
                    Order::STATUS_IN_PROGRESS,
                    Order::STATUS_NEAR_COMPLETION,
                    Order::STATUS_COMPLETED,
                ], true);

                $order = Order::query()->create([
                    'order_number' => $this->generateOrderNumber(),
                    'outlet_id' => $outletId,
                    'customer_id' => (int) $validated['customer_id'],
                    'worker_id' => $workerId > 0 ? $workerId : null,
                    'worker_assigned_at' => $hasAssignedOrLaterStatus
                        ? now()
                        : null,
                    'worker_deadline_at' => $validated['worker_deadline_at'] ?? null,
                    'ordered_at' => $validated['ordered_at'],
                    'delivery_due_at' => $validated['delivery_due_at'],
                    'status' => $status,
                    'fabric_issued_at' => $hasFabricIssuedOrLaterStatus
                        ? now()
                        : null,
                    'completed_at' => $status === Order::STATUS_COMPLETED ? now() : null,
                    'payment_status' => (string) $validated['payment_status'],
                    'payment_method' => $validated['payment_method'] ?? null,
                    'advance_payment_amount' => (float) ($validated['advance_payment_amount'] ?? 0),
                    'discount_amount' => (float) ($validated['discount_amount'] ?? 0),
                    'vat_enabled' => $vatEnabled,
                    'vat_amount' => 0,
                    'subtotal_amount' => 0,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => (int) auth()->id(),
                ]);

                $subtotal = 0.0;

                foreach ($items as $itemIndex => $item) {
                    $itemCategory = (string) ($item['item_category'] ?? 'readymade');
                    $quantity = (float) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];
                    if ($itemCategory === 'custom') {
                        $custom = (array) ($item['custom'] ?? []);
                        $garmentTypeId = (int) ($custom['garment_type_id'] ?? 0);
                        $garmentType = $garmentTypes->get($garmentTypeId);
                        $tailoringPackageId = !empty($custom['tailoring_package_id']) ? (int) $custom['tailoring_package_id'] : null;
                        $tailoringPackageName = trim((string) ($custom['tailoring_package'] ?? ''));
                        $garmentStitchingPrice = (float) ($custom['tailoring_amount'] ?? ($garmentType?->amount ?? 0));
                        $garmentTaxPercent = (float) ($garmentType?->tax ?? 0);
                        $garmentTaxAmount = $garmentTaxPercent > 0
                            ? round($garmentStitchingPrice * ($garmentTaxPercent / 100), 2)
                            : 0.0;
                        $fabricSource = (string) ($custom['fabric_source'] ?? 'own');
                        $fabricProductId = !empty($custom['fabric_product_id']) ? (int) $custom['fabric_product_id'] : null;
                        $fabricQuantity = (float) ($custom['fabric_quantity'] ?? 0);
                        $fabricUnitPrice = 0.0;
                        // Customer fabric: no product/fabric charge, only tailoring charge is tracked separately.
                        if ($fabricSource === 'own') {
                            $unitPrice = 0.0;
                        }
                        if ($fabricSource === 'stock') {
                            if ($fabricProductId && array_key_exists($fabricProductId, $productFabricPriceMap)) {
                                $fabricUnitPrice = (float) $productFabricPriceMap[$fabricProductId];
                            }

                            // Enforce custom stock pricing: stitching + (fabric qty * selected stock unit price).
                            $unitPrice = $garmentStitchingPrice + ($fabricQuantity * $fabricUnitPrice);
                        }

                        $lineTotal = $quantity * $unitPrice;
                        $fabricQuantityUnit = 'm';
                        $designImagePaths = [];
                        $designImages = (array) $request->file("items.{$itemIndex}.custom.design_images", []);
                        foreach ($designImages as $designImage) {
                            if (!$designImage || !$designImage->isValid()) {
                                continue;
                            }

                            $storedPath = Storage::disk('public')->putFile('order-designs', $designImage);
                            if ($storedPath) {
                                $designImagePaths[] = $storedPath;
                            }
                        }

                        $order->items()->create([
                            'item_category' => 'custom',
                            'product_id' => null,
                            'unit_id' => null,
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'line_total' => $lineTotal,
                            'custom_details' => [
                                'garment_type_id' => $garmentTypeId,
                                'garment_title' => $garmentType?->title,
                                'tailoring_package_id' => $tailoringPackageId,
                                'tailoring_package' => $tailoringPackageName !== '' ? $tailoringPackageName : null,
                                'garment_stitching_price' => $garmentStitchingPrice,
                                'garment_tax_percent' => $garmentTaxPercent,
                                'garment_tax_amount' => $garmentTaxAmount,
                                'measurements' => array_values((array) ($custom['measurements'] ?? [])),
                                'fabric_source' => $fabricSource,
                                'fabric_product_id' => $fabricProductId,
                                'fabric_quantity' => $fabricQuantity,
                                'fabric_quantity_unit' => $fabricQuantityUnit,
                                'fabric_unit_price' => $fabricSource === 'stock' ? $fabricUnitPrice : null,
                                'fabric_total_price' => $fabricSource === 'stock' ? ($fabricQuantity * $fabricUnitPrice) : null,
                                'quantity_unit' => 'pcs',
                                'design_note' => $custom['design_note'] ?? null,
                                'design_images' => $designImagePaths,
                                'design_image' => $designImagePaths[0] ?? null,
                            ],
                        ]);

                        if ($fabricSource === 'stock' && $fabricProductId && $fabricQuantity > 0) {
                            $averageCost = $this->deductFromOutletStock(
                                locationId: (int) $outletLocation->id,
                                productId: $fabricProductId,
                                requiredQty: $fabricQuantity
                            );

                            $transaction = InventoryTransaction::query()->create([
                                'inventory_type_id' => $inventoryTypeId,
                                'trx_type' => InventoryTransaction::TYPE_OUT,
                                'reference_type' => 'order',
                                'reference_id' => $order->id,
                                'from_location_id' => $outletLocation->id,
                                'to_location_id' => null,
                                'vendor_id' => null,
                                'trx_date' => $validated['ordered_at'],
                                'notes' => 'Order ' . $order->order_number . ' custom fabric stock deduction',
                                'created_by' => (int) auth()->id(),
                            ]);

                            $transaction->items()->create([
                                'product_id' => $fabricProductId,
                                'qty' => $fabricQuantity,
                                'unit_cost' => $averageCost,
                                'total_cost' => $averageCost !== null ? $averageCost * $fabricQuantity : null,
                            ]);
                        }
                    } else {
                        $productId = (int) $item['product_id'];
                        $product = $products->get($productId);
                        $lineTotal = $quantity * $unitPrice;

                        $order->items()->create([
                            'item_category' => $itemCategory,
                            'product_id' => $productId,
                            'unit_id' => null,
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'line_total' => $lineTotal,
                        ]);

                        $averageCost = $this->deductFromOutletStock(
                            locationId: (int) $outletLocation->id,
                            productId: $productId,
                            requiredQty: $quantity
                        );

                        $transaction = InventoryTransaction::query()->create([
                            'inventory_type_id' => $inventoryTypeId,
                            'trx_type' => InventoryTransaction::TYPE_OUT,
                            'reference_type' => 'order',
                            'reference_id' => $order->id,
                            'from_location_id' => $outletLocation->id,
                            'to_location_id' => null,
                            'vendor_id' => null,
                            'trx_date' => $validated['ordered_at'],
                            'notes' => 'Order ' . $order->order_number . ' stock deduction',
                            'created_by' => (int) auth()->id(),
                        ]);

                        $transaction->items()->create([
                            'product_id' => $productId,
                            'qty' => $quantity,
                            'unit_cost' => $averageCost,
                            'total_cost' => $averageCost !== null ? $averageCost * $quantity : null,
                        ]);
                    }

                    $subtotal += $lineTotal;
                }

                $discountAmount = (float) ($validated['discount_amount'] ?? 0);
                $taxableSubtotal = max(0.0, $subtotal - $discountAmount);
                $vatAmount = $vatEnabled
                    ? round($taxableSubtotal * 0.13, 2)
                    : 0.0;

                $order->subtotal_amount = $subtotal;
                $order->vat_amount = $vatAmount;
                $order->save();
                $createdOrder = $order;
            });
        } catch (\RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        if ($printBill && $createdOrder) {
            return redirect()
                ->route('order.bill.customer', ['order' => $createdOrder, 'autoprint' => 1]);
        }

        return redirect()
            ->route('order.index')
            ->with('success', 'Order created successfully.');
    }

    /**
     * Update order status to delivered or cancelled.
     */
    public function updateStatus(UpdateStatusRequest $request, Order $order)
    {
        $this->ensureOrderBelongsToCurrentOutlet($order);

        $user = auth()->user();
        $canManageOrders = (bool) $user?->hasPermission('manage-orders');
        $canViewAssignedJobs = (bool) $user?->hasPermission(self::WORKER_PERMISSION_KEY);
        $isOwnAssignedOrder = (int) ($order->worker_id ?? 0) === (int) ($user?->id ?? 0);
        $redirectRoute = $canManageOrders ? 'order.index' : 'order.assignedJobs';

        if (!$canManageOrders) {
            if (!$canViewAssignedJobs || !$isOwnAssignedOrder) {
                return redirect()
                    ->route($redirectRoute)
                    ->with('error', 'You can only update statuses for jobs assigned to you.');
            }
        }

        $nextStatuses = Order::nextStatusesFor((string) $order->status);
        if (empty($nextStatuses)) {
            return redirect()
                ->route($redirectRoute)
                ->with('error', 'Finalized orders are locked and cannot be updated.');
        }

        $validated = $request->validated();
        $targetStatus = (string) ($validated['status'] ?? '');

        if (!$canManageOrders) {
            $allowedWorkerStatuses = [
                Order::STATUS_IN_PROGRESS,
                Order::STATUS_NEAR_COMPLETION,
                Order::STATUS_COMPLETED,
            ];

            if (!in_array($targetStatus, $allowedWorkerStatuses, true)) {
                return redirect()
                    ->route($redirectRoute)
                    ->with('error', 'You can only move your assigned jobs through progress statuses.');
            }
        }

        try {
            app(OrderWorkflowService::class)->transition($order, $validated);
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route($redirectRoute)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Order status updated successfully.');
    }

    private function generateOrderNumber(): string
    {
        $prefix = 'ORD-' . now()->format('Ymd');
        $count = Order::query()
            ->whereDate('created_at', now()->toDateString())
            ->lockForUpdate()
            ->get(['id'])
            ->count();

        return $prefix . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareBillData(Order $order): array
    {
        $order->loadMissing([
            'outlet:id,name',
            'customer:id,name,email,phone,address',
            'worker:id,name,email',
            'items.product:id,name,code',
            'items.unit:id,name,symbol',
        ]);

        $items = $order->items;
        $customItems = $items->filter(fn ($item) => (string) $item->item_category === 'custom')->values();
        $fabricItems = $items->filter(fn ($item) => in_array((string) $item->item_category, ['custom', 'fabric'], true))->values();

        $stitchingCharges = (float) $customItems->sum(function ($item) {
            return (float) data_get($item->custom_details, 'garment_stitching_price', 0) * (float) $item->quantity;
        });

        $taxAmount = (bool) $order->vat_enabled
            ? (float) ($order->vat_amount ?? 0)
            : 0.0;

        $subtotal = (float) $order->subtotal_amount;
        $discount = (float) ($order->discount_amount ?? 0);
        $netPayable = max(0.0, ($subtotal - $discount) + $taxAmount);
        $paidAmount = (float) ($order->advance_payment_amount ?? 0);
        if ((string) $order->payment_status === Order::PAYMENT_STATUS_PAID) {
            $paidAmount = $netPayable;
        }
        $dueAmount = max(0.0, $netPayable - $paidAmount);

        $inventoryTransactions = InventoryTransaction::query()
            ->where('reference_type', 'order')
            ->where('reference_id', (int) $order->id)
            ->where('trx_type', InventoryTransaction::TYPE_OUT)
            ->with('items:id,inventory_transaction_id,total_cost')
            ->get(['id']);

        $vendorCost = (float) $inventoryTransactions->sum(function ($transaction) {
            return (float) $transaction->items->sum(fn ($row) => (float) ($row->total_cost ?? 0));
        });

        $workerPayment = $stitchingCharges;
        $profitMargin = $netPayable - ($vendorCost + $workerPayment);

        return [
            'order' => $order,
            'items' => $items,
            'customItems' => $customItems,
            'fabricItems' => $fabricItems,
            'stitchingCharges' => $stitchingCharges,
            'taxAmount' => $taxAmount,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'netPayable' => $netPayable,
            'paidAmount' => $paidAmount,
            'dueAmount' => $dueAmount,
            'vendorCost' => $vendorCost,
            'workerPayment' => $workerPayment,
            'profitMargin' => $profitMargin,
        ];
    }

    private function deductFromOutletStock(int $locationId, int $productId, float $requiredQty): ?float
    {
        $remainingQty = $requiredQty;

        $stocks = InventoryStock::query()
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->where('on_hand_qty', '>', 0)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $availableQty = (float) $stocks->sum('on_hand_qty');

        if ($availableQty < $requiredQty) {
            throw new \RuntimeException('Insufficient stock for one or more order items at current outlet.');
        }

        $totalCost = 0.0;
        $consumedQty = 0.0;

        /** @var Collection<int, InventoryStock> $stocks */
        foreach ($stocks as $stock) {
            if ($remainingQty <= 0) {
                break;
            }

            $stockQty = (float) $stock->on_hand_qty;
            if ($stockQty <= 0) {
                continue;
            }

            $deductQty = min($remainingQty, $stockQty);
            $stock->on_hand_qty = $stockQty - $deductQty;
            $stock->save();

            $cost = (float) $stock->avg_cost;
            $totalCost += $deductQty * $cost;
            $consumedQty += $deductQty;
            $remainingQty -= $deductQty;
        }

        if ($consumedQty <= 0) {
            return null;
        }

        return $totalCost / $consumedQty;
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function assignableWorkers(?int $outletId = null): \Illuminate\Support\Collection
    {
        $scopeOutletId = $outletId && $outletId > 0
            ? $outletId
            : null;

        return User::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->filter(fn (User $user) => $user->hasPermission(self::WORKER_PERMISSION_KEY, $scopeOutletId))
            ->values();
    }

    private function currentOutletId(): int
    {
        return (int) (auth()->user()?->current_outlet_id ?? 0);
    }

    private function ensureOrderBelongsToCurrentOutlet(Order $order): void
    {
        $currentOutletId = $this->currentOutletId();

        if ($currentOutletId < 1 || (int) $order->outlet_id !== $currentOutletId) {
            abort(404);
        }
    }
}
