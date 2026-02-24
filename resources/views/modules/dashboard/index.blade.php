@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $formatMoney = fn ($value) => number_format((float) $value, 2);
    $formatQty = fn ($value) => number_format((float) $value, 2);
@endphp

<div class="page-header dashboard-page-header">
    <div class="page-title">
        <h1 class="text-dark">
            @if ($roleScope === 'owner_admin')
                Owner / Admin Dashboard
            @elseif ($roleScope === 'outlet_manager')
                Outlet Manager Dashboard
            @else
                Worker Dashboard
            @endif
        </h1>
        <p>{{ $rangeLabel }} snapshot</p>
    </div>
    <div class="page-actions">
        @canany(['create-orders', 'manage-orders'])
            <a class="btn btn-primary" href="{{ route('order.create') }}"><i class="fas fa-plus"></i> Create Order</a>
        @endcanany
        @canany(['create-raw-material-purchases', 'manage-raw-material-purchases'])
            <a class="btn btn-secondary" href="{{ route('rawMaterialPurchase.create') }}"><i class="fas fa-cart-plus"></i> Add Purchase</a>
        @endcanany
        @can('manage-inventory')
            <a class="btn btn-outline-primary" href="{{ route('inventory.index') }}"><i class="fas fa-warehouse"></i> Stock Adjustment</a>
        @endcan
    </div>
</div>

@if ($roleScope !== 'worker')
    <div class="table-card dashboard-quick-links">
        @canany(['view-orders', 'manage-orders'])
            <a class="btn btn-sm btn-outline-primary" href="{{ route('order.index') }}">Sales Report</a>
        @endcanany
        @canany(['view-inventory', 'manage-inventory'])
            <a class="btn btn-sm btn-outline-primary" href="{{ route('inventory.index') }}">Inventory Report</a>
        @endcanany
        @canany(['view-raw-material-purchases', 'manage-raw-material-purchases'])
            <a class="btn btn-sm btn-outline-primary" href="{{ route('rawMaterialPurchase.index') }}">Purchase Report</a>
        @endcanany
    </div>
@endif

<div class="table-card dashboard-filter-card">
    <form method="GET" class="dashboard-filter-grid">
        <div class="outlet-form-group">
            <label for="range">Date Range</label>
            <select id="range" name="range" class="outlet-input">
                <option value="today" @selected($range === 'today')>Today</option>
                <option value="7d" @selected($range === '7d')>Last 7 Days</option>
                <option value="30d" @selected($range === '30d')>Last 30 Days</option>
                <option value="month" @selected($range === 'month')>This Month</option>
                <option value="custom" @selected($range === 'custom')>Custom</option>
            </select>
        </div>
        <div class="outlet-form-group">
            <label for="from_date">From</label>
            <input id="from_date" type="date" name="from_date" class="outlet-input" value="{{ request('from_date', $dateFrom) }}">
        </div>
        <div class="outlet-form-group">
            <label for="to_date">To</label>
            <input id="to_date" type="date" name="to_date" class="outlet-input" value="{{ request('to_date', $dateTo) }}">
        </div>
        @if ($roleScope === 'owner_admin')
            <div class="outlet-form-group">
                <label for="outlet_id">Outlet</label>
                <select id="outlet_id" name="outlet_id" class="outlet-input">
                    <option value="">All Outlets</option>
                    @foreach ($availableOutlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected((int) $selectedOutletId === (int) $outlet->id)>
                            {{ $outlet->name }} ({{ $outlet->code }})
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="outlet-form-group">
            <label for="order_status">Order Status</label>
            <select id="order_status" name="order_status" class="outlet-input">
                <option value="">All</option>
                @foreach ($orderStatuses as $key => $label)
                    <option value="{{ $key }}" @selected($orderStatus === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="outlet-form-group">
            <label for="payment_status">Payment Status</label>
            <select id="payment_status" name="payment_status" class="outlet-input">
                <option value="">All</option>
                @foreach ($paymentStatuses as $key => $label)
                    <option value="{{ $key }}" @selected($paymentStatus === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @if ($roleScope === 'owner_admin')
            <div class="outlet-form-group">
                <label for="trend_group">Trend Group</label>
                <select id="trend_group" name="trend_group" class="outlet-input">
                    <option value="day" @selected($trendGroup === 'day')>Daily</option>
                    <option value="week" @selected($trendGroup === 'week')>Weekly</option>
                    <option value="month" @selected($trendGroup === 'month')>Monthly</option>
                </select>
            </div>
            <div class="outlet-form-group">
                <label for="trend_metric">Trend Metric</label>
                <select id="trend_metric" name="trend_metric" class="outlet-input">
                    <option value="sales" @selected($trendMetric === 'sales')>Sales</option>
                    <option value="orders" @selected($trendMetric === 'orders')>Orders</option>
                </select>
            </div>
            <div class="outlet-form-group">
                <label for="chart_view">Sales Trend Chart</label>
                <select id="chart_view" name="chart_view" class="outlet-input">
                    <option value="bar" @selected($chartView === 'bar')>Bar</option>
                    <option value="line" @selected($chartView === 'line')>Line</option>
                </select>
            </div>
            <div class="outlet-form-group">
                <label for="outlet_chart_view">Sales by Outlet View</label>
                <select id="outlet_chart_view" name="outlet_chart_view" class="outlet-input">
                    <option value="table" @selected($outletChartView === 'table')>Table</option>
                    <option value="bars" @selected($outletChartView === 'bars')>Bars</option>
                </select>
            </div>
        @endif
        <div class="dashboard-filter-actions">
            <button type="submit" class="btn btn-primary">Apply</button>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>
</div>

@if ($roleScope === 'owner_admin')
    <div class="dashboard-grid dashboard-kpi-grid">
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Total Sales</div><div class="dashboard-kpi__value">Rs {{ $formatMoney($kpis['totalSales']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Orders Count</div><div class="dashboard-kpi__value">{{ number_format($kpis['ordersCount']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Advance Collected</div><div class="dashboard-kpi__value">Rs {{ $formatMoney($kpis['advanceCollected']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Pending Payments</div><div class="dashboard-kpi__value">{{ number_format($kpis['pendingPayments']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Delivered Orders</div><div class="dashboard-kpi__value">{{ number_format($kpis['deliveredOrders']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Inventory Value</div><div class="dashboard-kpi__value">Rs {{ $formatMoney($kpis['inventoryValue']) }}</div></div>
    </div>

    <div class="dashboard-grid dashboard-two-col">
        <div class="table-card">
            <div class="table-header"><div class="table-title">Sales Trend ({{ count($salesTrend) }} points)</div></div>
            @if (count($salesTrend) > 0)
                <div class="dashboard-chart-wrap">
                    <canvas id="salesTrendChart" aria-label="Sales trend chart"></canvas>
                </div>
            @else
                <div class="empty">No sales trend data.</div>
            @endif
        </div>

        <div class="table-card">
            <div class="table-header"><div class="table-title">Sales by Outlet</div></div>
            @if ($outletChartView === 'bars')
                @if (count($salesByOutlet) > 0)
                    <div class="dashboard-chart-wrap">
                        <canvas id="salesByOutletChart" aria-label="Sales by outlet chart"></canvas>
                    </div>
                @else
                    <div class="empty">No outlet sales found.</div>
                @endif
            @else
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Outlet</th><th>Orders</th><th>Sales</th></tr></thead>
                        <tbody>
                        @forelse ($salesByOutlet as $row)
                            <tr><td>{{ $row->outlet_name }}</td><td>{{ number_format((int) $row->total_orders) }}</td><td>Rs {{ $formatMoney($row->total_sales) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="empty">No outlet sales found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="dashboard-grid dashboard-two-col">
        <div class="table-card">
            <div class="table-header"><div class="table-title">Top 10 Products</div></div>
            <div class="table-container">
                <table class="table">
                    <thead><tr><th>Product</th><th>Qty</th><th>Amount</th></tr></thead>
                    <tbody>
                    @forelse ($topProducts as $row)
                        <tr><td>{{ $row->product_name }}</td><td>{{ $formatQty($row->total_qty) }}</td><td>Rs {{ $formatMoney($row->total_amount) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="empty">No product sales found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header"><div class="table-title">Alerts / Attention</div></div>
            <div class="dashboard-alert-list">
                <div><strong>Low Stock Alerts:</strong> {{ number_format($lowStockCount) }}</div>
                <div><strong>Overdue Deliveries:</strong> {{ number_format($overdueDeliveriesCount) }}</div>
                <div><strong>Pending Inventory Transactions:</strong> {{ number_format($pendingInventoryTransactionsCount) }}</div>
            </div>
            <div class="table-container" style="margin-top: 12px;">
                <table class="table">
                    <thead><tr><th>Order</th><th>Customer</th><th>Due</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($overdueDeliveries as $row)
                        <tr>
                            <td>{{ $row->order_number }}</td>
                            <td>{{ $row->customer?->name ?: '-' }}</td>
                            <td>{{ $row->delivery_due_at?->format('M d, Y h:i A') ?: '-' }}</td>
                            <td>{{ \App\Models\Order::statusLabel((string) $row->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">No overdue deliveries.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

@if ($roleScope === 'outlet_manager')
    <div class="dashboard-grid dashboard-kpi-grid">
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Outlet Sales Today</div><div class="dashboard-kpi__value">Rs {{ $formatMoney($outletKpis['outletSalesToday']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Orders Today</div><div class="dashboard-kpi__value">{{ number_format($outletKpis['outletOrdersToday']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Due Today</div><div class="dashboard-kpi__value">{{ number_format($outletKpis['dueTodayCount']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Overdue Orders</div><div class="dashboard-kpi__value">{{ number_format($outletKpis['overdueOutletCount']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Pending Payments</div><div class="dashboard-kpi__value">{{ number_format($outletKpis['outletPendingPayments']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Stock Value</div><div class="dashboard-kpi__value">Rs {{ $formatMoney($outletKpis['outletStockValue']) }}</div></div>
    </div>

    <div class="dashboard-grid dashboard-two-col">
        <div class="table-card">
            <div class="table-header"><div class="table-title">Today’s Deliveries</div></div>
            <div class="table-container">
                <table class="table">
                    <thead><tr><th>Order</th><th>Customer</th><th>Due</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($todayDeliveries as $row)
                        <tr>
                            <td>{{ $row->order_number }}</td>
                            <td>{{ $row->customer?->name ?: '-' }}</td>
                            <td>{{ $row->delivery_due_at?->format('h:i A') ?: '-' }}</td>
                            <td>{{ \App\Models\Order::statusLabel((string) $row->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">No deliveries due today.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="table-card">
            <div class="table-header"><div class="table-title">Recent Orders</div></div>
            <div class="table-container">
                <table class="table">
                    <thead><tr><th>Order</th><th>Customer</th><th>Date</th><th>Amount</th></tr></thead>
                    <tbody>
                    @forelse ($recentOrders as $row)
                        <tr>
                            <td>{{ $row->order_number }}</td>
                            <td>{{ $row->customer?->name ?: '-' }}</td>
                            <td>{{ $row->ordered_at?->format('M d, h:i A') ?: '-' }}</td>
                            <td>Rs {{ $formatMoney(((float) $row->subtotal_amount) - ((float) $row->discount_amount)) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">No recent orders found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-header"><div class="table-title">Low Stock Items (Top 10)</div></div>
        <div class="table-container">
            <table class="table">
                <thead><tr><th>Product</th><th>Location</th><th>Current Qty</th><th>Min Qty</th></tr></thead>
                <tbody>
                @forelse ($lowStockItems as $row)
                    <tr>
                        <td>{{ $row->product?->name ?: '-' }}</td>
                        <td>{{ $row->location?->name ?: '-' }}</td>
                        <td>{{ $formatQty($row->current_qty) }}</td>
                        <td>{{ $formatQty($row->min_qty) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">No low stock items.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

@if ($roleScope === 'worker')
    <div class="dashboard-grid dashboard-kpi-grid dashboard-kpi-grid-worker">
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Assigned Orders</div><div class="dashboard-kpi__value">{{ number_format($workerKpis['assignedCount']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Due Today</div><div class="dashboard-kpi__value">{{ number_format($workerKpis['dueToday']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Overdue</div><div class="dashboard-kpi__value">{{ number_format($workerKpis['overdue']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Completed This Week</div><div class="dashboard-kpi__value">{{ number_format($workerKpis['completedThisWeek']) }}</div></div>
    </div>

    <div class="dashboard-grid dashboard-two-col">
        <div class="table-card">
            <div class="table-header"><div class="table-title">My Current Work Queue</div></div>
            <div class="table-container">
                <table class="table">
                    <thead><tr><th>Order</th><th>Customer</th><th>Due Date</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($workerQueue as $row)
                        <tr>
                            <td>{{ $row->order_number }}</td>
                            <td>{{ $row->customer?->name ?: '-' }}</td>
                            <td>{{ $row->worker_deadline_at?->format('M d, h:i A') ?: '-' }}</td>
                            <td>{{ \App\Models\Order::statusLabel((string) $row->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">No active assignments.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header"><div class="table-title">Recently Completed</div></div>
            <div class="table-container">
                <table class="table">
                    <thead><tr><th>Order</th><th>Customer</th><th>Completed/Delivered</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($workerRecentlyCompleted as $row)
                        <tr>
                            <td>{{ $row->order_number }}</td>
                            <td>{{ $row->customer?->name ?: '-' }}</td>
                            <td>{{ ($row->delivered_at ?: $row->completed_at)?->format('M d, h:i A') ?: '-' }}</td>
                            <td>{{ \App\Models\Order::statusLabel((string) $row->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">No recently completed orders.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

@if ($roleScope !== 'worker')
    <div class="dashboard-grid dashboard-two-col">
        <div class="table-card">
            <div class="table-header"><div class="table-title">Customer Insights</div></div>
            <div class="dashboard-alert-list"><strong>New Customers This Month:</strong> {{ number_format($smartWidgets['newCustomersThisMonth']) }}</div>
            <div class="table-container" style="margin-top: 12px;">
                <table class="table">
                    <thead><tr><th>Customer</th><th>Orders</th><th>Sales</th></tr></thead>
                    <tbody>
                    @forelse ($smartWidgets['topCustomers'] as $row)
                        <tr><td>{{ $row->customer_name }}</td><td>{{ number_format((int) $row->total_orders) }}</td><td>Rs {{ $formatMoney($row->total_sales) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="empty">No customer insights data.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header"><div class="table-title">Inventory Insights</div></div>
            <div class="table-container">
                <table class="table">
                    <thead><tr><th>Fast Moving (30D)</th><th>Qty</th></tr></thead>
                    <tbody>
                    @forelse ($smartWidgets['fastMovingItems'] as $row)
                        <tr><td>{{ $row->product_name }}</td><td>{{ $formatQty($row->total_qty) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="empty">No fast moving items.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="table-container" style="margin-top:12px;">
                <table class="table">
                    <thead><tr><th>Dead Stock (60D)</th><th>On Hand</th></tr></thead>
                    <tbody>
                    @forelse ($smartWidgets['deadStockItems'] as $row)
                        <tr><td>{{ $row->name }}</td><td>{{ $formatQty($row->on_hand_qty) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="empty">No dead stock found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-header"><div class="table-title">Purchase Insights</div></div>
        <div class="dashboard-alert-list"><strong>Purchases This Month:</strong> Rs {{ $formatMoney($smartWidgets['purchasesThisMonth']) }}</div>
        <div class="table-container" style="margin-top:12px;">
            <table class="table">
                <thead><tr><th>Vendor</th><th>Purchase Amount</th></tr></thead>
                <tbody>
                @forelse ($smartWidgets['topVendors'] as $row)
                    <tr><td>{{ $row->vendor_name }}</td><td>Rs {{ $formatMoney($row->purchase_amount) }}</td></tr>
                @empty
                    <tr><td colspan="2" class="empty">No vendor purchase data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection

@section('page-specific-style')
<style>
    .dashboard-page-header {
        margin-bottom: 14px;
    }

    .dashboard-filter-card {
        margin-bottom: 14px;
        padding: 16px;
    }

    .dashboard-quick-links {
        margin-bottom: 14px;
        padding: 12px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .dashboard-filter-grid {
        display: grid;
        grid-template-columns: repeat(8, minmax(140px, 1fr));
        gap: 12px;
        align-items: end;
    }

    .dashboard-filter-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        padding-top: 6px;
    }

    .dashboard-grid {
        display: grid;
        gap: 14px;
        margin-bottom: 14px;
    }

    .dashboard-kpi-grid {
        grid-template-columns: repeat(6, minmax(150px, 1fr));
    }

    .dashboard-kpi-grid-worker {
        grid-template-columns: repeat(4, minmax(150px, 1fr));
    }

    .dashboard-two-col {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .dashboard-kpi {
        padding: 14px;
    }

    .dashboard-kpi__label {
        font-size: 0.84rem;
        color: #64748b;
        margin-bottom: 4px;
    }

    .dashboard-kpi__value {
        font-size: 1.2rem;
        color: #0f172a;
        font-weight: 700;
    }

    .dashboard-chart-wrap {
        position: relative;
        height: 280px;
    }

    .dashboard-chart-wrap canvas {
        width: 100% !important;
        height: 100% !important;
    }

    .dashboard-alert-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
        color: #334155;
    }

    @media (max-width: 1200px) {
        .dashboard-filter-grid {
            grid-template-columns: repeat(3, minmax(140px, 1fr));
        }

        .dashboard-kpi-grid {
            grid-template-columns: repeat(3, minmax(140px, 1fr));
        }
    }

    @media (max-width: 800px) {
        .dashboard-filter-grid,
        .dashboard-kpi-grid,
        .dashboard-kpi-grid-worker,
        .dashboard-two-col {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('page-specific-script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
<script>
    (() => {
        if (typeof Chart === 'undefined') {
            return;
        }

        const salesTrendData = @json($salesTrendChartData);
        const outletSalesData = @json($outletSalesChartData);
        const trendMetric = @json($trendMetric);
        const trendChartType = @json($chartView);

        const formatMoney = (value) => `Rs ${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        const formatCount = (value) => Number(value || 0).toLocaleString();
        const isSalesMetric = trendMetric === 'sales';

        const trendChartElement = document.getElementById('salesTrendChart');
        if (trendChartElement && salesTrendData.length > 0) {
            const trendValues = salesTrendData.map((point) => isSalesMetric ? Number(point.sales || 0) : Number(point.orders_count || 0));

            new Chart(trendChartElement, {
                type: trendChartType === 'line' ? 'line' : 'bar',
                data: {
                    labels: salesTrendData.map((point) => point.label),
                    datasets: [{
                        label: isSalesMetric ? 'Sales' : 'Orders',
                        data: trendValues,
                        borderColor: '#0f766e',
                        backgroundColor: trendChartType === 'line' ? 'rgba(15, 118, 110, 0.15)' : 'rgba(14, 165, 233, 0.75)',
                        pointRadius: trendChartType === 'line' ? 3 : 0,
                        pointHoverRadius: trendChartType === 'line' ? 4 : 0,
                        fill: trendChartType === 'line',
                        tension: 0.3,
                        borderWidth: 2
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => isSalesMetric
                                    ? `${context.dataset.label}: ${formatMoney(context.parsed.y)}`
                                    : `${context.dataset.label}: ${formatCount(context.parsed.y)}`
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => isSalesMetric ? `Rs ${Number(value).toLocaleString()}` : Number(value).toLocaleString()
                            }
                        }
                    }
                }
            });
        }

        const outletChartElement = document.getElementById('salesByOutletChart');
        if (outletChartElement && outletSalesData.length > 0) {
            new Chart(outletChartElement, {
                type: 'bar',
                data: {
                    labels: outletSalesData.map((point) => point.label),
                    datasets: [{
                        label: 'Sales',
                        data: outletSalesData.map((point) => Number(point.sales || 0)),
                        backgroundColor: 'rgba(59, 130, 246, 0.75)',
                        borderColor: '#1d4ed8',
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => `Sales: ${formatMoney(context.parsed.x)}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => `Rs ${Number(value).toLocaleString()}`
                            }
                        }
                    }
                }
            });
        }
    })();
</script>
@endsection
