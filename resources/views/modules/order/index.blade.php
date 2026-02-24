@extends('layouts.app')

@section('title', 'Order Management')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Order Management</h1>
        <p>Create and track orders for custom, ready-made, accessories, and fabric items.</p>
    </div>
    @canany(['manage-orders', 'create-orders'])
        <div class="page-actions">
            <a href="{{ route('order.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Order
            </a>
        </div>
    @endcanany
</div>

@include('includes.reporting-filter', ['paginator' => $orders, 'placeholder' => 'Search by order no, customer, phone, status...', 'reporting' => $reporting])

<div class="table-card">
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-header">
        <div class="table-title">Order Records</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Order No</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Delivery</th>
                    <th>Worker</th>
                    <th>Status</th>
                    <th>Job Workflow</th>
                    <th>Payment</th>
                    <th>Total Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    @php
                        $authUser = auth()->user();
                        $canManageOrders = (bool) $authUser?->hasPermission('manage-orders');
                        $canViewOrders = (bool) $authUser?->hasPermission('view-orders');
                        $canViewAssignedJobs = (bool) $authUser?->hasPermission('view-assigned-jobs');
                        $isOwnAssignedOrder = (int) ($order->worker_id ?? 0) === (int) ($authUser?->id ?? 0);
                        $remainingDue = max(
                            0,
                            ((float) $order->subtotal_amount - (float) ($order->discount_amount ?? 0))
                            - (float) ($order->advance_payment_amount ?? 0)
                        );
                        $nextStatuses = $nextStatusesByOrderId[$order->id] ?? [];
                        $workerVisibleStatuses = [
                            \App\Models\Order::STATUS_IN_PROGRESS,
                            \App\Models\Order::STATUS_NEAR_COMPLETION,
                            \App\Models\Order::STATUS_COMPLETED,
                            \App\Models\Order::STATUS_DELIVERED,
                        ];
                        $visibleNextStatuses = $canManageOrders
                            ? $nextStatuses
                            : (($canViewAssignedJobs && $isOwnAssignedOrder)
                                ? collect($nextStatuses)->filter(fn ($s) => in_array($s, $workerVisibleStatuses, true))->values()->all()
                                : []);
                        $workerName = $order->worker?->name ?: '-';
                        $workflowStatuses = [
                            \App\Models\Order::STATUS_ASSIGNED,
                            \App\Models\Order::STATUS_IN_PROGRESS,
                            \App\Models\Order::STATUS_NEAR_COMPLETION,
                            \App\Models\Order::STATUS_COMPLETED,
                            \App\Models\Order::STATUS_DELIVERED,
                        ];
                        $currentWorkflowIndex = array_search($order->status, $workflowStatuses, true);
                    @endphp
                    <tr>
                        <td>
                            <div>{{ $order->order_number }}</div>
                            <small>{{ $order->outlet?->name ?: '-' }}</small>
                        </td>
                        <td>{{ $order->ordered_at?->format('M d, Y h:i A') ?: '-' }}</td>
                        <td>
                            @if ($order->customer)
                                {{ $order->customer->name }}
                                @if ($order->customer->phone)
                                    ({{ $order->customer->phone }})
                                @endif
                            @else
                                Walk-in
                            @endif
                        </td>
                        <td>
                            <div>Due: {{ $order->delivery_due_at?->format('M d, Y h:i A') ?: '-' }}</div>
                            <small>Delivered: {{ $order->delivered_at?->format('M d, Y h:i A') ?: '-' }}</small>
                        </td>
                        <td>
                            <div>{{ $workerName }}</div>
                            <small>Deadline: {{ $order->worker_deadline_at?->format('M d, Y h:i A') ?: '-' }}</small>
                            <small style="display:block;">Fabric Issued: {{ $order->fabric_issued_at?->format('M d, Y h:i A') ?: '-' }}</small>
                        </td>
                        <td>{{ $statusLabels[$order->status] ?? ucfirst($order->status ?: '-') }}</td>
                        <td>
                            <div class="order-lifecycle">
                                @foreach ($workflowStatuses as $workflowStatus)
                                    @php
                                        $isActive = $order->status === $workflowStatus;
                                        $stepIndex = array_search($workflowStatus, $workflowStatuses, true);
                                        $isDone = $stepIndex !== false
                                            && $currentWorkflowIndex !== false
                                            && $stepIndex <= $currentWorkflowIndex;
                                    @endphp
                                    <span class="order-lifecycle-step {{ $isDone ? 'is-done' : '' }} {{ $isActive ? 'is-active' : '' }}">
                                        {{ $statusLabels[$workflowStatus] ?? ucfirst($workflowStatus) }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            {{ ucfirst($order->payment_status ?: '-') }}
                            @if ($order->payment_method)
                                <div>{{ $order->payment_method }}</div>
                            @endif
                            <div>Discount: {{ number_format((float) ($order->discount_amount ?? 0), 2) }}</div>
                            <div>Advance: {{ number_format((float) ($order->advance_payment_amount ?? 0), 2) }}</div>
                            <div>Due: {{ number_format($remainingDue, 2) }}</div>
                        </td>
                        <td>{{ number_format(max(0, (float) $order->subtotal_amount - (float) ($order->discount_amount ?? 0)), 2) }}</td>
                        <td>
                            @if (empty($visibleNextStatuses))
                                <span>Locked</span>
                            @else
                                <form action="{{ route('order.status.update', $order) }}" method="POST" class="order-status-update-form" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                    @csrf
                                    @method('PUT')

                                    <select name="status" class="outlet-input order-status-select" style="min-width:160px;" required>
                                        <option value="" disabled selected>Select Next Status</option>
                                        @foreach ($visibleNextStatuses as $status)
                                            <option value="{{ $status }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</option>
                                        @endforeach
                                    </select>

                                    <input type="hidden" class="remaining-due-value" value="{{ number_format($remainingDue, 2, '.', '') }}">

                                    <div class="worker-assign-wrap" style="display:none; gap:8px; align-items:center; flex-wrap:wrap;">
                                        <select name="worker_id" class="outlet-input" style="min-width:160px;">
                                            <option value="">Select Worker</option>
                                            @foreach ($workers as $worker)
                                                <option value="{{ $worker->id }}" @selected((int) $order->worker_id === (int) $worker->id)>{{ $worker->name }}</option>
                                            @endforeach
                                        </select>
                                        <input
                                            name="worker_deadline_at"
                                            type="datetime-local"
                                            class="outlet-input"
                                            style="min-width:180px;"
                                            value="{{ old('worker_deadline_at', $order->worker_deadline_at?->format('Y-m-d\TH:i') ?: $order->delivery_due_at?->format('Y-m-d\TH:i')) }}"
                                        >
                                    </div>

                                    <div class="remaining-payment-wrap" style="display:none; gap:8px; align-items:center; flex-wrap:wrap;">
                                        <input
                                            name="remaining_payment_amount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="outlet-input remaining-payment-input"
                                            placeholder="Remaining payment"
                                            style="min-width:150px;"
                                            value="{{ number_format($remainingDue, 2, '.', '') }}"
                                        >
                                        <input
                                            name="payment_method"
                                            type="text"
                                            class="outlet-input"
                                            placeholder="Payment method"
                                            style="min-width:150px;"
                                        >
                                    </div>

                                    <button type="submit" class="btn btn-sm btn-secondary">Update</button>
                                </form>
                            @endif
                            <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:8px;">
                                @if ($canManageOrders || $canViewOrders)
                                    <a href="{{ route('order.bill.customer', $order) }}" target="_blank" class="btn btn-sm btn-outline-primary">Customer Bill</a>
                                @endif
                                @if ($canManageOrders || ($canViewAssignedJobs && $isOwnAssignedOrder))
                                    <a href="{{ route('order.bill.worker', $order) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Worker Slip</a>
                                @endif
                                @if ($canManageOrders)
                                    <a href="{{ route('order.bill.office', $order) }}" target="_blank" class="btn btn-sm btn-outline-dark">Office Bill</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="empty">No orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($orders->hasPages())
        <div class="pagination">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection

@section('page-specific-style')
<style>
    .order-lifecycle {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .order-lifecycle-step {
        font-size: 11px;
        line-height: 1;
        padding: 6px 8px;
        border-radius: 999px;
        border: 1px solid #c9d3df;
        color: #5f6d7b;
        background: #f5f8fb;
        white-space: nowrap;
    }

    .order-lifecycle-step.is-done {
        border-color: #84b49f;
        color: #1e6a4c;
        background: #e8f7ef;
    }

    .order-lifecycle-step.is-active {
        border-color: #4b78c2;
        color: #1f4f9a;
        background: #e9f1ff;
        font-weight: 600;
    }
</style>
@endsection

@section('page-specific-script')
<script>
    (function () {
        const forms = document.querySelectorAll('.order-status-update-form');
        forms.forEach((form) => {
            const statusSelect = form.querySelector('.order-status-select');
            const remainingWrap = form.querySelector('.remaining-payment-wrap');
            const remainingInput = form.querySelector('.remaining-payment-input');
            const dueInput = form.querySelector('.remaining-due-value');
            const workerWrap = form.querySelector('.worker-assign-wrap');
            const workerSelect = workerWrap?.querySelector('select[name="worker_id"]');
            const workerDeadline = workerWrap?.querySelector('input[name="worker_deadline_at"]');

            if (!statusSelect) {
                return;
            }

            const toggleFields = () => {
                const selected = statusSelect.value;
                const isAssigned = selected === '{{ \App\Models\Order::STATUS_ASSIGNED }}';
                const isDelivered = selected === '{{ \App\Models\Order::STATUS_DELIVERED }}';

                if (workerWrap) {
                    workerWrap.style.display = isAssigned ? 'flex' : 'none';
                }
                if (workerSelect) {
                    workerSelect.required = isAssigned;
                }
                if (workerDeadline) {
                    workerDeadline.required = isAssigned;
                }

                if (remainingWrap && remainingInput && dueInput) {
                    remainingWrap.style.display = isDelivered ? 'flex' : 'none';
                    remainingInput.required = isDelivered;
                    if (isDelivered && !remainingInput.value) {
                        remainingInput.value = dueInput.value || '0.00';
                    }
                }
            };

            statusSelect.addEventListener('change', toggleFields);
            toggleFields();
        });
    })();
</script>
@endsection
