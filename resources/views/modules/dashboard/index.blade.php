@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-specific-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
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
        grid-template-columns: repeat(6, minmax(140px, 1fr));
        gap: 12px;
        align-items: end;
    }

    .dashboard-filter-field--range {
        grid-column: span 2;
    }

    .dashboard-date-range-input {
        cursor: pointer;
        background: #fff;
        text-align: left;
    }

    .daterangepicker {
        font-family: 'Poppins', sans-serif;
    }

    .daterangepicker .ranges li.active {
        background-color: #0f766e;
        color: #fff;
    }

    .daterangepicker .calendar-table td.active,
    .daterangepicker .calendar-table td.active:hover {
        background-color: #2563eb;
        border-color: #2563eb;
        color: #fff;
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

    .dashboard-card-header {
        align-items: flex-start;
        gap: 12px;
        flex-wrap: wrap;
    }

    .dashboard-card-controls {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
        margin-left: auto;
    }

    .dashboard-control-group {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 4px 6px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #f8fafc;
    }

    .dashboard-control-label {
        font-size: 0.72rem;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        white-space: nowrap;
    }

    .dashboard-card-tabs {
        display: inline-flex;
        gap: 6px;
        align-items: center;
        flex-wrap: wrap;
    }

    .dashboard-card-tab {
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #334155;
        border-radius: 999px;
        padding: 5px 11px;
        font-size: 0.78rem;
        font-weight: 600;
        line-height: 1;
        cursor: pointer;
        transition: all .15s ease;
    }

    .dashboard-card-tab:hover {
        border-color: #94a3b8;
        color: #0f172a;
    }

    .dashboard-card-tab.is-active {
        background: #0f766e;
        border-color: #0f766e;
        color: #fff;
        box-shadow: 0 1px 2px rgba(15, 118, 110, 0.25);
    }

    @media (max-width: 1200px) {
        .dashboard-filter-grid {
            grid-template-columns: repeat(3, minmax(140px, 1fr));
        }

        .dashboard-filter-field--range {
            grid-column: span 1;
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

        .dashboard-card-controls {
            width: 100%;
            margin-left: 0;
            justify-content: flex-start;
        }
    }
</style>
@endsection

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
        @if ($roleScope === 'worker')
            <a class="btn btn-secondary" href="{{ route('worker.tasks', ['worker' => $workerTaskRouteWorkerId]) }}">
                <i class="fas fa-list-check"></i> My Tasks &amp; Report
            </a>
        @endif
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
        <div class="outlet-form-group dashboard-filter-field--range">
            <label for="dashboard_date_range">Date Range</label>
            <input
                id="dashboard_date_range"
                type="text"
                class="outlet-input dashboard-date-range-input"
                value="{{ request('from_date') && request('to_date') ? request('from_date') . ' - ' . request('to_date') : ($dateFrom . ' - ' . $dateTo) }}"
                placeholder="Select date range"
                autocomplete="off"
            >
            <input id="from_date" type="hidden" name="from_date" value="{{ request('from_date', $dateFrom) }}">
            <input id="to_date" type="hidden" name="to_date" value="{{ request('to_date', $dateTo) }}">
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
            <input type="hidden" id="trend_group" name="trend_group" value="{{ $trendGroup }}">
            <input type="hidden" id="trend_metric" name="trend_metric" value="{{ $trendMetric }}">
            <input type="hidden" id="chart_view" name="chart_view" value="{{ $chartView }}">
            <input type="hidden" id="outlet_chart_view" name="outlet_chart_view" value="{{ $outletChartView }}">
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
            <div class="table-header dashboard-card-header">
                <div class="table-title">Sales Trend ({{ count($salesTrend) }} points)</div>
                <div class="dashboard-card-controls">
                    <div class="dashboard-control-group" role="tablist" aria-label="Sales trend group tabs">
                        <span class="dashboard-control-label">Range</span>
                        <div class="dashboard-card-tabs">
                            <button type="button" class="dashboard-card-tab js-trend-group-tab @if($trendGroup === 'day') is-active @endif" data-trend-group="day" role="tab" aria-selected="{{ $trendGroup === 'day' ? 'true' : 'false' }}">Daily</button>
                            <button type="button" class="dashboard-card-tab js-trend-group-tab @if($trendGroup === 'week') is-active @endif" data-trend-group="week" role="tab" aria-selected="{{ $trendGroup === 'week' ? 'true' : 'false' }}">Weekly</button>
                            <button type="button" class="dashboard-card-tab js-trend-group-tab @if($trendGroup === 'month') is-active @endif" data-trend-group="month" role="tab" aria-selected="{{ $trendGroup === 'month' ? 'true' : 'false' }}">Monthly</button>
                        </div>
                    </div>
                    <div class="dashboard-control-group" role="tablist" aria-label="Sales trend metric tabs">
                        <span class="dashboard-control-label">Metric</span>
                        <div class="dashboard-card-tabs">
                            <button type="button" class="dashboard-card-tab js-trend-metric-tab @if($trendMetric === 'sales') is-active @endif" data-trend-metric="sales" role="tab" aria-selected="{{ $trendMetric === 'sales' ? 'true' : 'false' }}">Sales</button>
                            <button type="button" class="dashboard-card-tab js-trend-metric-tab @if($trendMetric === 'orders') is-active @endif" data-trend-metric="orders" role="tab" aria-selected="{{ $trendMetric === 'orders' ? 'true' : 'false' }}">Orders</button>
                        </div>
                    </div>
                    <div class="dashboard-control-group" role="tablist" aria-label="Sales trend chart tabs">
                        <span class="dashboard-control-label">Chart</span>
                        <div class="dashboard-card-tabs">
                            <button type="button" class="dashboard-card-tab js-trend-tab @if($chartView === 'bar') is-active @endif" data-chart-view="bar" role="tab" aria-selected="{{ $chartView === 'bar' ? 'true' : 'false' }}">Bar</button>
                            <button type="button" class="dashboard-card-tab js-trend-tab @if($chartView === 'line') is-active @endif" data-chart-view="line" role="tab" aria-selected="{{ $chartView === 'line' ? 'true' : 'false' }}">Line</button>
                        </div>
                    </div>
                </div>
            </div>
            @if (count($salesTrend) > 0)
                <div class="dashboard-chart-wrap">
                    <canvas id="salesTrendChart" aria-label="Sales trend chart"></canvas>
                </div>
            @else
                <div class="empty">No sales trend data.</div>
            @endif
        </div>

        <div class="table-card">
            <div class="table-header dashboard-card-header">
                <div class="table-title">Sales by Outlet</div>
                <div class="dashboard-card-controls">
                    <div class="dashboard-control-group" role="tablist" aria-label="Sales by outlet view tabs">
                        <span class="dashboard-control-label">View</span>
                        <div class="dashboard-card-tabs">
                            <button type="button" class="dashboard-card-tab js-outlet-tab @if($outletChartView === 'table') is-active @endif" data-outlet-view="table" role="tab" aria-selected="{{ $outletChartView === 'table' ? 'true' : 'false' }}">Table</button>
                            <button type="button" class="dashboard-card-tab js-outlet-tab @if($outletChartView === 'bars') is-active @endif" data-outlet-view="bars" role="tab" aria-selected="{{ $outletChartView === 'bars' ? 'true' : 'false' }}">Bars</button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="outletBarsPanel" @if($outletChartView !== 'bars') hidden @endif>
                @if (count($salesByOutlet) > 0)
                    <div class="dashboard-chart-wrap">
                        <canvas id="salesByOutletChart" aria-label="Sales by outlet chart"></canvas>
                    </div>
                @else
                    <div class="empty">No outlet sales found.</div>
                @endif
            </div>
            <div id="outletTablePanel" @if($outletChartView !== 'table') hidden @endif>
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
            </div>
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
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Outlet Sales ({{ $rangeLabel }})</div><div class="dashboard-kpi__value">Rs {{ $formatMoney($outletKpis['outletSalesToday']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Orders ({{ $rangeLabel }})</div><div class="dashboard-kpi__value">{{ number_format($outletKpis['outletOrdersToday']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Due In Range</div><div class="dashboard-kpi__value">{{ number_format($outletKpis['dueTodayCount']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Overdue Orders</div><div class="dashboard-kpi__value">{{ number_format($outletKpis['overdueOutletCount']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Pending Payments</div><div class="dashboard-kpi__value">{{ number_format($outletKpis['outletPendingPayments']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Stock Value</div><div class="dashboard-kpi__value">Rs {{ $formatMoney($outletKpis['outletStockValue']) }}</div></div>
    </div>

    <div class="dashboard-grid dashboard-two-col">
        <div class="table-card">
            <div class="table-header"><div class="table-title">Due Deliveries ({{ $rangeLabel }})</div></div>
            <div class="table-container">
                <table class="table">
                    <thead><tr><th>Order</th><th>Customer</th><th>Due</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($todayDeliveries as $row)
                        <tr>
                            <td>{{ $row->order_number }}</td>
                            <td>{{ $row->customer?->name ?: '-' }}</td>
                            <td>{{ $row->delivery_due_at?->format('M d, h:i A') ?: '-' }}</td>
                            <td>{{ \App\Models\Order::statusLabel((string) $row->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">No deliveries due in selected range.</td></tr>
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
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Assigned Tasks</div><div class="dashboard-kpi__value">{{ number_format($workerKpis['assignedCount']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Due Today</div><div class="dashboard-kpi__value">{{ number_format($workerKpis['dueToday']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Overdue</div><div class="dashboard-kpi__value">{{ number_format($workerKpis['overdue']) }}</div></div>
        <div class="table-card dashboard-kpi"><div class="dashboard-kpi__label">Completed This Week</div><div class="dashboard-kpi__value">{{ number_format($workerKpis['completedThisWeek']) }}</div></div>
    </div>

    <div class="dashboard-grid dashboard-two-col">
        <div class="table-card">
            <div class="table-header"><div class="table-title">My Current Work Queue</div></div>
            <div class="table-container">
                <table class="table">
                    <thead><tr><th>Task</th><th>Order</th><th>Customer</th><th>Due Date</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($workerQueue as $row)
                        <tr>
                            <td>{{ $row->task_title ?: ($row->task_number ?: '-') }}</td>
                            <td>{{ $row->order?->order_number ?: '-' }}</td>
                            <td>{{ $row->order?->customer?->name ?: '-' }}</td>
                            <td>{{ $row->worker_deadline_at?->format('M d, h:i A') ?: '-' }}</td>
                            <td>{{ \App\Models\OrderTask::statusLabels()[(string) $row->status] ?? ucfirst((string) $row->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No active assignments.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header"><div class="table-title">Recently Completed</div></div>
            <div class="table-container">
                <table class="table">
                    <thead><tr><th>Task</th><th>Order</th><th>Customer</th><th>Completed</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($workerRecentlyCompleted as $row)
                        <tr>
                            <td>{{ $row->task_title ?: ($row->task_number ?: '-') }}</td>
                            <td>{{ $row->order?->order_number ?: '-' }}</td>
                            <td>{{ $row->order?->customer?->name ?: '-' }}</td>
                            <td>{{ $row->completed_at?->format('M d, h:i A') ?: '-' }}</td>
                            <td>{{ \App\Models\OrderTask::statusLabels()[(string) $row->status] ?? ucfirst((string) $row->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No recently completed tasks.</td></tr>
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

@section('page-specific-script')
<script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/min/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
<script>
    (() => {
        const salesTrendData = @json($salesTrendChartData);
        const outletSalesData = @json($outletSalesChartData);
        let trendMetric = @json($trendMetric);
        let trendChartType = @json($chartView);
        let outletViewType = @json($outletChartView);

        const formatMoney = (value) => `Rs ${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        const formatCount = (value) => Number(value || 0).toLocaleString();
        const trendGroupInput = document.getElementById('trend_group');
        const trendMetricInput = document.getElementById('trend_metric');
        const filterForm = document.querySelector('.dashboard-filter-card form');
        const dateRangeInput = document.getElementById('dashboard_date_range');
        const fromDateInput = document.getElementById('from_date');
        const toDateInput = document.getElementById('to_date');

        const chartViewInput = document.getElementById('chart_view');
        const outletViewInput = document.getElementById('outlet_chart_view');
        const trendGroupTabs = Array.from(document.querySelectorAll('.js-trend-group-tab'));
        const trendMetricTabs = Array.from(document.querySelectorAll('.js-trend-metric-tab'));
        const trendTabs = Array.from(document.querySelectorAll('.js-trend-tab'));
        const outletTabs = Array.from(document.querySelectorAll('.js-outlet-tab'));
        const outletBarsPanel = document.getElementById('outletBarsPanel');
        const outletTablePanel = document.getElementById('outletTablePanel');

        let trendChartInstance = null;
        let outletChartInstance = null;

        if (dateRangeInput && fromDateInput && toDateInput && window.jQuery && window.jQuery.fn && window.jQuery.fn.daterangepicker) {
            const options = {
                autoUpdateInput: false,
                alwaysShowCalendars: true,
                opens: 'left',
                linkedCalendars: false,
                showCustomRangeLabel: true,
                ranges: {
                    Today: [window.moment(), window.moment()],
                    Yesterday: [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                    'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                    'Last 30 Days': [window.moment().subtract(29, 'days'), window.moment()],
                    'This Month': [window.moment().startOf('month'), window.moment().endOf('month')],
                    'Last Month': [
                        window.moment().subtract(1, 'month').startOf('month'),
                        window.moment().subtract(1, 'month').endOf('month'),
                    ],
                },
                locale: {
                    cancelLabel: 'Clear',
                    format: 'YYYY-MM-DD',
                    customRangeLabel: 'Custom Range',
                },
            };

            if (fromDateInput.value && toDateInput.value) {
                options.startDate = fromDateInput.value;
                options.endDate = toDateInput.value;
                dateRangeInput.value = `${fromDateInput.value} - ${toDateInput.value}`;
            }

            window.jQuery(dateRangeInput).daterangepicker(options);

            window.jQuery(dateRangeInput).on('apply.daterangepicker', function (_event, picker) {
                const start = picker.startDate.format('YYYY-MM-DD');
                const end = picker.endDate.format('YYYY-MM-DD');

                fromDateInput.value = start;
                toDateInput.value = end;
                dateRangeInput.value = `${start} - ${end}`;
            });

            window.jQuery(dateRangeInput).on('cancel.daterangepicker', function () {
                fromDateInput.value = '';
                toDateInput.value = '';
                dateRangeInput.value = '';
            });
        }

        const trendChartElement = document.getElementById('salesTrendChart');
        if (trendChartElement && salesTrendData.length > 0 && typeof Chart !== 'undefined') {
            const renderTrendChart = (type) => {
                const isSalesMetric = trendMetric === 'sales';
                const trendValues = salesTrendData.map((point) => isSalesMetric ? Number(point.sales || 0) : Number(point.orders_count || 0));
                if (trendChartInstance) {
                    trendChartInstance.destroy();
                }
                trendChartInstance = new Chart(trendChartElement, {
                    type: type === 'line' ? 'line' : 'bar',
                    data: {
                        labels: salesTrendData.map((point) => point.label),
                        datasets: [{
                            label: isSalesMetric ? 'Sales' : 'Orders',
                            data: trendValues,
                            borderColor: '#0f766e',
                            backgroundColor: type === 'line' ? 'rgba(15, 118, 110, 0.15)' : 'rgba(14, 165, 233, 0.75)',
                            pointRadius: type === 'line' ? 3 : 0,
                            pointHoverRadius: type === 'line' ? 4 : 0,
                            fill: type === 'line',
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
            };

            renderTrendChart(trendChartType);

            trendGroupTabs.forEach((button) => {
                button.addEventListener('click', () => {
                    const nextGroup = String(button.dataset.trendGroup || '');
                    if (!['day', 'week', 'month'].includes(nextGroup)) {
                        return;
                    }
                    if (trendGroupInput) {
                        trendGroupInput.value = nextGroup;
                    }
                    if (filterForm) {
                        filterForm.submit();
                    }
                });
            });

            trendMetricTabs.forEach((button) => {
                button.addEventListener('click', () => {
                    const nextMetric = String(button.dataset.trendMetric || '');
                    if (!['sales', 'orders'].includes(nextMetric)) {
                        return;
                    }
                    trendMetric = nextMetric;
                    if (trendMetricInput) {
                        trendMetricInput.value = trendMetric;
                    }
                    trendMetricTabs.forEach((tab) => {
                        const isActive = tab === button;
                        tab.classList.toggle('is-active', isActive);
                        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    });
                    renderTrendChart(trendChartType);
                });
            });

            trendTabs.forEach((button) => {
                button.addEventListener('click', () => {
                    const nextType = String(button.dataset.chartView || 'bar');
                    if (nextType !== 'bar' && nextType !== 'line') {
                        return;
                    }
                    trendChartType = nextType;
                    if (chartViewInput) {
                        chartViewInput.value = trendChartType;
                    }
                    trendTabs.forEach((tab) => {
                        const isActive = tab === button;
                        tab.classList.toggle('is-active', isActive);
                        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    });
                    renderTrendChart(trendChartType);
                });
            });
        }

        const outletChartElement = document.getElementById('salesByOutletChart');
        const ensureOutletChart = () => {
            if (!outletChartElement || outletSalesData.length < 1 || typeof Chart === 'undefined' || outletChartInstance) {
                return;
            }
            outletChartInstance = new Chart(outletChartElement, {
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
        };

        const applyOutletView = (view) => {
            outletViewType = view === 'bars' ? 'bars' : 'table';
            if (outletViewInput) {
                outletViewInput.value = outletViewType;
            }
            if (outletBarsPanel) {
                outletBarsPanel.hidden = outletViewType !== 'bars';
            }
            if (outletTablePanel) {
                outletTablePanel.hidden = outletViewType !== 'table';
            }
            outletTabs.forEach((tab) => {
                const isActive = String(tab.dataset.outletView || '') === outletViewType;
                tab.classList.toggle('is-active', isActive);
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            if (outletViewType === 'bars') {
                ensureOutletChart();
            }
        };

        if (outletTabs.length > 0) {
            outletTabs.forEach((button) => {
                button.addEventListener('click', () => {
                    applyOutletView(String(button.dataset.outletView || 'table'));
                });
            });
            applyOutletView(outletViewType);
        }
    })();
</script>
@endsection
