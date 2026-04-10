@extends('layouts.app')

@section('title', 'View Order')

@section('content')
@php
    $authUser = auth()->user();
    $canManageOrders = (bool) $authUser?->hasPermission('manage-orders');
    $taskWorkers = $order->tasks
        ->map(fn ($task) => $task->worker)
        ->filter()
        ->unique('id')
        ->values();
    $taskDeadline = $order->tasks->pluck('worker_deadline_at')->filter()->sort()->first();
    $netPayable = $order->payableAmount();
    $paidAmount = $order->paidAmount();
    $remainingDue = $order->dueAmount();
    $canTakePayment = $canManageOrders
        && (string) $order->status !== \App\Models\Order::STATUS_CANCELLED
        && $remainingDue > 0;
    $canEditDeliveryDate = ($canManageOrders || $authUser?->hasPermission('create-orders'))
        && !in_array((string) $order->status, [
            \App\Models\Order::STATUS_DELIVERED,
            \App\Models\Order::STATUS_CANCELLED,
        ], true);
@endphp
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Order {{ $order->order_number }}</h1>
        <p>Read-only order details, items, and customer information.</p>
    </div>
    @canany(['view-task-management', 'manage-task-management', 'manage-orders'])
        <div class="page-actions">
            <a href="{{ route('taskManagement.order', $order) }}" class="btn btn-light">View Order Tasks</a>
        </div>
    @endcanany
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<style>
    .order-view-tabs {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .order-view-tab {
        border: 1px solid #d7dfeb;
        background: #fff;
        color: #334155;
        border-radius: 999px;
        padding: 10px 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .order-view-tab.is-active {
        background: #1f3d5a;
        border-color: #1f3d5a;
        color: #fff;
    }

    .order-view-panel {
        display: none;
    }

    .order-view-panel.is-active {
        display: block;
    }
</style>

<div class="order-view-tabs" role="tablist" aria-label="Order view tabs">
    <button type="button" class="order-view-tab is-active js-order-view-tab" data-tab="summary" role="tab" aria-selected="true">Summary</button>
    <button type="button" class="order-view-tab js-order-view-tab" data-tab="customer" role="tab" aria-selected="false">Customer</button>
    <button type="button" class="order-view-tab js-order-view-tab" data-tab="items" role="tab" aria-selected="false">Items</button>
</div>

<div class="order-view-panel is-active js-order-view-panel" data-panel="summary">
<div class="table-card" style="margin-bottom: 16px;">
    <div class="table-header">
        <div class="table-title">Order Summary</div>
    </div>
    <div class="outlet-form-grid" style="padding: 16px;">
        <div class="outlet-form-group">
            <label>Order Number</label>
            <div>{{ $order->order_number }}</div>
        </div>
        <div class="outlet-form-group">
            <label>Status</label>
            <div>
                <span class="app-badge {{ $order->displayStatusBadgeClass() }}">
                    {{ $order->displayStatusLabel() }}
                </span>
            </div>
        </div>
        <div class="outlet-form-group">
            <label>Payment Status</label>
            <div>
                <span class="app-badge {{ \App\Models\Order::paymentStatusBadgeClass((string) $order->payment_status) }}">
                    {{ \App\Models\Order::paymentStatusLabel((string) $order->payment_status) }}
                </span>
            </div>
        </div>
        <div class="outlet-form-group">
            <label>Outlet</label>
            <div>{{ $order->outlet?->name ?? '-' }}</div>
        </div>
        <div class="outlet-form-group">
            <label>Ordered At</label>
            <div>{{ $order->ordered_at?->format('M d, Y h:i A') ?? '-' }}</div>
        </div>
        <div class="outlet-form-group">
            <label>Delivery Due</label>
            <div>
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span>{{ $order->delivery_due_at?->format('M d, Y h:i A') ?? '-' }}</span>
                    @if ($canEditDeliveryDate)
                        <button
                            type="button"
                            class="btn btn-sm btn-light js-order-delivery-toggle"
                            aria-label="Edit delivery date"
                            title="Edit delivery date"
                        >
                            <i class="fas fa-pen"></i>
                        </button>
                    @endif
                </div>
                @if ($canEditDeliveryDate)
                    <form action="{{ route('order.deliveryDate.update', $order) }}" method="POST" class="js-order-delivery-form" style="display:none; margin-top:10px;">
                        @csrf
                        @method('PUT')
                        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
                            <div style="flex:1; min-width:220px;">
                                <input
                                    type="datetime-local"
                                    name="delivery_due_at"
                                    class="outlet-input"
                                    value="{{ $order->delivery_due_at?->format('Y-m-d\\TH:i') }}"
                                    min="{{ $order->ordered_at?->format('Y-m-d\\TH:i') }}"
                                    required
                                >
                            </div>
                            <button type="submit" class="btn btn-sm btn-secondary">Save</button>
                            <button type="button" class="btn btn-sm btn-light js-order-delivery-cancel">Cancel</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
        <div class="outlet-form-group">
            <label>Task Workers</label>
            <div>
                @if ($taskWorkers->isNotEmpty())
                    @foreach ($taskWorkers as $worker)
                        @canany(['view-task-management', 'manage-task-management', 'manage-orders'])
                            <a href="{{ route('worker.tasks', $worker) }}" style="text-decoration: underline;">
                                {{ $worker->name }}
                            </a>@if (! $loop->last), @endif
                        @else
                            {{ $worker->name }}@if (! $loop->last), @endif
                        @endcanany
                    @endforeach
                @else
                    Unassigned
                @endif
            </div>
        </div>
        <div class="outlet-form-group">
            <label>Task Deadline</label>
            <div>{{ $taskDeadline?->format('M d, Y h:i A') ?? '-' }}</div>
        </div>
        <div class="outlet-form-group">
            <label>Created By</label>
            <div>{{ $order->creator?->name ?? '-' }}</div>
        </div>
        <div class="outlet-form-group outlet-form-group-full">
            <label>Notes</label>
            <div>{{ $order->notes ?: '-' }}</div>
        </div>
    </div>
</div>

<div class="table-card" style="margin-top: 16px;">
    <div class="table-header">
        <div class="table-title">Payment Summary</div>
    </div>
    <div class="outlet-form-grid" style="padding: 16px;">
        <div class="outlet-form-group">
            <label>Payable Amount</label>
            <div>Rs. {{ number_format($netPayable, 2) }}</div>
        </div>
        <div class="outlet-form-group">
            <label>Paid Amount</label>
            <div>Rs. {{ number_format($paidAmount, 2) }}</div>
        </div>
        <div class="outlet-form-group">
            <label>Due Amount</label>
            <div>Rs. {{ number_format($remainingDue, 2) }}</div>
        </div>
        <div class="outlet-form-group">
            <label>Payment Method</label>
            <div>{{ $order->payment_method ?: '-' }}</div>
        </div>
    </div>

    @if ($canTakePayment)
        <form action="{{ route('order.payment.update', $order) }}" method="POST" style="padding: 0 16px 16px;">
            @csrf
            @method('PUT')

            <div class="outlet-form-grid">
                <div class="outlet-form-group">
                    <label for="payment_amount">Payment Amount</label>
                    <input
                        id="payment_amount"
                        type="number"
                        name="payment_amount"
                        class="outlet-input"
                        min="0.01"
                        max="{{ number_format($remainingDue, 2, '.', '') }}"
                        step="0.01"
                        value="{{ number_format($remainingDue, 2, '.', '') }}"
                        required
                    >
                </div>
                <div class="outlet-form-group">
                    <label for="payment_method">Payment Method</label>
                    <input
                        id="payment_method"
                        type="text"
                        name="payment_method"
                        class="outlet-input"
                        value="{{ old('payment_method', $order->payment_method ?: 'cash') }}"
                        placeholder="Payment method"
                    >
                </div>
            </div>

            <div class="outlet-form-actions" style="padding: 0; margin-top: 12px;">
                <button type="submit" class="btn btn-secondary">Record Payment</button>
            </div>
        </form>
    @endif
</div>
</div>

<div class="order-view-panel js-order-view-panel" data-panel="customer">
<div class="table-card" style="margin-bottom: 16px;">
    <div class="table-header">
        <div class="table-title">Customer</div>
    </div>
    <div class="outlet-form-grid" style="padding: 16px;">
        <div class="outlet-form-group">
            <label>Name</label>
            <div>
                @if ($order->customer)
                    @canany(['view-customers', 'manage-customers'])
                        <a href="{{ route('customer.show', $order->customer) }}" style="text-decoration: underline;">
                            {{ $order->customer->name }}
                        </a>
                    @else
                        {{ $order->customer->name }}
                    @endcanany
                @else
                    -
                @endif
            </div>
        </div>
        <div class="outlet-form-group">
            <label>Phone</label>
            <div>{{ $order->customer?->phone ?? '-' }}</div>
        </div>
        <div class="outlet-form-group">
            <label>Email</label>
            <div>{{ $order->customer?->email ?? '-' }}</div>
        </div>
        <div class="outlet-form-group outlet-form-group-full">
            <label>Address</label>
            <div>{{ $order->customer?->address ?? '-' }}</div>
        </div>
    </div>
</div>
</div>

<div class="order-view-panel js-order-view-panel" data-panel="items">
<div class="table-card">
    <div class="table-header">
        <div class="table-title">Order Items</div>
    </div>
    <style>
        .order-item-meta {
            margin-top: 6px;
            color: #5f7083;
            font-size: 13px;
        }

        .order-custom-block {
            margin-top: 12px;
            padding: 12px 14px;
            border: 1px solid #e3eaf3;
            border-radius: 10px;
            background: #f8fbff;
        }

        .order-custom-title {
            font-weight: 700;
            color: #1f3d5a;
        }

        .order-custom-grid {
            display: grid;
            gap: 10px;
            margin-top: 10px;
        }

        .order-custom-card {
            padding: 12px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #dbe3ee;
        }

        .order-custom-card-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }

        .order-custom-card-title {
            font-weight: 700;
            color: #1f3d5a;
        }

        .order-custom-card-meta {
            margin-top: 4px;
            color: #5f7083;
            font-size: 13px;
        }

        .order-custom-measurements,
        .order-custom-notes {
            margin-top: 10px;
            color: #3b4b5c;
            font-size: 13px;
        }

        .order-custom-images {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .order-custom-images img {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #dbe3ee;
            background: #fff;
        }

        .order-task-edit-btn {
            margin-left: 8px;
            width: 30px;
            height: 30px;
            border: 1px solid #d7dfeb;
            border-radius: 8px;
            background: #fff;
            color: #334155;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .order-task-edit-btn:hover {
            background: #f8fafc;
        }

        .app-modal {
            position: fixed;
            inset: 0;
            display: none;
            z-index: 1300;
        }

        .app-modal.is-open {
            display: block;
        }

        .app-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
        }

        .app-modal__panel {
            position: relative;
            width: min(640px, calc(100vw - 32px));
            margin: 48px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
            overflow: hidden;
        }

        .app-modal__header {
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .app-modal__header h3 {
            margin: 0;
        }

        .app-modal__meta {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .order-task-modal-close {
            width: 36px;
            height: 36px;
            border: 1px solid #d7dfeb;
            border-radius: 10px;
            background: #fff;
            color: #334155;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .order-task-modal-close:hover {
            background: #f8fafc;
        }

        .order-task-assign-grid {
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .order-task-assign-grid .outlet-form-group {
            margin-bottom: 0;
        }

        .order-task-assign-grid .order-task-assign-full {
            grid-column: 1 / -1;
        }

        .order-task-assign-actions {
            padding: 0 20px 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .app-modal__panel .select2-container {
            width: 100% !important;
        }

        .app-modal__panel .select2-container--default .select2-selection--single {
            height: 46px;
            border-radius: 10px;
            border: 1px solid #d7dfeb;
            background: #fff;
        }

        .app-modal__panel .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 44px;
            padding-left: 12px;
            color: #0f172a;
            font-size: 14px;
        }

        .app-modal__panel .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 44px;
            right: 8px;
        }

        body.app-modal-open {
            overflow: hidden;
        }

        @media (max-width: 768px) {
            .order-task-assign-grid {
                grid-template-columns: 1fr;
            }

            .order-task-assign-grid .order-task-assign-full {
                grid-column: auto;
            }

            .order-task-assign-actions {
                flex-direction: column;
            }
        }
    </style>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Code</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Unit Price</th>
                    <th>Line Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($order->items as $item)
                    @php
                        $isCustom = (string) $item->item_category === 'custom';
                        $customDetails = (array) ($item->custom_details ?? []);
                        $garments = collect((array) data_get($customDetails, 'garments', []));
                        $fabricProduct = $isCustom
                            ? $customFabricProducts->get((int) data_get($customDetails, 'fabric_product_id', 0))
                            : null;
                        $displayName = $isCustom
                            ? ($fabricProduct?->name ?: 'Custom Fabric')
                            : ($item->product?->name ?? '-');
                        $displayCode = $isCustom
                            ? ($fabricProduct?->code ?: '-')
                            : ($item->product?->code ?? '-');
                        $displayUnit = $isCustom
                            ? (data_get($customDetails, 'fabric_quantity_unit') ?: data_get($customDetails, 'quantity_unit') ?: 'm')
                            : ((string) $item->item_category === 'fabric' ? 'm' : 'pcs');
                    @endphp
                    <tr>
                        <td>
                            {{ $displayName }}
                            @php
                                $itemTasks = $order->tasks
                                    ->where('order_item_id', $item->id)
                                    ->values();
                            @endphp
                            @if (! $isCustom && $itemTasks->isNotEmpty())
                                <div class="order-item-meta">
                                    Assigned:
                                    @foreach ($itemTasks as $itemTask)
                                        @if ($itemTask->worker)
                                            @canany(['view-task-management', 'manage-task-management', 'manage-orders'])
                                                <a href="{{ route('worker.tasks', $itemTask->worker) }}" style="text-decoration: underline;">
                                                    {{ $itemTask->worker->name }}
                                                </a>
                                            @else
                                                {{ $itemTask->worker->name }}
                                            @endcanany
                                        @else
                                            Unassigned
                                        @endif
                                        <span class="app-badge {{ \App\Models\OrderTask::statusBadgeClass((string) $itemTask->status) }}" style="margin-left: 6px; min-width: 0;">
                                            {{ $itemTask->statusLabel() }}
                                        </span>
                                        @if ($itemTask->task_number)
                                            @canany(['view-task-management', 'manage-task-management', 'manage-orders'])
                                                <a href="{{ route('taskManagement.order', ['order' => $order, 'q' => $itemTask->task_number]) }}" style="margin-left: 6px; text-decoration: underline;">
                                                    {{ $itemTask->task_number }}
                                                </a>
                                            @else
                                                <span style="margin-left: 6px;">{{ $itemTask->task_number }}</span>
                                            @endcanany
                                        @endif
                                        @if (! $loop->last)
                                            ,
                                        @endif
                                    @endforeach
                                </div>
                                @canany(['manage-task-management', 'manage-orders'])
                                    @foreach ($itemTasks as $itemTask)
                                        <button
                                            type="button"
                                            class="order-task-edit-btn js-open-order-task-modal"
                                            title="Assign task"
                                            aria-label="Assign task"
                                            data-task-id="{{ $itemTask->id }}"
                                            data-task-number="{{ $itemTask->task_number ?: '-' }}"
                                            data-order-number="{{ $order->order_number }}"
                                            data-customer-name="{{ $order->customer?->name ?: '-' }}"
                                            data-task-title="{{ $itemTask->task_title }}"
                                            data-worker-id="{{ (int) ($itemTask->worker_id ?? 0) }}"
                                            data-worker-deadline="{{ $itemTask->worker_deadline_at?->format('Y-m-d\\TH:i') }}"
                                            data-notes="{{ $itemTask->notes }}"
                                            data-slip-received="{{ $itemTask->slip_received_at ? '1' : '0' }}"
                                        >
                                            <i class="fas fa-pen"></i>
                                        </button>
                                    @endforeach
                                @endcanany
                            @endif
                            @if ($isCustom)
                                <div class="order-item-meta">
                                    Fabric Source: {{ ucfirst((string) data_get($customDetails, 'fabric_source', 'own')) }}
                                </div>
                            @endif
                        </td>
                        <td>{{ $displayCode }}</td>
                        <td>{{ number_format((float) $item->quantity, 2) }}</td>
                        <td>{{ $displayUnit }}</td>
                        <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td>{{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                    @if ($isCustom)
                        <tr>
                            <td colspan="6" style="background: #fcfdff;">
                                <div class="order-custom-block">
                                    <div class="order-custom-title">Custom Garment Details</div>
                                    <div class="order-item-meta">
                                        Tailoring Total: {{ number_format((float) data_get($customDetails, 'tailoring_total_price', 0), 2) }}
                                    </div>
                                    @if ($garments->isNotEmpty())
                                        <div class="order-custom-grid">
                                            @foreach ($garments as $garment)
                                                @php
                                                    $garmentTask = $order->tasks
                                                        ->first(function ($task) use ($item, $loop) {
                                                            return (int) $task->order_item_id === (int) $item->id
                                                                && (int) $task->source_garment_index === (int) $loop->index;
                                                        });
                                                    $garmentNotes = collect((array) ($garment['design_note'] ?? []))
                                                        ->map(fn ($note) => trim((string) $note))
                                                        ->filter()
                                                        ->values();
                                                    $garmentImages = collect((array) ($garment['design_images'] ?? []))
                                                        ->push($garment['design_image'] ?? null)
                                                        ->filter(fn ($path) => filled($path))
                                                        ->unique()
                                                        ->values();
                                                @endphp
                                                <div class="order-custom-card">
                                                    <div class="order-custom-card-head">
                                                        <div>
                                                            <div class="order-custom-card-title">
                                                                {{ $garment['garment_title'] ?? 'Garment' }} x {{ number_format((float) ($garment['quantity'] ?? 1), 2) }}
                                                            </div>
                                                            <div class="order-custom-card-meta">
                                                                {{ $garment['tailoring_package'] ?? 'Tailoring' }} | NPR {{ number_format((float) ($garment['tailoring_amount'] ?? 0), 2) }}
                                                            </div>
                                                            <div class="order-custom-card-meta">
                                                                <strong>Worker:</strong>
                                                                @if ($garmentTask?->worker)
                                                                    @canany(['view-task-management', 'manage-task-management', 'manage-orders'])
                                                                        <a href="{{ route('worker.tasks', $garmentTask->worker) }}" style="text-decoration: underline;">
                                                                            {{ $garmentTask->worker->name }}
                                                                        </a>
                                                                    @else
                                                                        {{ $garmentTask->worker->name }}
                                                                    @endcanany
                                                                @else
                                                                    Unassigned
                                                                @endif
                                                                @if ($garmentTask)
                                                                    | <strong>Task:</strong>
                                                                    @if ($garmentTask->task_number)
                                                                        @canany(['view-task-management', 'manage-task-management', 'manage-orders'])
                                                                            <a href="{{ route('taskManagement.order', ['order' => $order, 'q' => $garmentTask->task_number]) }}" style="text-decoration: underline;">
                                                                                {{ $garmentTask->task_number }}
                                                                            </a>
                                                                        @else
                                                                            {{ $garmentTask->task_number }}
                                                                        @endcanany
                                                                    @else
                                                                        -
                                                                    @endif
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div style="font-weight:700;color:#1f3d5a;">
                                                            NPR {{ number_format((float) ($garment['tailoring_total_amount'] ?? 0), 2) }}
                                                        </div>
                                                    </div>
                                                    @if ($garmentTask)
                                                        <div class="order-custom-measurements">
                                                            <strong>Status:</strong>
                                                            <span class="app-badge {{ \App\Models\OrderTask::statusBadgeClass((string) $garmentTask->status) }}" style="min-width: 0; margin-left: 6px;">
                                                                {{ $garmentTask->statusLabel() }}
                                                            </span>
                                                            @canany(['manage-task-management', 'manage-orders'])
                                                                <button
                                                                    type="button"
                                                                    class="order-task-edit-btn js-open-order-task-modal"
                                                                    title="Assign task"
                                                                    aria-label="Assign task"
                                                                    data-task-id="{{ $garmentTask->id }}"
                                                                    data-task-number="{{ $garmentTask->task_number ?: '-' }}"
                                                                    data-order-number="{{ $order->order_number }}"
                                                                    data-customer-name="{{ $order->customer?->name ?: '-' }}"
                                                                    data-task-title="{{ $garmentTask->task_title }}"
                                                                    data-worker-id="{{ (int) ($garmentTask->worker_id ?? 0) }}"
                                                                    data-worker-deadline="{{ $garmentTask->worker_deadline_at?->format('Y-m-d\\TH:i') }}"
                                                                    data-notes="{{ $garmentTask->notes }}"
                                                                    data-slip-received="{{ $garmentTask->slip_received_at ? '1' : '0' }}"
                                                                >
                                                                    <i class="fas fa-pen"></i>
                                                                </button>
                                                            @endcanany
                                                            @if ($garmentTask->worker_deadline_at)
                                                                <span style="margin-left: 8px;">Deadline: {{ $garmentTask->worker_deadline_at->format('M d, Y h:i A') }}</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                    <div class="order-custom-measurements">
                                                        <strong>Measurements:</strong>
                                                        {{ collect((array) ($garment['measurements'] ?? []))->map(function ($measurement) {
                                                            return trim((string) ($measurement['type'] ?? '-')) . ': '
                                                                . trim((string) ($measurement['measurement'] ?? '-'))
                                                                . (filled($measurement['unit'] ?? null) ? ' ' . trim((string) $measurement['unit']) : '');
                                                        })->implode(', ') ?: '-' }}
                                                    </div>
                                                    <div class="order-custom-notes">
                                                        <strong>Design Note:</strong> {{ $garmentNotes->isNotEmpty() ? $garmentNotes->implode(', ') : '-' }}
                                                    </div>
                                                    @if ($garmentImages->isNotEmpty())
                                                        <div class="order-custom-images">
                                                            @foreach ($garmentImages as $imagePath)
                                                                @php
                                                                    $imagePath = (string) $imagePath;
                                                                    $imageUrl = str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')
                                                                        ? $imagePath
                                                                        : \Illuminate\Support\Facades\Storage::url(ltrim($imagePath, '/'));
                                                                @endphp
                                                                <a href="{{ $imageUrl }}" target="_blank" rel="noopener">
                                                                    <img src="{{ $imageUrl }}" alt="Design Sample">
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="order-item-meta">No garment breakdown saved for this custom item.</div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="6" class="empty">No order items found.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" style="text-align: right;">Subtotal</th>
                    <th>{{ number_format((float) ($order->subtotal_amount ?? 0), 2) }}</th>
                </tr>
                <tr>
                    <th colspan="5" style="text-align: right;">Discount</th>
                    <th>{{ number_format((float) ($order->discount_amount ?? 0), 2) }}</th>
                </tr>
                <tr>
                    <th colspan="5" style="text-align: right;">Tailoring Amount</th>
                    <th>{{ number_format((float) ($order->tailoring_amount ?? 0), 2) }}</th>
                </tr>
                <tr>
                    <th colspan="5" style="text-align: right;">VAT {{ $order->vat_enabled ? '(13%)' : '' }}</th>
                    <th>{{ number_format((float) ($order->vat_enabled ? ($order->vat_amount ?? 0) : 0), 2) }}</th>
                </tr>
                <tr>
                    <th colspan="5" style="text-align: right;">Payable Amount</th>
                    <th>{{ number_format($order->payableAmount(), 2) }}</th>
                </tr>
                <tr>
                    <th colspan="5" style="text-align: right;">Advance Paid</th>
                    <th>{{ number_format($order->paidAmount(), 2) }}</th>
                </tr>
                <tr>
                    <th colspan="5" style="text-align: right;">Due Amount</th>
                    <th>{{ number_format($order->dueAmount(), 2) }}</th>
                </tr>
            </tfoot>
        </table>
</div>
</div>
</div>

<div class="outlet-form-actions" style="padding: 16px;">
        <a href="{{ route('order.index') }}" class="btn btn-secondary">Back to Orders</a>
        @if ($order->customer_id)
            <a href="{{ route('customer.show', $order->customer_id) }}" class="btn btn-secondary">Back to Customer</a>
        @endif
        @if (in_array((string) $order->status, [\App\Models\Order::STATUS_PENDING, \App\Models\Order::STATUS_CONFIRMED, \App\Models\Order::STATUS_FABRIC_ISSUED], true))
            <a href="{{ route('order.edit', $order) }}" class="btn btn-primary">Edit Order</a>
        @endif
    </div>

<div id="orderTaskAssignModal" class="app-modal" aria-hidden="true">
    <div class="app-modal__backdrop js-order-task-modal-close"></div>
    <div class="app-modal__panel" role="dialog" aria-modal="true" aria-labelledby="orderTaskAssignModalTitle">
        <div class="app-modal__header">
            <div>
                <h3 id="orderTaskAssignModalTitle">Assign Task</h3>
                <p class="app-modal__meta js-order-task-modal-meta">-</p>
            </div>
            <button type="button" class="order-task-modal-close js-order-task-modal-close" aria-label="Close assign modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="orderTaskAssignForm" method="POST">
            @csrf
            @method('PUT')

            <div class="order-task-assign-grid">
                <div class="outlet-form-group">
                    <label for="orderTaskAssignWorker">Worker</label>
                    <select id="orderTaskAssignWorker" name="worker_id" class="outlet-input">
                        <option value="">Select Worker</option>
                        @foreach ($workers as $worker)
                            <option value="{{ $worker->id }}">{{ $worker->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="outlet-form-group">
                    <label for="orderTaskAssignDeadline">Deadline</label>
                    <input id="orderTaskAssignDeadline" type="datetime-local" name="worker_deadline_at" class="outlet-input">
                </div>

                <div class="outlet-form-group order-task-assign-full">
                    <label for="orderTaskAssignNotes">Notes</label>
                    <textarea id="orderTaskAssignNotes" name="notes" class="outlet-input" rows="3" placeholder="Assignment note"></textarea>
                </div>

                <div class="outlet-form-group order-task-assign-full">
                    <label style="display:inline-flex;align-items:center;gap:10px;font-weight:500;">
                        <input id="orderTaskAssignSlip" type="checkbox" name="slip_received" value="1">
                        <span>Slip Received</span>
                    </label>
                </div>
            </div>

            <div class="order-task-assign-actions">
                <button type="button" class="btn btn-sm btn-light js-order-task-modal-close">Cancel</button>
                <button type="submit" class="btn btn-sm btn-secondary">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('page-specific-script')
<script>
    (() => {
        const body = document.body;
        const modal = document.getElementById('orderTaskAssignModal');
        const form = document.getElementById('orderTaskAssignForm');
        const meta = modal?.querySelector('.js-order-task-modal-meta');
        const workerInput = document.getElementById('orderTaskAssignWorker');
        const deadlineInput = document.getElementById('orderTaskAssignDeadline');
        const notesInput = document.getElementById('orderTaskAssignNotes');
        const slipInput = document.getElementById('orderTaskAssignSlip');
        const modalPanel = modal?.querySelector('.app-modal__panel');
        const tabButtons = document.querySelectorAll('.js-order-view-tab');
        const tabPanels = document.querySelectorAll('.js-order-view-panel');

        if (!modal || !form || !workerInput || !deadlineInput || !notesInput || !slipInput) {
            return;
        }

        const setActiveTab = (tabName) => {
            tabButtons.forEach((button) => {
                const isActive = button.dataset.tab === tabName;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            tabPanels.forEach((panel) => {
                panel.classList.toggle('is-active', panel.dataset.panel === tabName);
            });
        };

        tabButtons.forEach((button) => {
            button.addEventListener('click', () => setActiveTab(button.dataset.tab || 'summary'));
        });

        const initWorkerSelect2 = () => {
            if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                return;
            }

            const $worker = window.jQuery(workerInput);
            if ($worker.hasClass('select2-hidden-accessible')) {
                $worker.off('.orderTaskAssignSelect2');
                $worker.select2('destroy');
            }

            $worker.select2({
                width: '100%',
                placeholder: 'Select Worker',
                allowClear: true,
                dropdownParent: window.jQuery(modalPanel || modal),
            });
        };

        const closeModal = () => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            body.classList.remove('app-modal-open');
        };

        const openModal = () => {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            body.classList.add('app-modal-open');
        };

        modal.querySelectorAll('.js-order-task-modal-close').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        document.querySelectorAll('.js-open-order-task-modal').forEach((button) => {
            button.addEventListener('click', () => {
                form.action = "{{ url('/task-management/update') }}/" + button.dataset.taskId;
                workerInput.value = button.dataset.workerId || '';
                deadlineInput.value = button.dataset.workerDeadline || '';
                notesInput.value = button.dataset.notes || '';
                slipInput.checked = button.dataset.slipReceived === '1';

                if (meta) {
                    meta.textContent = `${button.dataset.taskNumber} | Order ${button.dataset.orderNumber} | ${button.dataset.customerName} | ${button.dataset.taskTitle}`;
                }

                openModal();
                initWorkerSelect2();
                if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                    window.jQuery(workerInput).trigger('change.select2');
                }
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });

        document.querySelectorAll('.js-order-delivery-toggle').forEach((button) => {
            button.addEventListener('click', () => {
                const form = button.closest('.outlet-form-group')?.querySelector('.js-order-delivery-form');
                if (form) {
                    form.style.display = form.style.display === 'none' || form.style.display === '' ? 'block' : 'none';
                }
            });
        });

        document.querySelectorAll('.js-order-delivery-cancel').forEach((button) => {
            button.addEventListener('click', () => {
                const form = button.closest('.js-order-delivery-form');
                if (form) {
                    form.style.display = 'none';
                }
            });
        });
    })();
</script>
@endsection
