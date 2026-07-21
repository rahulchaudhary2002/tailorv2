<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InventoryAlert;
use App\Models\InventoryLocation;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTask;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\VendorRawMaterialPurchase;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function search(Request $request)
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $q = trim((string) $request->query('q', ''));
        $qLower = mb_strtolower($q);
        $outletId = (int) ($user->current_outlet_id ?? 0);
        $workerRoleId = (int) (Role::query()->where('name', 'Worker')->value('id') ?? 0);

        $results = [
            'orders' => collect(),
            'customers' => collect(),
            'products' => collect(),
            'workers' => collect(),
            'tasks' => collect(),
        ];

        if ($q !== '') {
            if ($user->hasPermission('view-orders') || $user->hasPermission('manage-orders') || $user->hasPermission('view-assigned-jobs')) {
                $ordersQuery = Order::query()
                    ->with(['customer:id,name,phone', 'outlet:id,name'])
                    ->when($outletId > 0, fn ($query) => $query->where('outlet_id', $outletId))
                    ->when(
                        ! $user->hasPermission('view-orders') && ! $user->hasPermission('manage-orders'),
                        fn ($query) => $query->whereHas('tasks', fn ($taskQuery) => $taskQuery->where('worker_id', (int) $user->id))
                    )
                    ->where(function ($query) use ($qLower): void {
                        $query->whereRaw('LOWER(order_number) LIKE ?', ['%'.$qLower.'%'])
                            ->orWhereHas('customer', function ($customerQuery) use ($qLower): void {
                                $customerQuery->whereRaw('LOWER(name) LIKE ?', ['%'.$qLower.'%'])
                                    ->orWhereRaw('LOWER(phone) LIKE ?', ['%'.$qLower.'%']);
                            });
                    })
                    ->latest('id')
                    ->limit(8);

                $results['orders'] = $ordersQuery->get();
            }

            if ($user->hasPermission('view-customers') || $user->hasPermission('manage-customers')) {
                $results['customers'] = Customer::query()
                    ->when($outletId > 0, function ($query) use ($outletId): void {
                        $query->whereHas('orders', fn ($orderQuery) => $orderQuery->where('outlet_id', $outletId));
                    })
                    ->where(function ($query) use ($qLower): void {
                        $query->whereRaw('LOWER(name) LIKE ?', ['%'.$qLower.'%'])
                            ->orWhereRaw('LOWER(phone) LIKE ?', ['%'.$qLower.'%'])
                            ->orWhereRaw('LOWER(COALESCE(email, \'\')) LIKE ?', ['%'.$qLower.'%']);
                    })
                    ->latest('id')
                    ->limit(8)
                    ->get();
            }

            if ($user->hasPermission('view-products') || $user->hasPermission('manage-products')) {
                $results['products'] = Product::query()
                    ->with('category:id,name,slug')
                    ->where(function ($query) use ($qLower): void {
                        $query->whereRaw('LOWER(name) LIKE ?', ['%'.$qLower.'%'])
                            ->orWhereRaw('LOWER(code) LIKE ?', ['%'.$qLower.'%']);
                    })
                    ->latest('id')
                    ->limit(8)
                    ->get();
            }

            if (($user->hasPermission('view-task-management') || $user->hasPermission('manage-task-management') || $user->hasPermission('manage-orders')) && $workerRoleId > 0) {
                $results['workers'] = User::query()
                    ->where('is_super_admin', false)
                    ->whereExists(function ($query) use ($workerRoleId, $outletId): void {
                        $query->selectRaw('1')
                            ->from('user_role')
                            ->whereColumn('user_role.user_id', 'users.id')
                            ->where('user_role.role_id', $workerRoleId);

                        if ($outletId > 0) {
                            $query->where('user_role.outlet_id', $outletId);
                        }
                    })
                    ->where(function ($query) use ($qLower): void {
                        $query->whereRaw('LOWER(name) LIKE ?', ['%'.$qLower.'%'])
                            ->orWhereRaw('LOWER(email) LIKE ?', ['%'.$qLower.'%']);
                    })
                    ->orderBy('name')
                    ->limit(8)
                    ->get(['id', 'name', 'email']);
            }

            if ($user->hasPermission('view-task-management') || $user->hasPermission('manage-task-management') || $user->hasPermission('manage-orders') || $user->hasPermission('view-assigned-jobs')) {
                $results['tasks'] = OrderTask::query()
                    ->with([
                        'order:id,order_number,customer_id,outlet_id',
                        'order.customer:id,name',
                        'worker:id,name',
                    ])
                    ->whereHas('order', function ($query) use ($outletId): void {
                        if ($outletId > 0) {
                            $query->where('outlet_id', $outletId);
                        }
                    })
                    ->when(
                        $user->hasPermission('view-assigned-jobs') && ! $user->hasPermission('view-task-management') && ! $user->hasPermission('manage-task-management') && ! $user->hasPermission('manage-orders'),
                        fn ($query) => $query->where('worker_id', (int) $user->id)
                    )
                    ->where(function ($query) use ($qLower): void {
                        $query->whereRaw('LOWER(task_number) LIKE ?', ['%'.$qLower.'%'])
                            ->orWhereRaw('LOWER(task_title) LIKE ?', ['%'.$qLower.'%'])
                            ->orWhereHas('order', function ($orderQuery) use ($qLower): void {
                                $orderQuery->whereRaw('LOWER(order_number) LIKE ?', ['%'.$qLower.'%'])
                                    ->orWhereHas('customer', function ($customerQuery) use ($qLower): void {
                                        $customerQuery->whereRaw('LOWER(name) LIKE ?', ['%'.$qLower.'%']);
                                    });
                            });
                    })
                    ->latest('id')
                    ->limit(8)
                    ->get();
            }
        }

        return view('modules.search.index', [
            'query' => $q,
            'results' => $results,
        ]);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $now = now();
        $range = (string) $request->query('range', 'today');
        $chartView = (string) $request->query('chart_view', 'bar');
        $outletChartView = (string) $request->query('outlet_chart_view', 'table');
        $trendGroup = (string) $request->query('trend_group', 'day');
        $trendMetric = (string) $request->query('trend_metric', 'sales');
        $orderStatus = trim((string) $request->query('order_status', ''));
        $paymentStatus = trim((string) $request->query('payment_status', ''));

        if (! in_array($chartView, ['bar', 'line'], true)) {
            $chartView = 'bar';
        }

        if (! in_array($outletChartView, ['table', 'bars'], true)) {
            $outletChartView = 'table';
        }
        if (! in_array($trendGroup, ['day', 'week', 'month'], true)) {
            $trendGroup = 'day';
        }
        if (! in_array($trendMetric, ['sales', 'orders'], true)) {
            $trendMetric = 'sales';
        }

        $accessibleOutletIds = $user->outlets()->pluck('outlets.id')->map(fn ($id) => (int) $id)->values();
        $currentOutletId = (int) ($user->current_outlet_id ?? 0);

        $isWorker = $user->hasPermission('view-assigned-jobs') && ! $user->hasPermission('manage-orders');
        // Keep owner/admin dashboard strictly for super admin.
        // Other non-worker users should see outlet manager dashboard.
        $isOwnerAdmin = (bool) $user->is_super_admin;
        $roleScope = $isWorker ? 'worker' : ($isOwnerAdmin ? 'owner_admin' : 'outlet_manager');

        [$dateFrom, $dateTo, $rangeLabel] = $this->resolveDateRange($request, $range, $now);

        $selectedOutletId = 0;
        if ($isOwnerAdmin) {
            $candidateOutletId = (int) $request->query('outlet_id', 0);
            if ($candidateOutletId > 0 && ($user->is_super_admin || $accessibleOutletIds->contains($candidateOutletId))) {
                $selectedOutletId = $candidateOutletId;
            }
        } elseif ($currentOutletId > 0) {
            $selectedOutletId = $currentOutletId;
        } elseif ($accessibleOutletIds->isNotEmpty()) {
            $selectedOutletId = (int) $accessibleOutletIds->first();
        }

        $scopeOutletIds = collect();
        if ($selectedOutletId > 0) {
            $scopeOutletIds = collect([$selectedOutletId]);
        } elseif (! $isOwnerAdmin) {
            $scopeOutletIds = $accessibleOutletIds;
        }

        $salesAmountSql = 'COALESCE(SUM(GREATEST(0, COALESCE(subtotal_amount, 0) - COALESCE(discount_amount, 0) + COALESCE(tailoring_amount, 0) + CASE WHEN vat_enabled IS TRUE THEN COALESCE(vat_amount, 0) ELSE 0 END)), 0)';

        $orderScope = Order::query()
            ->whereBetween('ordered_at', [$dateFrom, $dateTo])
            ->where('status', '!=', Order::STATUS_CANCELLED);

        if ($scopeOutletIds->isNotEmpty()) {
            $orderScope->whereIn('outlet_id', $scopeOutletIds);
        } elseif (! $isOwnerAdmin) {
            $orderScope->whereRaw('1=0');
        }

        if ($orderStatus !== '') {
            $orderScope->where('status', $orderStatus);
        }

        if ($paymentStatus !== '') {
            $orderScope->where('payment_status', $paymentStatus);
        }

        $totalSales = (float) (clone $orderScope)
            ->selectRaw($salesAmountSql.' as total')
            ->value('total');

        $ordersCount = (int) (clone $orderScope)->count();
        $advanceCollected = (float) (clone $orderScope)->sum('advance_payment_amount');
        $totalPaid = (float) (clone $orderScope)
            ->where('payment_status', Order::PAYMENT_STATUS_PAID)
            ->sum('advance_payment_amount');
        $cashCollected = (float) (clone $orderScope)
            ->where('payment_method', Order::PAYMENT_METHOD_CASH)
            ->sum('advance_payment_amount');
        $pendingPayments = (float) (clone $orderScope)
            ->where('payment_status', '!=', Order::PAYMENT_STATUS_PAID)
            ->selectRaw(
                'COALESCE(SUM(GREATEST(0, '.
                '(COALESCE(subtotal_amount, 0) - COALESCE(discount_amount, 0) + COALESCE(tailoring_amount, 0) + CASE WHEN vat_enabled IS TRUE THEN COALESCE(vat_amount, 0) ELSE 0 END)'.
                ' - COALESCE(advance_payment_amount, 0)'.
                ')), 0) as total'
            )
            ->value('total');
        $deliveredOrders = (int) (clone $orderScope)
            ->where('status', Order::STATUS_DELIVERED)
            ->count();

        $inventoryValueQuery = InventoryStock::query()
            ->join('inventory_locations', 'inventory_locations.id', '=', 'inventory_stocks.location_id')
            ->selectRaw('COALESCE(SUM(COALESCE(inventory_stocks.on_hand_qty, 0) * COALESCE(inventory_stocks.unit_cost, 0)), 0) as stock_value');

        if ($scopeOutletIds->isNotEmpty()) {
            $inventoryValueQuery->whereIn('inventory_locations.outlet_id', $scopeOutletIds);
        }

        $inventoryValue = (float) $inventoryValueQuery->value('stock_value');

        $trendStart = $dateFrom->copy()->startOfDay();
        $trendEnd = $dateTo->copy()->endOfDay();

        $trendQuery = Order::query()
            ->whereBetween('ordered_at', [$trendStart, $trendEnd])
            ->where('status', '!=', Order::STATUS_CANCELLED);

        if ($scopeOutletIds->isNotEmpty()) {
            $trendQuery->whereIn('outlet_id', $scopeOutletIds);
        } elseif (! $isOwnerAdmin) {
            $trendQuery->whereRaw('1=0');
        }

        if ($orderStatus !== '') {
            $trendQuery->where('status', $orderStatus);
        }

        if ($paymentStatus !== '') {
            $trendQuery->where('payment_status', $paymentStatus);
        }

        $trendRows = $trendQuery
            ->selectRaw('DATE(ordered_at) as day')
            ->selectRaw($salesAmountSql.' as sales')
            ->selectRaw('COUNT(*) as orders_count')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $salesTrend = [];
        $maxTrendSales = 0.0;

        if ($trendGroup === 'day') {
            $cursor = $trendStart->copy()->startOfDay();
            $endCursor = $trendEnd->copy()->startOfDay();
            while ($cursor <= $endCursor) {
                $dayKey = $cursor->format('Y-m-d');
                $row = $trendRows->get($dayKey);
                $sales = (float) ($row->sales ?? 0);

                $salesTrend[] = [
                    'date' => $dayKey,
                    'label' => $cursor->format('M d'),
                    'sales' => $sales,
                    'orders_count' => (int) ($row->orders_count ?? 0),
                ];
                $maxTrendSales = max($maxTrendSales, $sales);
                $cursor->addDay();
            }
        } else {
            $periodCursor = $trendGroup === 'week'
                ? $trendStart->copy()->startOfWeek()
                : $trendStart->copy()->startOfMonth();
            $periodEnd = $trendEnd->copy()->startOfDay();

            while ($periodCursor <= $periodEnd) {
                $segmentStart = $periodCursor->copy()->startOfDay();
                $segmentEnd = $trendGroup === 'week'
                    ? $periodCursor->copy()->endOfWeek()->startOfDay()
                    : $periodCursor->copy()->endOfMonth()->startOfDay();

                $effectiveStart = $segmentStart->copy()->greaterThan($trendStart->copy()->startOfDay())
                    ? $segmentStart->copy()
                    : $trendStart->copy()->startOfDay();
                $effectiveEnd = $segmentEnd->copy()->lessThan($periodEnd)
                    ? $segmentEnd->copy()
                    : $periodEnd->copy();

                $bucketSales = 0.0;
                $bucketOrders = 0;
                $dayCursor = $effectiveStart->copy();
                while ($dayCursor <= $effectiveEnd) {
                    $row = $trendRows->get($dayCursor->format('Y-m-d'));
                    $bucketSales += (float) ($row->sales ?? 0);
                    $bucketOrders += (int) ($row->orders_count ?? 0);
                    $dayCursor->addDay();
                }

                $label = $trendGroup === 'week'
                    ? 'Wk '.$segmentStart->format('M d')
                    : $segmentStart->format('M Y');

                $salesTrend[] = [
                    'date' => $effectiveStart->format('Y-m-d'),
                    'label' => $label,
                    'sales' => $bucketSales,
                    'orders_count' => $bucketOrders,
                ];
                $maxTrendSales = max($maxTrendSales, $bucketSales);

                if ($trendGroup === 'week') {
                    $periodCursor->addWeek()->startOfWeek();
                } else {
                    $periodCursor->addMonth()->startOfMonth();
                }
            }
        }

        $salesByOutletQuery = Order::query()
            ->join('outlets', 'outlets.id', '=', 'orders.outlet_id')
            ->whereBetween('orders.ordered_at', [$dateFrom, $dateTo]);

        if ($scopeOutletIds->isNotEmpty()) {
            $salesByOutletQuery->whereIn('orders.outlet_id', $scopeOutletIds);
        } elseif (! $isOwnerAdmin) {
            $salesByOutletQuery->whereRaw('1=0');
        }

        if ($orderStatus !== '') {
            $salesByOutletQuery->where('orders.status', $orderStatus);
        }

        if ($paymentStatus !== '') {
            $salesByOutletQuery->where('orders.payment_status', $paymentStatus);
        }

        $salesByOutlet = $salesByOutletQuery
            ->groupBy('orders.outlet_id', 'outlets.name')
            ->orderByDesc(DB::raw('SUM(COALESCE(orders.subtotal_amount, 0) - COALESCE(orders.discount_amount, 0))'))
            ->get([
                'orders.outlet_id',
                'outlets.name as outlet_name',
                DB::raw('COALESCE(SUM(COALESCE(orders.subtotal_amount, 0) - COALESCE(orders.discount_amount, 0)), 0) as total_sales'),
                DB::raw('COUNT(*) as total_orders'),
            ]);

        $topProductsQuery = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->whereBetween('orders.ordered_at', [$dateFrom, $dateTo]);

        if ($scopeOutletIds->isNotEmpty()) {
            $topProductsQuery->whereIn('orders.outlet_id', $scopeOutletIds);
        } elseif (! $isOwnerAdmin) {
            $topProductsQuery->whereRaw('1=0');
        }

        if ($orderStatus !== '') {
            $topProductsQuery->where('orders.status', $orderStatus);
        }

        if ($paymentStatus !== '') {
            $topProductsQuery->where('orders.payment_status', $paymentStatus);
        }

        $topProducts = $topProductsQuery
            ->groupBy('order_items.product_id', 'products.name')
            ->orderByDesc(DB::raw('SUM(order_items.quantity)'))
            ->limit(10)
            ->get([
                'order_items.product_id',
                DB::raw("COALESCE(products.name, 'Custom Item') as product_name"),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_qty'),
                DB::raw('COALESCE(SUM(order_items.line_total), 0) as total_amount'),
            ]);

        $lowStockAlertsQuery = InventoryAlert::query()
            ->with(['product:id,name', 'location:id,name,outlet_id'])
            ->where('status', InventoryAlert::STATUS_OPEN)
            ->orderByRaw('(COALESCE(min_qty, 0) - COALESCE(current_qty, 0)) DESC')
            ->orderByDesc('updated_at');

        if ($scopeOutletIds->isNotEmpty()) {
            $lowStockAlertsQuery->whereHas('location', function (Builder $query) use ($scopeOutletIds): void {
                $query->whereIn('outlet_id', $scopeOutletIds);
            });
        }

        $lowStockAlerts = (clone $lowStockAlertsQuery)->limit(10)->get();
        $lowStockCount = (clone $lowStockAlertsQuery)->count();

        $overdueDeliveriesQuery = Order::query()
            ->with(['customer:id,name', 'outlet:id,name'])
            ->whereNotIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])
            ->where(function (Builder $query) use ($now): void {
                $query->whereNotNull('delivery_due_at')->where('delivery_due_at', '<', $now);
            });

        if ($scopeOutletIds->isNotEmpty()) {
            $overdueDeliveriesQuery->whereIn('outlet_id', $scopeOutletIds);
        } elseif (! $isOwnerAdmin) {
            $overdueDeliveriesQuery->whereRaw('1=0');
        }

        $overdueDeliveriesCount = (clone $overdueDeliveriesQuery)->count();
        $overdueDeliveries = (clone $overdueDeliveriesQuery)
            ->latest('delivery_due_at')
            ->limit(10)
            ->get(['id', 'order_number', 'customer_id', 'outlet_id', 'status', 'delivery_due_at']);

        $pendingTransactionsQuery = InventoryTransaction::query()
            ->with(['fromLocation:id,name,outlet_id', 'toLocation:id,name,outlet_id', 'creator:id,name'])
            ->where('status', InventoryTransaction::STATUS_PENDING);

        if ($scopeOutletIds->isNotEmpty()) {
            $pendingTransactionsQuery->where(function (Builder $query) use ($scopeOutletIds): void {
                $query->whereHas('fromLocation', function (Builder $from) use ($scopeOutletIds): void {
                    $from->whereIn('outlet_id', $scopeOutletIds);
                })->orWhereHas('toLocation', function (Builder $to) use ($scopeOutletIds): void {
                    $to->whereIn('outlet_id', $scopeOutletIds);
                });
            });
        }

        $pendingInventoryTransactionsCount = (clone $pendingTransactionsQuery)->count();
        $pendingInventoryTransactions = (clone $pendingTransactionsQuery)
            ->latest('trx_date')
            ->limit(10)
            ->get(['id', 'reference_type', 'status', 'trx_date', 'from_location_id', 'to_location_id', 'created_by']);

        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $rangeStart = $dateFrom->copy()->startOfDay();
        $rangeEnd = $dateTo->copy()->endOfDay();

        $outletContextId = $selectedOutletId > 0 ? $selectedOutletId : $currentOutletId;
        if ($outletContextId < 1 && $accessibleOutletIds->isNotEmpty()) {
            $outletContextId = (int) $accessibleOutletIds->first();
        }

        $outletSalesToday = 0.0;
        $outletRevenueToday = 0.0;
        $outletTotalPaidToday = 0.0;
        $outletCashCollected = 0.0;
        $outletOrdersToday = 0;
        $dueTodayCount = 0;
        $overdueOutletCount = 0;
        $outletPendingPayments = 0.0;
        $outletStockValue = 0.0;
        $todayDeliveries = collect();
        $recentOrders = collect();
        $lowStockItems = collect();

        if ($outletContextId > 0) {
            $outletBaseOrders = Order::query()
                ->where('outlet_id', $outletContextId)
                ->where('status', '!=', Order::STATUS_CANCELLED);

            if ($orderStatus !== '') {
                $outletBaseOrders->where('status', $orderStatus);
            }
            if ($paymentStatus !== '') {
                $outletBaseOrders->where('payment_status', $paymentStatus);
            }

            $outletSalesToday = (float) (clone $outletBaseOrders)
                ->whereBetween('ordered_at', [$rangeStart, $rangeEnd])
                ->selectRaw($salesAmountSql.' as total')
                ->value('total');

            $outletRevenueToday = (float) (clone $outletBaseOrders)
                ->whereBetween('ordered_at', [$rangeStart, $rangeEnd])
                ->sum('advance_payment_amount');

            $outletTotalPaidToday = (float) (clone $outletBaseOrders)
                ->whereBetween('ordered_at', [$rangeStart, $rangeEnd])
                ->where('payment_status', Order::PAYMENT_STATUS_PAID)
                ->sum('advance_payment_amount');

            $outletCashCollected = (float) (clone $outletBaseOrders)
                ->whereBetween('ordered_at', [$rangeStart, $rangeEnd])
                ->where('payment_method', Order::PAYMENT_METHOD_CASH)
                ->sum('advance_payment_amount');

            $outletOrdersToday = (int) (clone $outletBaseOrders)
                ->whereBetween('ordered_at', [$rangeStart, $rangeEnd])
                ->count();

            $dueTodayCount = (int) (clone $outletBaseOrders)
                ->whereBetween('delivery_due_at', [$rangeStart, $rangeEnd])
                ->whereNotIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])
                ->count();

            $overdueOutletCount = (int) (clone $outletBaseOrders)
                ->whereNotIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])
                ->whereNotNull('delivery_due_at')
                ->where('delivery_due_at', '<', $now)
                ->count();

            $outletPendingPayments = (float) (clone $outletBaseOrders)
                ->where('payment_status', '!=', Order::PAYMENT_STATUS_PAID)
                ->selectRaw(
                    'COALESCE(SUM(GREATEST(0, '.
                    '(COALESCE(subtotal_amount, 0) - COALESCE(discount_amount, 0) + COALESCE(tailoring_amount, 0) + CASE WHEN vat_enabled IS TRUE THEN COALESCE(vat_amount, 0) ELSE 0 END)'.
                    ' - COALESCE(advance_payment_amount, 0)'.
                    ')), 0) as total'
                )
                ->value('total');

            $outletStockValue = (float) InventoryStock::query()
                ->join('inventory_locations', 'inventory_locations.id', '=', 'inventory_stocks.location_id')
                ->where('inventory_locations.outlet_id', $outletContextId)
                ->selectRaw('COALESCE(SUM(COALESCE(inventory_stocks.on_hand_qty, 0) * COALESCE(inventory_stocks.unit_cost, 0)), 0) as stock_value')
                ->value('stock_value');

            $todayDeliveries = (clone $outletBaseOrders)
                ->with(['customer:id,name'])
                ->whereBetween('delivery_due_at', [$rangeStart, $rangeEnd])
                ->orderBy('delivery_due_at')
                ->limit(10)
                ->get(['id', 'order_number', 'customer_id', 'delivery_due_at', 'status']);

            $recentOrders = (clone $outletBaseOrders)
                ->with(['customer:id,name'])
                ->latest('ordered_at')
                ->limit(10)
                ->get(['id', 'order_number', 'customer_id', 'ordered_at', 'status', 'subtotal_amount', 'discount_amount']);

            $lowStockItems = InventoryAlert::query()
                ->with(['product:id,name', 'location:id,name'])
                ->where('status', InventoryAlert::STATUS_OPEN)
                ->whereHas('location', function (Builder $query) use ($outletContextId): void {
                    $query->where('outlet_id', $outletContextId);
                })
                ->orderByRaw('(COALESCE(min_qty, 0) - COALESCE(current_qty, 0)) DESC')
                ->limit(10)
                ->get();
        }

        $dailyCollectionScope = Order::query()
            ->whereBetween('ordered_at', [$todayStart, $todayEnd])
            ->where('status', '!=', Order::STATUS_CANCELLED);

        if ($outletContextId > 0) {
            $dailyCollectionScope->where('outlet_id', $outletContextId);
        } elseif ($scopeOutletIds->isNotEmpty()) {
            $dailyCollectionScope->whereIn('outlet_id', $scopeOutletIds);
        } elseif (! $isOwnerAdmin) {
            $dailyCollectionScope->whereRaw('1=0');
        }

        $dailyCashCollected = (float) (clone $dailyCollectionScope)
            ->where('payment_method', Order::PAYMENT_METHOD_CASH)
            ->sum('advance_payment_amount');

        $dailyQrCollected = (float) (clone $dailyCollectionScope)
            ->where('payment_method', Order::PAYMENT_METHOD_QR)
            ->sum('advance_payment_amount');

        $dailyPosCollected = (float) (clone $dailyCollectionScope)
            ->where('payment_method', Order::PAYMENT_METHOD_POS)
            ->sum('advance_payment_amount');

        $workerAssignedCount = 0;
        $workerDueToday = 0;
        $workerOverdue = 0;
        $workerCompletedThisWeek = 0;
        $workerQueue = collect();
        $workerRecentlyCompleted = collect();

        if ($isWorker) {
            $workerBase = OrderTask::query()
                ->with(['order:id,order_number,customer_id,outlet_id', 'order.customer:id,name'])
                ->where('worker_id', (int) $user->id)
                ->where('status', '!=', OrderTask::STATUS_PENDING);

            if ($accessibleOutletIds->isNotEmpty()) {
                $workerBase->whereHas('order', function (Builder $query) use ($accessibleOutletIds): void {
                    $query->whereIn('outlet_id', $accessibleOutletIds);
                });
            } elseif ($outletContextId > 0) {
                $workerBase->whereHas('order', function (Builder $query) use ($outletContextId): void {
                    $query->where('outlet_id', $outletContextId);
                });
            }

            $activeStatuses = [
                OrderTask::STATUS_ASSIGNED,
                OrderTask::STATUS_IN_PROGRESS,
            ];

            $workerAssignedCount = (int) (clone $workerBase)
                ->whereIn('status', $activeStatuses)
                ->count();

            $workerDueToday = (int) (clone $workerBase)
                ->whereIn('status', $activeStatuses)
                ->whereDate('worker_deadline_at', $todayStart->toDateString())
                ->count();

            $workerOverdue = (int) (clone $workerBase)
                ->whereIn('status', $activeStatuses)
                ->whereNotNull('worker_deadline_at')
                ->where('worker_deadline_at', '<', $now)
                ->count();

            $weekStart = $now->copy()->startOfWeek();
            $weekEnd = $now->copy()->endOfWeek();
            $workerCompletedThisWeek = (int) (clone $workerBase)
                ->where('status', OrderTask::STATUS_COMPLETED)
                ->whereBetween('completed_at', [$weekStart, $weekEnd])
                ->count();

            $workerQueue = (clone $workerBase)
                ->whereIn('status', $activeStatuses)
                ->orderBy('worker_deadline_at')
                ->limit(15)
                ->get(['id', 'order_id', 'task_number', 'task_title', 'worker_deadline_at', 'status']);

            $workerRecentlyCompleted = (clone $workerBase)
                ->where('status', OrderTask::STATUS_COMPLETED)
                ->orderByRaw('COALESCE(completed_at, updated_at) DESC')
                ->limit(10)
                ->get(['id', 'order_id', 'task_number', 'task_title', 'completed_at', 'status']);
        }

        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $newCustomersThisMonth = (int) Customer::query()
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();

        $topCustomersQuery = Order::query()
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->whereNotNull('orders.customer_id')
            ->whereBetween('orders.ordered_at', [$dateFrom, $dateTo]);

        if ($scopeOutletIds->isNotEmpty()) {
            $topCustomersQuery->whereIn('orders.outlet_id', $scopeOutletIds);
        } elseif (! $isOwnerAdmin) {
            $topCustomersQuery->whereRaw('1=0');
        }
        if ($orderStatus !== '') {
            $topCustomersQuery->where('orders.status', $orderStatus);
        }
        if ($paymentStatus !== '') {
            $topCustomersQuery->where('orders.payment_status', $paymentStatus);
        }

        $topCustomers = $topCustomersQuery
            ->groupBy('orders.customer_id', 'customers.name')
            ->orderByDesc(DB::raw('SUM(COALESCE(orders.subtotal_amount, 0) - COALESCE(orders.discount_amount, 0))'))
            ->limit(10)
            ->get([
                'orders.customer_id',
                'customers.name as customer_name',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('COALESCE(SUM(COALESCE(orders.subtotal_amount, 0) - COALESCE(orders.discount_amount, 0)), 0) as total_sales'),
            ]);

        $fastMovingFrom = $now->copy()->subDays(29)->startOfDay();
        $fastMovingQuery = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->whereBetween('orders.ordered_at', [$fastMovingFrom, $now]);

        if ($scopeOutletIds->isNotEmpty()) {
            $fastMovingQuery->whereIn('orders.outlet_id', $scopeOutletIds);
        } elseif (! $isOwnerAdmin) {
            $fastMovingQuery->whereRaw('1=0');
        }
        if ($orderStatus !== '') {
            $fastMovingQuery->where('orders.status', $orderStatus);
        }
        if ($paymentStatus !== '') {
            $fastMovingQuery->where('orders.payment_status', $paymentStatus);
        }

        $fastMovingItems = $fastMovingQuery
            ->groupBy('order_items.product_id', 'products.name')
            ->orderByDesc(DB::raw('SUM(order_items.quantity)'))
            ->limit(10)
            ->get([
                'order_items.product_id',
                DB::raw("COALESCE(products.name, 'Custom Item') as product_name"),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_qty'),
            ]);

        $deadStockCutoff = $now->copy()->subDays(60)->startOfDay();
        $recentMovementProductIdsQuery = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.ordered_at', [$deadStockCutoff, $now]);

        if ($scopeOutletIds->isNotEmpty()) {
            $recentMovementProductIdsQuery->whereIn('orders.outlet_id', $scopeOutletIds);
        } elseif (! $isOwnerAdmin) {
            $recentMovementProductIdsQuery->whereRaw('1=0');
        }
        if ($orderStatus !== '') {
            $recentMovementProductIdsQuery->where('orders.status', $orderStatus);
        }
        if ($paymentStatus !== '') {
            $recentMovementProductIdsQuery->where('orders.payment_status', $paymentStatus);
        }

        $recentMovementProductIds = $recentMovementProductIdsQuery
            ->whereNotNull('order_items.product_id')
            ->distinct()
            ->pluck('order_items.product_id');

        $deadStockQuery = Product::query()
            ->leftJoin('inventory_stocks', 'inventory_stocks.product_id', '=', 'products.id')
            ->leftJoin('inventory_locations', 'inventory_locations.id', '=', 'inventory_stocks.location_id')
            ->select('products.id', 'products.name')
            ->selectRaw('COALESCE(SUM(inventory_stocks.on_hand_qty), 0) as on_hand_qty')
            ->whereNotIn('products.id', $recentMovementProductIds)
            ->groupBy('products.id', 'products.name')
            ->havingRaw('COALESCE(SUM(inventory_stocks.on_hand_qty), 0) > 0')
            ->orderByDesc('on_hand_qty')
            ->limit(10);

        if ($scopeOutletIds->isNotEmpty()) {
            $deadStockQuery->whereIn('inventory_locations.outlet_id', $scopeOutletIds);
        } elseif (! $isOwnerAdmin) {
            $deadStockQuery->whereRaw('1=0');
        }

        $deadStockItems = $deadStockQuery->get();

        $purchasesThisMonthQuery = VendorRawMaterialPurchase::query()
            ->whereBetween('purchased_at', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        if ($scopeOutletIds->isNotEmpty()) {
            $purchasesThisMonthQuery->whereHas('inventoryLocation', function (Builder $query) use ($scopeOutletIds): void {
                $query->whereIn('outlet_id', $scopeOutletIds);
            });
        } elseif (! $isOwnerAdmin) {
            $purchasesThisMonthQuery->whereRaw('1=0');
        }

        $purchasesThisMonth = (float) (clone $purchasesThisMonthQuery)->sum('total_amount');

        $topVendorsQuery = VendorRawMaterialPurchase::query()
            ->join('vendors', 'vendors.id', '=', 'vendor_raw_material_purchases.vendor_id')
            ->whereBetween('vendor_raw_material_purchases.purchased_at', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        if ($scopeOutletIds->isNotEmpty()) {
            $topVendorsQuery->whereIn(
                'vendor_raw_material_purchases.inventory_location_id',
                InventoryLocation::query()->whereIn('outlet_id', $scopeOutletIds)->pluck('id')
            );
        } elseif (! $isOwnerAdmin) {
            $topVendorsQuery->whereRaw('1=0');
        }

        $topVendors = $topVendorsQuery
            ->groupBy('vendor_raw_material_purchases.vendor_id', 'vendors.name')
            ->orderByDesc(DB::raw('SUM(COALESCE(vendor_raw_material_purchases.total_amount, 0))'))
            ->limit(10)
            ->get([
                'vendor_raw_material_purchases.vendor_id',
                'vendors.name as vendor_name',
                DB::raw('COALESCE(SUM(COALESCE(vendor_raw_material_purchases.total_amount, 0)), 0) as purchase_amount'),
            ]);

        $availableOutlets = $user->is_super_admin
            ? Outlet::query()->orderBy('name')->get(['id', 'name', 'code'])
            : Outlet::query()->whereIn('id', $accessibleOutletIds)->orderBy('name')->get(['id', 'name', 'code']);

        $salesTrendChartData = collect($salesTrend)
            ->map(fn (array $point): array => [
                'label' => (string) ($point['label'] ?? ''),
                'sales' => (float) ($point['sales'] ?? 0),
                'orders_count' => (int) ($point['orders_count'] ?? 0),
            ])
            ->values();
        $outletSalesChartData = $salesByOutlet
            ->map(fn ($row): array => [
                'label' => (string) ($row->outlet_name ?? ''),
                'sales' => (float) ($row->total_sales ?? 0),
                'orders_count' => (int) ($row->total_orders ?? 0),
            ])
            ->values();

        return view('modules.dashboard.index', [
            'roleScope' => $roleScope,
            'range' => $range,
            'chartView' => $chartView,
            'outletChartView' => $outletChartView,
            'trendGroup' => $trendGroup,
            'trendMetric' => $trendMetric,
            'rangeLabel' => $rangeLabel,
            'dateFrom' => $dateFrom->toDateString(),
            'dateTo' => $dateTo->toDateString(),
            'selectedOutletId' => $selectedOutletId,
            'availableOutlets' => $availableOutlets,
            'orderStatus' => $orderStatus,
            'paymentStatus' => $paymentStatus,
            'orderStatuses' => Order::statusLabels(),
            'paymentStatuses' => [
                Order::PAYMENT_STATUS_UNPAID => 'Unpaid',
                Order::PAYMENT_STATUS_PARTIAL => 'Partial',
                Order::PAYMENT_STATUS_PAID => 'Paid',
            ],
            'kpis' => [
                'totalSales' => $totalSales,
                'ordersCount' => $ordersCount,
                'advanceCollected' => $advanceCollected,
                'totalPaid' => $totalPaid,
                'cashCollected' => $cashCollected,
                'pendingPayments' => $pendingPayments,
                'deliveredOrders' => $deliveredOrders,
                'inventoryValue' => $inventoryValue,
            ],
            'salesTrend' => $salesTrend,
            'salesTrendChartData' => $salesTrendChartData,
            'maxTrendSales' => $maxTrendSales,
            'salesByOutlet' => $salesByOutlet,
            'outletSalesChartData' => $outletSalesChartData,
            'topProducts' => $topProducts,
            'lowStockAlerts' => $lowStockAlerts,
            'lowStockCount' => $lowStockCount,
            'overdueDeliveriesCount' => $overdueDeliveriesCount,
            'overdueDeliveries' => $overdueDeliveries,
            'pendingInventoryTransactionsCount' => $pendingInventoryTransactionsCount,
            'pendingInventoryTransactions' => $pendingInventoryTransactions,
            'outletKpis' => [
                'outletSalesToday' => $outletSalesToday,
                'outletRevenueToday' => $outletRevenueToday,
                'outletTotalPaidToday' => $outletTotalPaidToday,
                'outletCashCollected' => $outletCashCollected,
                'outletOrdersToday' => $outletOrdersToday,
                'dueTodayCount' => $dueTodayCount,
                'overdueOutletCount' => $overdueOutletCount,
                'outletPendingPayments' => $outletPendingPayments,
                'outletStockValue' => $outletStockValue,
                'lowStockCount' => $lowStockCount,
            ],
            'todayDeliveries' => $todayDeliveries,
            'recentOrders' => $recentOrders,
            'lowStockItems' => $lowStockItems,
            'workerKpis' => [
                'assignedCount' => $workerAssignedCount,
                'dueToday' => $workerDueToday,
                'overdue' => $workerOverdue,
                'completedThisWeek' => $workerCompletedThisWeek,
            ],
            'workerQueue' => $workerQueue,
            'workerRecentlyCompleted' => $workerRecentlyCompleted,
            'workerTaskRouteWorkerId' => (int) $user->id,
            'dailyCollection' => [
                'cash' => $dailyCashCollected,
                'qr' => $dailyQrCollected,
                'pos' => $dailyPosCollected,
            ],
            'smartWidgets' => [
                'newCustomersThisMonth' => $newCustomersThisMonth,
                'topCustomers' => $topCustomers,
                'fastMovingItems' => $fastMovingItems,
                'deadStockItems' => $deadStockItems,
                'purchasesThisMonth' => $purchasesThisMonth,
                'topVendors' => $topVendors,
            ],
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolveDateRange(Request $request, string $range, Carbon $now): array
    {
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();

        if ($request->filled('from_date') || $request->filled('to_date')) {
            return $this->resolveCustomRange($request, $todayStart, $todayEnd);
        }

        return match ($range) {
            '7d' => [$now->copy()->subDays(6)->startOfDay(), $todayEnd, 'Last 7 Days'],
            '30d' => [$now->copy()->subDays(29)->startOfDay(), $todayEnd, 'Last 30 Days'],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'This Month'],
            default => [$todayStart, $todayEnd, 'Today'],
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolveCustomRange(Request $request, Carbon $fallbackStart, Carbon $fallbackEnd): array
    {
        $from = trim((string) $request->query('from_date', ''));
        $to = trim((string) $request->query('to_date', ''));

        try {
            $fromDate = $from !== '' ? Carbon::parse($from)->startOfDay() : $fallbackStart;
            $toDate = $to !== '' ? Carbon::parse($to)->endOfDay() : $fallbackEnd;
        } catch (\Throwable) {
            return [$fallbackStart, $fallbackEnd, 'Today'];
        }

        if ($fromDate->greaterThan($toDate)) {
            [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
        }

        return [$fromDate, $toDate, 'Custom Range'];
    }
}
