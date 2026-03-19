<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\StoreRequest;
use App\Http\Requests\Order\UpdateDeliveryDateRequest;
use App\Http\Requests\Order\UpdatePaymentRequest;
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
        return view('modules.order.create', $this->buildOrderFormData($request));
    }

    /**
     * Show order edit form.
     */
    public function edit(Request $request, Order $order)
    {
        $this->ensureOrderBelongsToCurrentOutlet($order);
        $this->ensureOrderIsEditable($order);

        return view('modules.order.create', $this->buildOrderFormData($request, $order));
    }

    private function buildOrderFormData(Request $request, ?Order $editingOrder = null): array
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
            ->get(['id', 'name', 'code', 'product_category_id', 'amount']);

        $outletLocation = null;
        if ($outletId > 0) {
            $outletLocation = InventoryLocation::query()
                ->where('outlet_id', $outletId)
                ->where('type', InventoryLocation::TYPE_OUTLET)
                ->where('is_active', true)
                ->first(['id']);
        }

        $productAvailableQty = [];
        $productIds = $products->pluck('id')->values();

        if ($outletLocation) {
            $outletStockRows = InventoryStock::query()
                ->where('location_id', (int) $outletLocation->id)
                ->whereIn('product_id', $productIds)
                ->where('on_hand_qty', '>', 0)
                ->get(['product_id', 'on_hand_qty', 'reserved_qty']);

            foreach ($outletStockRows as $row) {
                $productId = (int) $row->product_id;
                $qty = max(0, (float) $row->on_hand_qty - (float) $row->reserved_qty);

                if (!array_key_exists($productId, $productAvailableQty)) {
                    $productAvailableQty[$productId] = 0.0;
                }
                $productAvailableQty[$productId] += $qty;
            }
        }

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

        $initialOrderState = $this->buildInitialOrderState($request, $products, $garmentTypes, $editingOrder);

        return compact(
            'products',
            'customers',
            'garmentTypes',
            'selectedCustomerId',
            'workers',
            'productAvailableQty',
            'editingOrder',
            'initialOrderState'
        );
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
        return $this->persistOrder($request);
    }

    /**
     * Update an existing order.
     */
    public function update(StoreRequest $request, Order $order)
    {
        $this->ensureOrderBelongsToCurrentOutlet($order);
        $this->ensureOrderIsEditable($order);

        return $this->persistOrder($request, $order);
    }

    public function updateDeliveryDate(UpdateDeliveryDateRequest $request, Order $order)
    {
        $this->ensureOrderBelongsToCurrentOutlet($order);
        $this->ensureOrderDeliveryDateIsEditable($order);

        $validated = $request->validated();
        $deliveryDueAt = $validated['delivery_due_at'];

        $order->delivery_due_at = $deliveryDueAt;

        if ($order->worker_deadline_at && $order->worker_deadline_at->gt($order->delivery_due_at)) {
            $order->worker_deadline_at = $order->delivery_due_at;
        }

        $order->save();

        return redirect()
            ->route('order.index')
            ->with('success', 'Delivery date updated successfully.');
    }

    private function persistOrder(StoreRequest $request, ?Order $existingOrder = null)
    {
        $user = auth()->user();
        $outletId = (int) ($user?->current_outlet_id ?? 0);
        $printBill = $request->boolean('print_bill');
        $vatEnabled = $request->boolean('vat_enabled');
        $savedOrder = null;

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
        $effectiveProductPrices = Product::query()
            ->whereIn(
                'id',
                $items
                    ->filter(fn ($item) => (string) ($item['item_category'] ?? '') !== 'custom')
                    ->map(fn ($item) => (int) ($item['product_id'] ?? 0))
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values()
            )
            ->pluck('amount', 'id')
            ->map(fn ($amount) => (float) $amount)
            ->all();

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

        $existingOrderStock = $existingOrder
            ? $this->getOrderCommittedStockMap($existingOrder)
            : [];

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
                ->get(['product_id', 'on_hand_qty', 'reserved_qty']);

            $availableMap = [];
            foreach ($availableStockRows as $row) {
                $stockKey = (string) ((int) $row->product_id);
                if (!array_key_exists($stockKey, $availableMap)) {
                    $availableMap[$stockKey] = 0.0;
                }
                $availableMap[$stockKey] += max(0, (float) $row->on_hand_qty - (float) $row->reserved_qty);
            }

            $productNameMap = Product::query()
                ->whereIn('id', $requiredProductIds)
                ->pluck('name', 'id');

            foreach ($requiredCollection as $requiredRow) {
                $stockKey = (string) ((int) $requiredRow['product_id']);
                $availableQty = (float) ($availableMap[$stockKey] ?? 0) + (float) ($existingOrderStock[$stockKey] ?? 0);
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
                    $items->flatMap(function ($item) {
                        return collect((array) data_get($item, 'custom.garments', []))
                            ->map(fn ($garment) => (int) ($garment['garment_type_id'] ?? 0));
                    })
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

            $productFabricPriceMap = Product::query()
                ->whereIn('id', $customStockFabricProductIds)
                ->pluck('amount', 'id')
                ->map(fn ($amount) => (float) $amount)
                ->all();

            DB::transaction(function () use ($request, $validated, $items, $products, $outletLocation, $inventoryTypeId, $outletId, $garmentTypes, $productFabricPriceMap, $vatEnabled, $existingOrder, &$savedOrder): void {
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

                if ($existingOrder) {
                    if ($this->orderHasIssuedStock($existingOrder)) {
                        $this->restoreOrderInventory($existingOrder);
                    } else {
                        $this->releaseReservedStockForOrder($existingOrder, (int) $outletLocation->id);
                    }
                    $existingOrder->items()->delete();
                }

                $order = $existingOrder ?: new Order();

                $order->fill([
                    'order_number' => $order->exists ? $order->order_number : $this->generateOrderNumber(),
                    'outlet_id' => $outletId,
                    'customer_id' => (int) $validated['customer_id'],
                    'worker_id' => $workerId > 0 ? $workerId : null,
                    'worker_assigned_at' => $hasAssignedOrLaterStatus
                        ? ($order->worker_assigned_at ?? now())
                        : null,
                    'worker_deadline_at' => $validated['worker_deadline_at'] ?? null,
                    'ordered_at' => $validated['ordered_at'],
                    'delivery_due_at' => $validated['delivery_due_at'],
                    'status' => $status,
                    'fabric_issued_at' => $hasFabricIssuedOrLaterStatus
                        ? ($order->fabric_issued_at ?? now())
                        : null,
                    'completed_at' => $status === Order::STATUS_COMPLETED ? ($order->completed_at ?? now()) : null,
                    'payment_status' => (string) $validated['payment_status'],
                    'payment_method' => $validated['payment_method'] ?? null,
                    'advance_payment_amount' => (float) ($validated['advance_payment_amount'] ?? 0),
                    'discount_amount' => (float) ($validated['discount_amount'] ?? 0),
                    'vat_enabled' => $vatEnabled,
                    'vat_amount' => 0,
                    'subtotal_amount' => 0,
                    'tailoring_amount' => 0,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $order->exists ? (int) ($order->created_by ?? auth()->id()) : (int) auth()->id(),
                ]);
                $order->save();

                $subtotal = 0.0;
                $tailoringTotal = 0.0;

                foreach ($items as $itemIndex => $item) {
                    $itemCategory = (string) ($item['item_category'] ?? 'readymade');
                    $quantity = (float) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];
                    $itemTailoringTotal = 0.0;
                    if ($itemCategory === 'custom') {
                        $custom = (array) ($item['custom'] ?? []);
                        $fabricSource = (string) ($custom['fabric_source'] ?? 'own');
                        $fabricProductId = !empty($custom['fabric_product_id']) ? (int) $custom['fabric_product_id'] : null;
                        $fabricQuantity = (float) ($custom['fabric_quantity'] ?? $quantity);
                        $fabricUnitPrice = 0.0;

                        if ($fabricSource === 'own') {
                            $unitPrice = 0.0;
                        }

                        if ($fabricSource === 'stock') {
                            if ($fabricProductId && array_key_exists($fabricProductId, $productFabricPriceMap)) {
                                $fabricUnitPrice = (float) $productFabricPriceMap[$fabricProductId];
                            }

                            $unitPrice = $fabricUnitPrice;
                        }

                        $lineTotal = $fabricQuantity * $unitPrice;
                        $fabricQuantityUnit = 'm';
                        $garments = collect((array) ($custom['garments'] ?? []))
                            ->values()
                            ->map(function ($garment) use ($garmentTypes) {
                                $garmentTypeId = (int) ($garment['garment_type_id'] ?? 0);
                                $garmentType = $garmentTypes->get($garmentTypeId);
                                $garmentQty = (float) ($garment['quantity'] ?? 1);
                                $tailoringAmount = (float) ($garment['tailoring_amount'] ?? ($garmentType?->amount ?? 0));
                                $taxPercent = (float) ($garmentType?->tax ?? 0);
                                $tailoringTotal = $garmentQty * $tailoringAmount;

                                return [
                                    'garment_type_id' => $garmentTypeId,
                                    'garment_title' => $garment['garment_title'] ?? $garmentType?->title,
                                    'quantity' => $garmentQty,
                                    'measurements' => array_values((array) ($garment['measurements'] ?? [])),
                                    'tailoring_package_id' => !empty($garment['tailoring_package_id'])
                                        ? (int) $garment['tailoring_package_id']
                                        : null,
                                    'tailoring_package' => filled($garment['tailoring_package'] ?? null)
                                        ? (string) $garment['tailoring_package']
                                        : null,
                                    'tailoring_amount' => $tailoringAmount,
                                    'tailoring_total_amount' => $tailoringTotal,
                                    'garment_tax_percent' => $taxPercent,
                                    'garment_tax_amount' => $taxPercent > 0
                                        ? round($tailoringTotal * ($taxPercent / 100), 2)
                                        : 0.0,
                                ];
                            });
                        $itemTailoringTotal = (float) $garments->sum(fn ($garment) => (float) ($garment['tailoring_total_amount'] ?? 0));
                        $designImagePaths = collect((array) ($custom['existing_design_images'] ?? []))
                            ->map(fn ($path) => trim((string) $path))
                            ->filter()
                            ->values()
                            ->all();
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
                            'quantity' => $fabricQuantity,
                            'unit_price' => $unitPrice,
                            'line_total' => $lineTotal,
                            'custom_details' => [
                                'garment_title' => $garments->pluck('garment_title')->filter()->implode(', '),
                                'garments' => $garments->all(),
                                'measurements' => $garments->flatMap(fn ($garment) => (array) ($garment['measurements'] ?? []))->values()->all(),
                                'fabric_source' => $fabricSource,
                                'fabric_product_id' => $fabricProductId,
                                'fabric_quantity' => $fabricQuantity,
                                'fabric_quantity_unit' => $fabricQuantityUnit,
                                'fabric_unit_price' => $fabricSource === 'stock' ? $fabricUnitPrice : null,
                                'fabric_total_price' => $fabricSource === 'stock' ? ($fabricQuantity * $fabricUnitPrice) : null,
                                'quantity_unit' => $fabricQuantityUnit,
                                'tailoring_total_price' => $itemTailoringTotal,
                                'design_note' => $custom['design_note'] ?? null,
                                'design_images' => $designImagePaths,
                                'design_image' => $designImagePaths[0] ?? null,
                            ],
                        ]);

                    } else {
                        $productId = (int) $item['product_id'];
                        $product = $products->get($productId);
                        $submittedUnitPrice = (float) ($item['unit_price'] ?? 0.0);
                        $resolvedUnitPrice = (float) ($effectiveProductPrices[$productId] ?? 0.0);
                        $unitPrice = $resolvedUnitPrice > 0 ? $resolvedUnitPrice : $submittedUnitPrice;
                        $lineTotal = $quantity * $unitPrice;

                        $order->items()->create([
                            'item_category' => $itemCategory,
                            'product_id' => $productId,
                            'unit_id' => null,
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'line_total' => $lineTotal,
                            'custom_details' => $itemCategory === 'readymade'
                                ? ['size' => $item['size'] ?? null]
                                : null,
                        ]);

                    }

                    $subtotal += $lineTotal;
                    if ($itemCategory === 'custom') {
                        $tailoringTotal += $itemTailoringTotal;
                    }
                }

                $discountAmount = (float) ($validated['discount_amount'] ?? 0);
                $taxableSubtotal = max(0.0, $subtotal - $discountAmount);
                $vatAmount = $vatEnabled
                    ? round($taxableSubtotal * 0.13, 2)
                    : 0.0;

                $order->subtotal_amount = $subtotal;
                $order->tailoring_amount = $tailoringTotal;
                $order->vat_amount = $vatAmount;
                $order->save();

                if ($this->orderHasIssuedStock($order)) {
                    $this->issueStockForOrder(
                        $order,
                        (int) $outletLocation->id,
                        (int) $inventoryTypeId,
                        $validated['ordered_at'],
                        (int) auth()->id()
                    );
                } else {
                    $this->reserveStockForOrder($order, (int) $outletLocation->id);
                }

                $savedOrder = $order;
            });
        } catch (\RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        if ($printBill && $savedOrder) {
            return redirect()
                ->route('order.bill.customer', ['order' => $savedOrder, 'autoprint' => 1]);
        }

        return redirect()
            ->route('order.index')
            ->with('success', $existingOrder ? 'Order updated successfully.' : 'Order created successfully.');
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
            DB::transaction(function () use ($order, $validated): void {
                $targetStatus = (string) ($validated['status'] ?? '');
                $outletLocationId = $this->resolveOutletLocationId((int) $order->outlet_id);

                if ($outletLocationId < 1) {
                    throw new \RuntimeException('No active inventory location found for this order outlet.');
                }

                if ($targetStatus === Order::STATUS_FABRIC_ISSUED) {
                    $inventoryTypeId = $this->resolveOutletInventoryTypeId();

                    if ($inventoryTypeId < 1) {
                        throw new \RuntimeException('Inventory type outlet is missing. Run inventory type seeder.');
                    }

                    $this->issueStockForOrder(
                        $order,
                        $outletLocationId,
                        $inventoryTypeId,
                        now(),
                        (int) auth()->id()
                    );
                }

                if ($targetStatus === Order::STATUS_CANCELLED) {
                    if ($this->orderHasIssuedStock($order)) {
                        $this->restoreOrderInventory($order);
                    } else {
                        $this->releaseReservedStockForOrder($order, $outletLocationId);
                    }
                }

                app(OrderWorkflowService::class)->transition($order, $validated);
            });
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route($redirectRoute)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Order status updated successfully.');
    }

    public function updatePayment(UpdatePaymentRequest $request, Order $order)
    {
        $this->ensureOrderBelongsToCurrentOutlet($order);

        if ((string) $order->status === Order::STATUS_CANCELLED) {
            return redirect()
                ->route('order.index')
                ->with('error', 'Cancelled orders cannot receive payments.');
        }

        $validated = $request->validated();
        $paymentAmount = (float) $validated['payment_amount'];
        $currentPaid = (float) ($order->advance_payment_amount ?? 0);
        $payableAmount = $order->payableAmount();
        $dueAmount = max(0.0, $payableAmount - $currentPaid);

        if ($paymentAmount - 0.0001 > $dueAmount) {
            return redirect()
                ->route('order.index')
                ->with('error', 'Payment amount cannot be greater than due amount.');
        }

        $updatedPaid = $currentPaid + $paymentAmount;
        $paymentStatus = Order::PAYMENT_STATUS_UNPAID;

        if ($updatedPaid > 0.0001 && $updatedPaid + 0.0001 < $payableAmount) {
            $paymentStatus = Order::PAYMENT_STATUS_PARTIAL;
        } elseif ($updatedPaid + 0.0001 >= $payableAmount) {
            $paymentStatus = Order::PAYMENT_STATUS_PAID;
            $updatedPaid = $payableAmount;
        }

        $order->advance_payment_amount = $updatedPaid;
        $order->payment_method = (string) $validated['payment_method'];
        $order->payment_status = $paymentStatus;
        $order->save();

        return redirect()
            ->route('order.index')
            ->with('success', 'Payment recorded successfully.');
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

        $stitchingCharges = (float) ($order->tailoring_amount ?? 0);
        if ($stitchingCharges <= 0) {
            $stitchingCharges = (float) $customItems->sum(function ($item) {
                $garments = collect((array) data_get($item->custom_details, 'garments', []));
                if ($garments->isNotEmpty()) {
                    return (float) $garments->sum(fn ($garment) => (float) ($garment['tailoring_total_amount'] ?? 0));
                }

                return (float) data_get($item->custom_details, 'garment_stitching_price', 0) * (float) $item->quantity;
            });
        }

        $taxAmount = (bool) $order->vat_enabled
            ? (float) ($order->vat_amount ?? 0)
            : 0.0;

        $subtotal = (float) $order->subtotal_amount;
        $discount = (float) ($order->discount_amount ?? 0);
        $netPayable = max(0.0, ($subtotal - $discount) + $taxAmount + $stitchingCharges);
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
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $availableQty = (float) $stocks->sum(function (InventoryStock $stock) {
            return max(0, (float) $stock->on_hand_qty - (float) $stock->reserved_qty);
        });

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

            $availableStockQty = max(0, (float) $stock->on_hand_qty - (float) $stock->reserved_qty);
            if ($availableStockQty <= 0) {
                continue;
            }

            $deductQty = min($remainingQty, $availableStockQty);
            $stock->on_hand_qty = (float) $stock->on_hand_qty - $deductQty;
            $stock->save();

            $cost = (float) $stock->unit_cost;
            $totalCost += $deductQty * $cost;
            $consumedQty += $deductQty;
            $remainingQty -= $deductQty;
        }

        if ($consumedQty <= 0) {
            return null;
        }

        return $totalCost / $consumedQty;
    }

    private function reserveStockForOrder(Order $order, int $locationId): void
    {
        foreach ($this->getOrderCommittedStockMap($order) as $productId => $qty) {
            $this->reserveOutletStock($locationId, (int) $productId, (float) $qty);
        }
    }

    private function releaseReservedStockForOrder(Order $order, int $locationId): void
    {
        foreach ($this->getOrderCommittedStockMap($order) as $productId => $qty) {
            $this->releaseReservedOutletStock($locationId, (int) $productId, (float) $qty);
        }
    }

    private function issueStockForOrder(
        Order $order,
        int $locationId,
        int $inventoryTypeId,
        mixed $trxDate,
        int $createdBy
    ): void {
        $requirements = $this->getOrderCommittedStockMap($order);

        foreach ($requirements as $productId => $qty) {
            $this->releaseReservedOutletStock($locationId, (int) $productId, (float) $qty);

            $averageCost = $this->deductFromOutletStock(
                locationId: $locationId,
                productId: (int) $productId,
                requiredQty: (float) $qty
            );

            $transaction = InventoryTransaction::query()->create([
                'inventory_type_id' => $inventoryTypeId,
                'trx_type' => InventoryTransaction::TYPE_OUT,
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'from_location_id' => $locationId,
                'to_location_id' => null,
                'vendor_id' => null,
                'trx_date' => $trxDate,
                'notes' => 'Order ' . $order->order_number . ' stock deduction',
                'created_by' => $createdBy,
            ]);

            $transaction->items()->create([
                'product_id' => (int) $productId,
                'qty' => (float) $qty,
                'unit_cost' => $averageCost,
                'total_cost' => $averageCost !== null ? $averageCost * (float) $qty : null,
            ]);
        }
    }

    private function reserveOutletStock(int $locationId, int $productId, float $requiredQty): void
    {
        $remainingQty = $requiredQty;

        $stocks = InventoryStock::query()
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $availableQty = (float) $stocks->sum(function (InventoryStock $stock) {
            return max(0, (float) $stock->on_hand_qty - (float) $stock->reserved_qty);
        });

        if ($availableQty + 0.000001 < $requiredQty) {
            throw new \RuntimeException('Insufficient stock for one or more order items at current outlet.');
        }

        foreach ($stocks as $stock) {
            if ($remainingQty <= 0) {
                break;
            }

            $availableStockQty = max(0, (float) $stock->on_hand_qty - (float) $stock->reserved_qty);
            if ($availableStockQty <= 0) {
                continue;
            }

            $reserveQty = min($remainingQty, $availableStockQty);
            $stock->reserved_qty = (float) $stock->reserved_qty + $reserveQty;
            $stock->save();
            $remainingQty -= $reserveQty;
        }
    }

    private function releaseReservedOutletStock(int $locationId, int $productId, float $qtyToRelease): void
    {
        if ($qtyToRelease <= 0) {
            return;
        }

        $remainingQty = $qtyToRelease;

        $stocks = InventoryStock::query()
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->where('reserved_qty', '>', 0)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->get();

        foreach ($stocks as $stock) {
            if ($remainingQty <= 0) {
                break;
            }

            $reservedQty = (float) $stock->reserved_qty;
            if ($reservedQty <= 0) {
                continue;
            }

            $releaseQty = min($remainingQty, $reservedQty);
            $stock->reserved_qty = $reservedQty - $releaseQty;
            $stock->save();
            $remainingQty -= $releaseQty;
        }
    }

    private function restoreOrderInventory(Order $order): void
    {
        $transactions = InventoryTransaction::query()
            ->where('reference_type', 'order')
            ->where('reference_id', (int) $order->id)
            ->where('trx_type', InventoryTransaction::TYPE_OUT)
            ->with('items')
            ->lockForUpdate()
            ->get();

        foreach ($transactions as $transaction) {
            $locationId = (int) ($transaction->from_location_id ?? 0);
            if ($locationId < 1) {
                $transaction->items()->delete();
                $transaction->delete();
                continue;
            }

            foreach ($transaction->items as $transactionItem) {
                $productId = (int) ($transactionItem->product_id ?? 0);
                $qty = (float) ($transactionItem->qty ?? 0);
                $unitCost = (float) ($transactionItem->unit_cost ?? 0);

                if ($productId < 1 || $qty <= 0) {
                    continue;
                }

                $stock = InventoryStock::query()
                    ->where('location_id', $locationId)
                    ->where('product_id', $productId)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if (!$stock) {
                    $stock = InventoryStock::query()->create([
                        'location_id' => $locationId,
                        'product_id' => $productId,
                        'vendor_id' => null,
                        'unit_id' => null,
                        'on_hand_qty' => 0,
                        'reserved_qty' => 0,
                        'unit_cost' => $unitCost,
                    ]);
                }

                $currentQty = (float) $stock->on_hand_qty;
                $currentValue = $currentQty * (float) $stock->unit_cost;
                $incomingValue = $qty * $unitCost;
                $newQty = $currentQty + $qty;

                $stock->unit_cost = $newQty > 0 ? (($currentValue + $incomingValue) / $newQty) : 0;
                $stock->on_hand_qty = $newQty;
                $stock->save();
            }

            $transaction->items()->delete();
            $transaction->delete();
        }
    }

    /**
     * @return array<string, float>
     */
    private function getOrderCommittedStockMap(Order $order): array
    {
        $order->loadMissing('items');

        $requirements = [];

        foreach ($order->items as $item) {
            $itemCategory = (string) ($item->item_category ?? '');

            if ($itemCategory === 'custom') {
                if ((string) data_get($item->custom_details, 'fabric_source') !== 'stock') {
                    continue;
                }

                $productId = (int) data_get($item->custom_details, 'fabric_product_id', 0);
                $qty = (float) data_get($item->custom_details, 'fabric_quantity', $item->quantity);
            } else {
                $productId = (int) ($item->product_id ?? 0);
                $qty = (float) ($item->quantity ?? 0);
            }

            if ($productId < 1 || $qty <= 0) {
                continue;
            }

            $stockKey = (string) $productId;
            if (!array_key_exists($stockKey, $requirements)) {
                $requirements[$stockKey] = 0.0;
            }

            $requirements[$stockKey] += $qty;
        }

        return $requirements;
    }

    private function orderHasIssuedStock(Order $order): bool
    {
        return in_array((string) $order->status, [
            Order::STATUS_FABRIC_ISSUED,
            Order::STATUS_ASSIGNED,
            Order::STATUS_IN_PROGRESS,
            Order::STATUS_NEAR_COMPLETION,
            Order::STATUS_COMPLETED,
            Order::STATUS_DELIVERED,
        ], true);
    }

    private function resolveOutletLocationId(int $outletId): int
    {
        if ($outletId < 1) {
            return 0;
        }

        return (int) (InventoryLocation::query()
            ->where('outlet_id', $outletId)
            ->where('type', InventoryLocation::TYPE_OUTLET)
            ->where('is_active', true)
            ->value('id') ?? 0);
    }

    private function resolveOutletInventoryTypeId(): int
    {
        return (int) (InventoryType::query()
            ->where('code', InventoryType::OUTLET)
            ->value('id') ?? 0);
    }

    private function ensureOrderIsEditable(Order $order): void
    {
        if (in_array((string) $order->status, [
            Order::STATUS_ASSIGNED,
            Order::STATUS_IN_PROGRESS,
            Order::STATUS_NEAR_COMPLETION,
            Order::STATUS_COMPLETED,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED,
        ], true)) {
            abort(403, 'Assigned, completed, delivered, or cancelled orders cannot be edited.');
        }
    }

    private function ensureOrderDeliveryDateIsEditable(Order $order): void
    {
        if (in_array((string) $order->status, [
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED,
        ], true)) {
            abort(403, 'Delivered or cancelled orders cannot be updated.');
        }
    }

    private function buildInitialOrderState(Request $request, Collection $products, Collection $garmentTypes, ?Order $editingOrder = null): array
    {
        $productLookup = $products->keyBy('id');
        $garmentLookup = $garmentTypes->keyBy('id');

        $inputItems = $request->old('items');
        if (is_array($inputItems)) {
            return [
                'items' => collect($inputItems)
                    ->values()
                    ->map(fn ($item, $index) => $this->mapBillItemFromInput((array) $item, $index, $productLookup, $garmentLookup))
                    ->filter()
                    ->values()
                    ->all(),
                'discount' => [
                    'type' => (float) $request->old('discount_amount', 0) > 0 ? 'flat' : 'none',
                    'value' => (float) $request->old('discount_amount', 0),
                ],
                'vatEnabled' => $request->old('vat_enabled', '0') === '1',
            ];
        }

        if (!$editingOrder) {
            return [
                'items' => [],
                'discount' => ['type' => 'none', 'value' => 0],
                'vatEnabled' => false,
            ];
        }

        $editingOrder->loadMissing(['items.product:id,name,code']);
        $customProductIds = $editingOrder->items
            ->pluck('custom_details.fabric_product_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $customProducts = Product::query()
            ->whereIn('id', $customProductIds)
            ->get(['id', 'name', 'code'])
            ->keyBy('id');

        return [
            'items' => $editingOrder->items
                ->values()
                ->map(function ($item) use ($customProducts) {
                    if ((string) $item->item_category !== 'custom') {
                        return [
                            'id' => 'existing-' . $item->id,
                            'category' => (string) $item->item_category,
                            'productId' => (int) $item->product_id,
                            'name' => $item->product
                                ? trim($item->product->name . ' (' . $item->product->code . ')')
                                : 'Product',
                            'unitLabel' => (string) $item->item_category === 'fabric' ? 'm' : 'pcs',
                            'qty' => (float) $item->quantity,
                            'unitPrice' => (float) $item->unit_price,
                            'size' => data_get($item->custom_details, 'size'),
                        ];
                    }

                    $fabricProductId = (int) data_get($item->custom_details, 'fabric_product_id', 0);
                    $fabricProduct = $customProducts->get($fabricProductId);
                    $fabricSource = (string) data_get($item->custom_details, 'fabric_source', 'own');
                    $fabricQuantity = (float) data_get($item->custom_details, 'fabric_quantity', $item->quantity);

                    return [
                        'id' => 'existing-' . $item->id,
                        'category' => 'custom',
                        'productId' => $fabricProductId,
                        'name' => $fabricProduct
                            ? trim($fabricProduct->name . ' (' . $fabricProduct->code . ')')
                            : 'Custom Product',
                        'unitLabel' => 'm',
                        'qty' => $fabricQuantity,
                        'unitPrice' => $fabricSource === 'stock' ? (float) $item->unit_price : 0,
                        'baseUnitPrice' => (float) data_get($item->custom_details, 'fabric_unit_price', $item->unit_price),
                        'fabricSource' => $fabricSource,
                        'fabricQuantity' => $fabricQuantity,
                        'designNote' => (string) data_get($item->custom_details, 'design_note', ''),
                        'existingDesignImages' => array_values((array) data_get($item->custom_details, 'design_images', [])),
                        'garments' => collect((array) data_get($item->custom_details, 'garments', []))
                            ->map(function ($garment) {
                                return [
                                    'garmentTypeId' => (int) ($garment['garment_type_id'] ?? 0),
                                    'title' => (string) ($garment['garment_title'] ?? 'Garment'),
                                    'quantity' => (float) ($garment['quantity'] ?? 1),
                                    'measurements' => array_values((array) ($garment['measurements'] ?? [])),
                                    'tailoring' => [
                                        'packageId' => (int) ($garment['tailoring_package_id'] ?? 0),
                                        'package' => (string) ($garment['tailoring_package'] ?? 'Tailoring'),
                                        'amount' => (float) ($garment['tailoring_amount'] ?? 0),
                                    ],
                                ];
                            })
                            ->values()
                            ->all(),
                    ];
                })
                ->all(),
            'discount' => [
                'type' => (float) ($editingOrder->discount_amount ?? 0) > 0 ? 'flat' : 'none',
                'value' => (float) ($editingOrder->discount_amount ?? 0),
            ],
            'vatEnabled' => (bool) ($editingOrder->vat_enabled ?? false),
        ];
    }

    private function mapBillItemFromInput(array $item, int $index, Collection $productLookup, Collection $garmentLookup): ?array
    {
        $category = (string) ($item['item_category'] ?? '');
        if (!in_array($category, ['custom', 'fabric', 'readymade'], true)) {
            return null;
        }

        if ($category !== 'custom') {
            $productId = (int) ($item['product_id'] ?? 0);
            $product = $productLookup->get($productId);

            return [
                'id' => 'old-' . $index,
                'category' => $category,
                'productId' => $productId,
                'name' => $product
                    ? trim($product->name . ' (' . $product->code . ')')
                    : 'Product',
                'unitLabel' => $category === 'fabric' ? 'm' : 'pcs',
                'qty' => (float) ($item['quantity'] ?? 0),
                'unitPrice' => (float) ($item['unit_price'] ?? 0),
                'size' => $item['size'] ?? null,
            ];
        }

        $custom = (array) ($item['custom'] ?? []);
        $productId = (int) ($custom['fabric_product_id'] ?? 0);
        $product = $productLookup->get($productId);
        $fabricSource = (string) ($custom['fabric_source'] ?? 'own');
        $submittedUnitPrice = (float) ($item['unit_price'] ?? 0);
        $unitPrice = $fabricSource === 'stock' ? $submittedUnitPrice : 0;

        return [
            'id' => 'old-' . $index,
            'category' => 'custom',
            'productId' => $productId,
            'name' => $product
                ? trim($product->name . ' (' . $product->code . ')')
                : 'Custom Product',
            'unitLabel' => 'm',
            'qty' => (float) ($custom['fabric_quantity'] ?? ($item['quantity'] ?? 0)),
            'unitPrice' => $unitPrice,
            'baseUnitPrice' => $submittedUnitPrice,
            'fabricSource' => $fabricSource,
            'fabricQuantity' => (float) ($custom['fabric_quantity'] ?? ($item['quantity'] ?? 0)),
            'designNote' => (string) ($custom['design_note'] ?? ''),
            'existingDesignImages' => array_values((array) ($custom['existing_design_images'] ?? [])),
            'garments' => collect((array) ($custom['garments'] ?? []))
                ->map(function ($garment) use ($garmentLookup) {
                    $garmentTypeId = (int) ($garment['garment_type_id'] ?? 0);
                    $garmentType = $garmentLookup->get($garmentTypeId);

                    return [
                        'garmentTypeId' => $garmentTypeId,
                        'title' => (string) ($garment['garment_title'] ?? ($garmentType?->title ?? 'Garment')),
                        'quantity' => (float) ($garment['quantity'] ?? 1),
                        'measurements' => array_values((array) ($garment['measurements'] ?? [])),
                        'tailoring' => [
                            'packageId' => (int) ($garment['tailoring_package_id'] ?? 0),
                            'package' => (string) ($garment['tailoring_package'] ?? 'Tailoring'),
                            'amount' => (float) ($garment['tailoring_amount'] ?? 0),
                        ],
                    ];
                })
                ->values()
                ->all(),
        ];
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
