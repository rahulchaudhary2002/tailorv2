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
use App\Models\OrderTask;
use App\Models\Product;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\OrderInventoryService;
use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    private const WORKER_PERMISSION_KEY = 'view-assigned-jobs';

    public function __construct(private readonly OrderInventoryService $inventoryService) {}

    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $qLower = mb_strtolower($q);
        $status = trim((string) $request->query('status', ''));
        $paymentStatus = trim((string) $request->query('payment_status', ''));
        $outletId = $this->currentOutletId();

        $ordersQuery = Order::query()
            ->with([
                'outlet:id,name',
                'customer:id,name,phone',
                'creator:id,name',
                'tasks:id,order_id,worker_id,worker_deadline_at,status',
                'tasks.worker:id,name',
            ]);

        if ($outletId > 0) {
            $ordersQuery->where('outlet_id', $outletId);
        } else {
            $ordersQuery->whereRaw('1 = 0');
        }

        if ($q !== '') {
            $ordersQuery->where(function ($query) use ($qLower): void {
                $query->whereRaw('LOWER(order_number) LIKE ?', ['%'.$qLower.'%'])
                    ->orWhereRaw('LOWER(status) LIKE ?', ['%'.$qLower.'%'])
                    ->orWhereRaw('LOWER(payment_status) LIKE ?', ['%'.$qLower.'%'])
                    ->orWhereHas('customer', function ($customerQuery) use ($qLower): void {
                        $customerQuery->whereRaw('LOWER(name) LIKE ?', ['%'.$qLower.'%'])
                            ->orWhereRaw('LOWER(phone) LIKE ?', ['%'.$qLower.'%']);
                    });
            });
        }

        if ($status !== '' && array_key_exists($status, Order::statusLabels())) {
            $ordersQuery->where('status', $status);
        }

        if ($paymentStatus !== '' && array_key_exists($paymentStatus, Order::paymentStatusLabels())) {
            $ordersQuery->where('payment_status', $paymentStatus);
        }

        $orders = $ordersQuery
            ->latest('ordered_at')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $statusLabels = Order::statusLabels();
        $paymentStatusLabels = Order::paymentStatusLabels();

        return view('modules.order.index', compact(
            'orders',
            'statusLabels',
            'paymentStatusLabels',
            'status',
            'paymentStatus'
        ));
    }

    /**
     * Display orders assigned to the logged in user.
     */
    public function assignedJobs(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $qLower = mb_strtolower($q);
        $status = trim((string) $request->query('status', ''));
        $user = auth()->user();
        $userId = (int) auth()->id();
        $accessibleOutletIds = $user?->outlets()
            ->pluck('outlets.id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values() ?? collect();

        $tasksQuery = OrderTask::query()
            ->with([
                'order:id,order_number,customer_id,delivery_due_at,outlet_id,status',
                'order.outlet:id,name',
                'order.customer:id,name,phone',
            ])
            ->where('worker_id', $userId)
            ->whereIn('status', [
                OrderTask::STATUS_ASSIGNED,
                OrderTask::STATUS_IN_PROGRESS,
                OrderTask::STATUS_COMPLETED,
            ]);

        if ($accessibleOutletIds->isNotEmpty()) {
            $tasksQuery->whereHas('order', function ($query) use ($accessibleOutletIds): void {
                $query->whereIn('outlet_id', $accessibleOutletIds);
            });
        } else {
            $tasksQuery->whereRaw('1 = 0');
        }

        if ($q !== '') {
            $tasksQuery->where(function ($query) use ($qLower): void {
                $query->whereRaw('LOWER(task_number) LIKE ?', ['%'.$qLower.'%'])
                    ->orWhereRaw('LOWER(task_title) LIKE ?', ['%'.$qLower.'%'])
                    ->orWhereHas('order', function ($orderQuery) use ($qLower): void {
                        $orderQuery->whereRaw('LOWER(order_number) LIKE ?', ['%'.$qLower.'%'])
                            ->orWhereRaw('LOWER(status) LIKE ?', ['%'.$qLower.'%'])
                            ->orWhereHas('customer', function ($customerQuery) use ($qLower): void {
                                $customerQuery->whereRaw('LOWER(name) LIKE ?', ['%'.$qLower.'%'])
                                    ->orWhereRaw('LOWER(phone) LIKE ?', ['%'.$qLower.'%']);
                            });
                    });
            });
        }

        if ($status !== '' && array_key_exists($status, OrderTask::statusLabels())) {
            $tasksQuery->where('status', $status);
        }

        $tasks = $tasksQuery
            ->latest('assigned_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('modules.order.assigned_jobs', [
            'tasks' => $tasks,
            'statusLabels' => OrderTask::statusLabels(),
            'selectedStatus' => $status,
        ]);
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
        $isOwnAssignedOrder = $order->tasks()
            ->where('worker_id', (int) ($user?->id ?? 0))
            ->exists();

        if (! $canManageOrders && ! $isOwnAssignedOrder) {
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
     * Read-only order details.
     */
    public function show(Order $order)
    {
        $this->ensureOrderBelongsToCurrentOutlet($order);
        $outletId = $this->currentOutletId();

        $order->load([
            'outlet:id,name',
            'customer:id,name,phone,email,address',
            'creator:id,name',
            'tasks:id,order_id,order_item_id,source_garment_index,worker_id,task_number,worker_deadline_at,status,notes,slip_received_at,paid_at',
            'tasks.worker:id,name',
            'items.product:id,name,code',
            'items.unit:id,name,symbol',
        ]);

        $workerRoleId = (int) (\App\Models\Role::query()->where('name', 'Worker')->value('id') ?? 0);
        $workers = User::query()
            ->where('is_super_admin', false)
            ->when($workerRoleId > 0, function ($query) use ($workerRoleId, $outletId): void {
                $query->whereHas('roles', function ($roleQuery) use ($workerRoleId, $outletId): void {
                    $roleQuery->where('roles.id', $workerRoleId);

                    if ($outletId > 0) {
                        $roleQuery->where('user_role.outlet_id', $outletId);
                    }
                });
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $customFabricProducts = Product::query()
            ->whereIn(
                'id',
                $order->items
                    ->pluck('custom_details.fabric_product_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
            )
            ->get(['id', 'name', 'code'])
            ->keyBy('id');

        return view('modules.order.show', compact('order', 'customFabricProducts', 'workers'));
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

                if (! array_key_exists($productId, $productAvailableQty)) {
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
            ->ordered()
            ->get(['id', 'title', 'sort_order', 'design_note']);

        $selectedCustomerId = $request->query('customer_id');

        $initialOrderState = $this->buildInitialOrderState($request, $products, $garmentTypes, $editingOrder);

        return compact(
            'products',
            'customers',
            'garmentTypes',
            'selectedCustomerId',
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
        $customerType = (string) ($validated['customer_type'] ?? 'retail');

        if ($name === '') {
            return response()->json([
                'status' => 'missing',
                'message' => 'Customer not found. Enter customer details to create one.',
            ]);
        }

        $customer = Customer::query()->create([
            'name' => $name,
            'email' => null,
            'phone' => $phone,
            'customer_type' => $customerType,
            'address' => null,
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

        $order->save();

        $this->notifyOrderRecipients(
            $order,
            'Order delivery date updated',
            'Delivery date for order '.$order->order_number.' was updated to '.($order->delivery_due_at?->format('M d, Y h:i A') ?: '-')
        );

        return back()
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

        if (! $outletLocation) {
            return redirect()
                ->route('order.index')
                ->with('error', 'No active inventory location found for your current outlet.');
        }

        $inventoryTypeId = InventoryType::query()
            ->where('code', InventoryType::OUTLET)
            ->value('id');

        if (! $inventoryTypeId) {
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
            ->map(fn ($id) => (int) $id)
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
            if (! $product) {
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
            if (! array_key_exists($stockKey, $requiredBySku)) {
                $requiredBySku[$stockKey] = [
                    'product_id' => $productId,
                    'required_qty' => 0.0,
                ];
            }

            $requiredBySku[$stockKey]['required_qty'] += $requiredQty;
        }

        if (! empty($requiredBySku)) {
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
                if (! array_key_exists($stockKey, $availableMap)) {
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
                ->get(['id', 'title'])
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
                $hasFabricIssuedOrLaterStatus = in_array($status, [
                    Order::STATUS_FABRIC_ISSUED,
                    Order::STATUS_ASSIGNED,
                    Order::STATUS_IN_PROGRESS,
                    Order::STATUS_NEAR_COMPLETION,
                    Order::STATUS_COMPLETED,
                ], true);

                $savedTasksByPosition = [];

                if ($existingOrder) {
                    $existingItemsInOrder = $existingOrder->items()->orderBy('id')->get();
                    foreach ($existingItemsInOrder as $position => $existingItem) {
                        $itemTasks = $existingItem->tasks()->get();
                        if ($itemTasks->isNotEmpty()) {
                            $savedTasksByPosition[$position] = $itemTasks->map(fn (OrderTask $task) => collect($task->getAttributes())
                                ->except(['id', 'order_id', 'order_item_id', 'created_at', 'updated_at'])
                                ->toArray()
                            )->all();
                        }
                    }

                    if ($this->inventoryService->orderHasIssuedStock($existingOrder)) {
                        $this->inventoryService->restoreOrderInventory($existingOrder);
                    } else {
                        $this->inventoryService->releaseReservedStockForOrder($existingOrder, (int) $outletLocation->id);
                    }
                    $existingOrder->items()->delete();
                }

                $order = $existingOrder ?: new Order;

                $order->fill([
                    'order_number' => $order->exists ? $order->order_number : $this->generateOrderNumber(),
                    'outlet_id' => $outletId,
                    'customer_id' => (int) $validated['customer_id'],
                    'ordered_at' => $validated['ordered_at'],
                    'delivery_due_at' => $validated['delivery_due_at'] ?? Date::now()->addDays(5),
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
                $newItemIdByIndex = [];

                foreach ($items as $itemIndex => $item) {
                    $itemCategory = (string) ($item['item_category'] ?? 'readymade');
                    $quantity = (float) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];
                    $itemTailoringTotal = 0.0;
                    if ($itemCategory === 'custom') {
                        $custom = (array) ($item['custom'] ?? []);
                        $fabricSource = (string) ($custom['fabric_source'] ?? 'own');
                        $customDesignNote = trim((string) ($custom['design_note'] ?? ''));
                        $fabricProductId = ! empty($custom['fabric_product_id']) ? (int) $custom['fabric_product_id'] : null;
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
                            ->map(function ($garment, $garmentIndex) use ($garmentTypes, $itemIndex, $request) {
                                $garmentTypeId = (int) ($garment['garment_type_id'] ?? 0);
                                $garmentType = $garmentTypes->get($garmentTypeId);
                                $garmentQty = (float) ($garment['quantity'] ?? 1);
                                $tailoringAmount = (float) ($garment['tailoring_amount'] ?? 0);
                                $taxPercent = 0.0;
                                $tailoringTotal = $garmentQty * $tailoringAmount;
                                $garmentDesignImagePaths = collect((array) ($garment['existing_design_images'] ?? []))
                                    ->map(fn ($path) => trim((string) $path))
                                    ->filter()
                                    ->values()
                                    ->all();
                                $garmentDesignImages = (array) $request->file("items.{$itemIndex}.custom.garments.{$garmentIndex}.design_images", []);

                                foreach ($garmentDesignImages as $garmentDesignImage) {
                                    if (! $garmentDesignImage || ! $garmentDesignImage->isValid()) {
                                        continue;
                                    }

                                    $storedPath = Storage::disk('public')->putFile('order-designs', $garmentDesignImage);
                                    if ($storedPath) {
                                        $garmentDesignImagePaths[] = $storedPath;
                                    }
                                }

                                return [
                                    'garment_type_id' => $garmentTypeId,
                                    'garment_title' => $garment['garment_title'] ?? $garmentType?->title,
                                    'quantity' => $garmentQty,
                                    'measurements' => array_values((array) ($garment['measurements'] ?? [])),
                                    'design_note' => collect((array) ($garment['design_note'] ?? []))
                                        ->map(fn ($note) => trim((string) $note))
                                        ->filter()
                                        ->values()
                                        ->all(),
                                    'design_images' => array_values(array_unique($garmentDesignImagePaths)),
                                    'design_image' => $garmentDesignImagePaths[0] ?? null,
                                    'tailoring_package_id' => ! empty($garment['tailoring_package_id'])
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
                            })
                            ->values();
                        $itemTailoringTotal = (float) $garments->sum(fn ($garment) => (float) ($garment['tailoring_total_amount'] ?? 0));
                        $designNoteSummary = $garments
                            ->flatMap(fn ($garment) => (array) ($garment['design_note'] ?? []))
                            ->map(fn ($note) => trim((string) $note))
                            ->filter()
                            ->unique()
                            ->implode(', ');
                        $designImagePaths = $garments
                            ->flatMap(fn ($garment) => (array) ($garment['design_images'] ?? []))
                            ->map(fn ($path) => trim((string) $path))
                            ->filter()
                            ->unique()
                            ->values()
                            ->all();

                        $newItemIdByIndex[$itemIndex] = $order->items()->create([
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
                                'design_note' => $customDesignNote !== '' ? $customDesignNote : null,
                                'garment_design_note_summary' => $designNoteSummary !== '' ? $designNoteSummary : null,
                                'design_images' => $designImagePaths,
                                'design_image' => $designImagePaths[0] ?? null,
                            ],
                        ])->id;

                    } else {
                        $productId = (int) $item['product_id'];
                        $product = $products->get($productId);
                        $submittedUnitPrice = (float) ($item['unit_price'] ?? 0.0);
                        $resolvedUnitPrice = (float) ($effectiveProductPrices[$productId] ?? 0.0);
                        $unitPrice = $resolvedUnitPrice > 0 ? $resolvedUnitPrice : $submittedUnitPrice;
                        $lineTotal = $quantity * $unitPrice;

                        $newItemIdByIndex[$itemIndex] = $order->items()->create([
                            'item_category' => $itemCategory,
                            'product_id' => $productId,
                            'unit_id' => null,
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'line_total' => $lineTotal,
                            'custom_details' => $itemCategory === 'readymade'
                                ? ['size' => $item['size'] ?? null]
                                : null,
                        ])->id;

                    }

                    $subtotal += $lineTotal;
                    if ($itemCategory === 'custom') {
                        $tailoringTotal += $itemTailoringTotal;
                    }
                }

                foreach ($newItemIdByIndex as $itemIndex => $newItemId) {
                    if (! isset($savedTasksByPosition[$itemIndex])) {
                        continue;
                    }
                    foreach ($savedTasksByPosition[$itemIndex] as $taskData) {
                        OrderTask::create(array_merge($taskData, [
                            'order_id' => $order->id,
                            'order_item_id' => $newItemId,
                        ]));
                    }
                }

                $discountAmount = (float) ($validated['discount_amount'] ?? 0);
                $taxableSubtotal = max(0.0, $subtotal - $discountAmount);
                $taxableTotal = $taxableSubtotal + $tailoringTotal;
                $vatAmount = $vatEnabled
                    ? round($taxableTotal * 0.13, 2)
                    : 0.0;
                $payableAmount = max(0.0, $taxableTotal + $vatAmount);
                $advanceAmount = min((float) ($validated['advance_payment_amount'] ?? 0), $payableAmount);
                $paymentStatus = Order::PAYMENT_STATUS_UNPAID;

                if ($payableAmount <= 0.0001) {
                    $paymentStatus = Order::PAYMENT_STATUS_PAID;
                    $advanceAmount = 0.0;
                } elseif ($advanceAmount > 0.0001 && $advanceAmount + 0.0001 < $payableAmount) {
                    $paymentStatus = Order::PAYMENT_STATUS_PARTIAL;
                } elseif ($advanceAmount + 0.0001 >= $payableAmount) {
                    $paymentStatus = Order::PAYMENT_STATUS_PAID;
                    $advanceAmount = $payableAmount;
                }

                $order->subtotal_amount = $subtotal;
                $order->tailoring_amount = $tailoringTotal;
                $order->vat_amount = $vatAmount;
                $order->advance_payment_amount = $advanceAmount;
                $order->payment_status = $paymentStatus;
                $order->save();

                // The order instance may still carry previously loaded items from before edit.
                // Clear and reload relations so reservation/issue uses the newly saved items.
                $order->unsetRelation('items');

                $this->syncCustomerMeasurementsFromOrderItems((int) $validated['customer_id'], $items);

                if ($this->inventoryService->orderHasIssuedStock($order)) {
                    $this->inventoryService->issueStockForOrder(
                        $order,
                        (int) $outletLocation->id,
                        (int) $inventoryTypeId,
                        $validated['ordered_at'],
                        (int) auth()->id()
                    );
                } else {
                    $this->inventoryService->reserveStockForOrder($order, (int) $outletLocation->id);
                }

                $savedOrder = $order;
            });
        } catch (\RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        if ($savedOrder) {
            $savedOrder->loadMissing(['customer:id,name', 'outlet:id,name']);
            $this->notifyOrderRecipients(
                $savedOrder,
                $existingOrder ? 'Order updated' : 'Order created',
                ($existingOrder ? 'Order ' : 'New order ').$savedOrder->order_number.' for '.($savedOrder->customer?->name ?: 'Walk-in').' was '.($existingOrder ? 'updated.' : 'created.')
            );
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
        $isOwnAssignedOrder = $order->tasks()
            ->where('worker_id', (int) ($user?->id ?? 0))
            ->exists();
        $redirectRoute = $canManageOrders ? 'order.index' : 'order.assignedJobs';

        if (! $canManageOrders) {
            if (! $canViewAssignedJobs || ! $isOwnAssignedOrder) {
                return redirect()
                    ->route($redirectRoute)
                    ->with('error', 'You can only update statuses for jobs assigned to you.');
            }
        }

        $validated = $request->validated();
        $targetStatus = (string) ($validated['status'] ?? '');

        if ($targetStatus === Order::STATUS_COMPLETED) {
            $hasIncompleteTasks = $order->tasks()
                ->whereNotIn('status', [OrderTask::STATUS_COMPLETED, OrderTask::STATUS_CANCELLED])
                ->exists();

            if ($hasIncompleteTasks) {
                return redirect()
                    ->route($redirectRoute)
                    ->with('error', 'Complete all tasks before marking the order as completed.');
            }
        }

        if (! $canManageOrders) {
            $allowedWorkerStatuses = [
                Order::STATUS_IN_PROGRESS,
                Order::STATUS_NEAR_COMPLETION,
                Order::STATUS_COMPLETED,
            ];

            if (! in_array($targetStatus, $allowedWorkerStatuses, true)) {
                return redirect()
                    ->route($redirectRoute)
                    ->with('error', 'You can only move your assigned jobs through progress statuses.');
            }
        }

        try {
            DB::transaction(function () use ($order, $validated): void {
                $targetStatus = (string) ($validated['status'] ?? '');
                $currentStatus = (string) $order->status;
                $outletLocationId = $this->inventoryService->resolveOutletLocationId((int) $order->outlet_id);
                $targetHasIssuedStock = in_array($targetStatus, [
                    Order::STATUS_FABRIC_ISSUED,
                    Order::STATUS_ASSIGNED,
                    Order::STATUS_IN_PROGRESS,
                    Order::STATUS_NEAR_COMPLETION,
                    Order::STATUS_COMPLETED,
                    Order::STATUS_DELIVERED,
                ], true);

                if ($outletLocationId < 1) {
                    throw new \RuntimeException('No active inventory location found for this order outlet.');
                }

                if ($targetHasIssuedStock && ! $this->inventoryService->orderHasIssuedStock($order)) {
                    $inventoryTypeId = $this->inventoryService->resolveOutletInventoryTypeId();

                    if ($inventoryTypeId < 1) {
                        throw new \RuntimeException('Inventory type outlet is missing. Run inventory type seeder.');
                    }

                    $this->inventoryService->issueStockForOrder(
                        $order,
                        $outletLocationId,
                        $inventoryTypeId,
                        now(),
                        (int) auth()->id()
                    );
                }

                if ($targetStatus === Order::STATUS_CANCELLED) {
                    if ($this->inventoryService->orderHasIssuedStock($order)) {
                        $this->inventoryService->restoreOrderInventory($order);
                    } else {
                        $this->inventoryService->releaseReservedStockForOrder($order, $outletLocationId);
                    }
                } elseif (! $targetHasIssuedStock && $this->inventoryService->orderHasIssuedStock($order)) {
                    $this->inventoryService->restoreOrderInventory($order);
                    $this->inventoryService->reserveStockForOrder($order, $outletLocationId);
                } elseif ($currentStatus === Order::STATUS_CANCELLED && ! $targetHasIssuedStock) {
                    $this->inventoryService->reserveStockForOrder($order, $outletLocationId);
                }

                app(OrderWorkflowService::class)->transition($order, $validated);
            });
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route($redirectRoute)
                ->with('error', $exception->getMessage());
        }

        $order->refresh();
        $this->notifyOrderRecipients(
            $order,
            'Order status updated',
            'Order '.$order->order_number.' status changed to '.Order::statusLabel((string) $order->status).'.'
        );

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Order status updated successfully.');
    }

    public function updatePayment(UpdatePaymentRequest $request, Order $order)
    {
        $this->ensureOrderBelongsToCurrentOutlet($order);

        if ((string) $order->status === Order::STATUS_CANCELLED) {
            return back()
                ->with('error', 'Cancelled orders cannot receive payments.');
        }

        $validated = $request->validated();
        $paymentAmount = (float) $validated['payment_amount'];
        $currentPaid = (float) ($order->advance_payment_amount ?? 0);
        $payableAmount = $order->payableAmount();
        $dueAmount = max(0.0, $payableAmount - $currentPaid);

        if ($paymentAmount - 0.0001 > $dueAmount) {
            return back()
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

        $this->notifyOrderRecipients(
            $order,
            'Order payment recorded',
            'Payment of NPR '.number_format($paymentAmount, 2).' was recorded for order '.$order->order_number.'.'
        );

        return back()
            ->with('success', 'Payment recorded successfully.');
    }

    private function notifyOrderRecipients(Order $order, string $title, string $message): void
    {
        $actorName = (string) (auth()->user()?->name ?: 'System');

        app(NotificationService::class)->notifyPermission(
            'receive-order-notifications',
            (int) ($order->outlet_id ?? 0),
            [
                'title' => $title,
                'message' => $actorName.': '.$message,
                'url' => route('order.show', $order),
                'module' => 'Order',
            ],
            array_filter([(int) auth()->id()])
        );
    }

    private function generateOrderNumber(): string
    {
        $prefix = 'ORD-'.now()->format('Ymd');
        $count = Order::query()
            ->whereDate('created_at', now()->toDateString())
            ->lockForUpdate()
            ->get(['id'])
            ->count();

        return $prefix.'-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareBillData(Order $order): array
    {
        $order->loadMissing([
            'outlet:id,name',
            'customer:id,name,email,phone,address',
            'tasks:id,order_id,worker_id,worker_deadline_at,payable_amount,status',
            'tasks.worker:id,name,email',
            'items.product:id,name,code',
            'items.unit:id,name,symbol',
        ]);

        $items = $order->items;
        $customFabricProducts = Product::query()
            ->whereIn(
                'id',
                $items
                    ->pluck('custom_details.fabric_product_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
            )
            ->get(['id', 'name', 'code'])
            ->keyBy('id');
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
            'customFabricProducts' => $customFabricProducts,
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
                'advancePaymentAmount' => (float) $request->old('advance_payment_amount', 0),
                'discount' => [
                    'type' => (float) $request->old('discount_amount', 0) > 0 ? 'flat' : 'none',
                    'value' => (float) $request->old('discount_amount', 0),
                ],
                'vatEnabled' => $request->old('vat_enabled', '0') === '1',
            ];
        }

        if (! $editingOrder) {
            return [
                'items' => [],
                'advancePaymentAmount' => 0,
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
                        $itemCategory = (string) $item->item_category;

                        return [
                            'id' => 'existing-'.$item->id,
                            'category' => $itemCategory,
                            'productId' => (int) $item->product_id,
                            'name' => $item->product
                                ? trim($item->product->name.' ('.$item->product->code.')')
                                : 'Product',
                            'unitLabel' => $itemCategory === 'fabric' ? 'm' : 'pcs',
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
                        'id' => 'existing-'.$item->id,
                        'category' => 'custom',
                        'productId' => $fabricProductId,
                        'name' => $fabricProduct
                            ? trim($fabricProduct->name.' ('.$fabricProduct->code.')')
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
                                    'designNotes' => array_values((array) ($garment['design_note'] ?? [])),
                                    'existingDesignImages' => array_values((array) ($garment['design_images'] ?? [])),
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
            'advancePaymentAmount' => (float) ($editingOrder->advance_payment_amount ?? 0),
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
        if (! in_array($category, ['custom', 'fabric', 'readymade', 'accessories'], true)) {
            return null;
        }

        if ($category !== 'custom') {
            $productId = (int) ($item['product_id'] ?? 0);
            $product = $productLookup->get($productId);

            return [
                'id' => 'old-'.$index,
                'category' => $category,
                'productId' => $productId,
                'name' => $product
                    ? trim($product->name.' ('.$product->code.')')
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
            'id' => 'old-'.$index,
            'category' => 'custom',
            'productId' => $productId,
            'name' => $product
                ? trim($product->name.' ('.$product->code.')')
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
                        'designNotes' => array_values((array) ($garment['design_note'] ?? [])),
                        'existingDesignImages' => array_values((array) ($garment['existing_design_images'] ?? $garment['design_images'] ?? [])),
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

    private function syncCustomerMeasurementsFromOrderItems(int $customerId, Collection $items): void
    {
        if ($customerId < 1) {
            return;
        }

        $garmentsByType = [];

        foreach ($items as $item) {
            if ((string) ($item['item_category'] ?? '') !== 'custom') {
                continue;
            }

            foreach ((array) data_get($item, 'custom.garments', []) as $garment) {
                $garmentTypeId = (int) ($garment['garment_type_id'] ?? 0);
                if ($garmentTypeId < 1) {
                    continue;
                }

                $measurements = collect((array) ($garment['measurements'] ?? []))
                    ->map(function ($measurement) {
                        return [
                            'type' => trim((string) ($measurement['type'] ?? '')),
                            'measurement' => trim((string) ($measurement['measurement'] ?? '')),
                            'unit' => trim((string) ($measurement['unit'] ?? '')),
                        ];
                    })
                    ->filter(fn (array $measurement) => $measurement['type'] !== '' && $measurement['measurement'] !== '' && $measurement['unit'] !== '')
                    ->values()
                    ->all();

                if (empty($measurements)) {
                    continue;
                }

                $garmentsByType[$garmentTypeId] = $measurements;
            }
        }

        if (empty($garmentsByType)) {
            return;
        }

        $customer = Customer::query()->find($customerId);
        if (! $customer) {
            return;
        }

        foreach ($garmentsByType as $garmentTypeId => $measurements) {
            $customerGarmentType = $customer->customerGarmentTypes()->firstOrCreate([
                'garment_type_id' => (int) $garmentTypeId,
            ]);

            $customerGarmentType->measurements()->delete();

            foreach ($measurements as $index => $measurement) {
                $customerGarmentType->measurements()->create([
                    'type' => $measurement['type'],
                    'measurement' => $measurement['measurement'],
                    'unit' => $measurement['unit'],
                    'order' => $index + 1,
                ]);
            }
        }
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
