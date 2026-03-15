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
                        $netPayable = $order->payableAmount();
                        $paidAmount = $order->paidAmount();
                        $remainingDue = $order->dueAmount();
                        $canTakePayment = $canManageOrders
                            && (string) $order->status !== \App\Models\Order::STATUS_CANCELLED
                            && $remainingDue > 0;
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
                        $isEditable = !in_array((string) $order->status, [
                            \App\Models\Order::STATUS_ASSIGNED,
                            \App\Models\Order::STATUS_IN_PROGRESS,
                            \App\Models\Order::STATUS_NEAR_COMPLETION,
                            \App\Models\Order::STATUS_COMPLETED,
                            \App\Models\Order::STATUS_DELIVERED,
                            \App\Models\Order::STATUS_CANCELLED,
                        ], true);
                        $canEditDeliveryDate = !in_array((string) $order->status, [
                            \App\Models\Order::STATUS_DELIVERED,
                            \App\Models\Order::STATUS_CANCELLED,
                        ], true);
                        $workerName = $order->worker?->name ?: '-';
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
                            <div class="order-delivery-cell">
                                <span>Due: {{ $order->delivery_due_at?->format('M d, Y h:i A') ?: '-' }}</span>
                                @if (($canManageOrders || $authUser?->hasPermission('create-orders')) && $canEditDeliveryDate)
                                    <button
                                        type="button"
                                        class="order-delivery-edit-btn"
                                        data-delivery-toggle
                                        aria-label="Edit delivery date"
                                        title="Edit delivery date"
                                    >
                                        <i class="fas fa-pen"></i>
                                    </button>
                                @endif
                            </div>
                            @if (($canManageOrders || $authUser?->hasPermission('create-orders')) && $canEditDeliveryDate)
                                <form action="{{ route('order.deliveryDate.update', $order) }}" method="POST" class="order-delivery-edit-form" style="display:none;">
                                    @csrf
                                    @method('PUT')
                                    <input
                                        type="datetime-local"
                                        name="delivery_due_at"
                                        class="outlet-input"
                                        value="{{ $order->delivery_due_at?->format('Y-m-d\TH:i') }}"
                                        min="{{ $order->ordered_at?->format('Y-m-d\TH:i') }}"
                                        required
                                    >
                                    <div class="order-delivery-edit-actions">
                                        <button type="submit" class="btn btn-sm btn-secondary">Save</button>
                                        <button type="button" class="btn btn-sm btn-light" data-delivery-cancel>Cancel</button>
                                    </div>
                                </form>
                            @endif
                            <small>Delivered: {{ $order->delivered_at?->format('M d, Y h:i A') ?: '-' }}</small>
                        </td>
                        <td>
                            <div>{{ $workerName }}</div>
                            <small>Deadline: {{ $order->worker_deadline_at?->format('M d, Y h:i A') ?: '-' }}</small>
                            <small style="display:block;">Fabric Issued: {{ $order->fabric_issued_at?->format('M d, Y h:i A') ?: '-' }}</small>
                        </td>
                        <td>{{ $statusLabels[$order->status] ?? ucfirst($order->status ?: '-') }}</td>
                        <td>
                            <div class="order-payment-cell">
                                <span>{{ ucfirst($order->payment_status ?: '-') }}</span>
                                @if ($canTakePayment)
                                    <button
                                        type="button"
                                        class="order-payment-btn"
                                        data-payment-toggle
                                        aria-label="Record payment"
                                        title="Record payment"
                                    >
                                        <i class="fas fa-money-bill-wave"></i>
                                    </button>
                                @endif
                            </div>
                            @if ($canTakePayment)
                                <form action="{{ route('order.payment.update', $order) }}" method="POST" class="order-payment-form" style="display:none;">
                                    @csrf
                                    @method('PUT')
                                    <input
                                        type="number"
                                        name="payment_amount"
                                        class="outlet-input"
                                        min="0.01"
                                        max="{{ number_format($remainingDue, 2, '.', '') }}"
                                        step="0.01"
                                        value="{{ number_format($remainingDue, 2, '.', '') }}"
                                        placeholder="Payment amount"
                                        required
                                    >
                                    <input
                                        type="text"
                                        name="payment_method"
                                        class="outlet-input"
                                        placeholder="Payment method"
                                        value="{{ old('payment_method', $order->payment_method) }}"
                                        required
                                    >
                                    <div class="order-payment-hint">
                                        Due: {{ number_format($remainingDue, 2) }} | Paid: {{ number_format($paidAmount, 2) }}
                                    </div>
                                    <div class="order-payment-actions">
                                        <button type="submit" class="btn btn-sm btn-secondary">Pay</button>
                                        <button type="button" class="btn btn-sm btn-light" data-payment-full data-full-amount="{{ number_format($remainingDue, 2, '.', '') }}">Full</button>
                                        <button type="button" class="btn btn-sm btn-light" data-payment-cancel>Cancel</button>
                                    </div>
                                </form>
                            @endif
                        </td>
                        <td>{{ number_format($netPayable, 2) }}</td>
                        <td>
                            <details class="order-actions-menu">
                                <summary class="order-actions-toggle" aria-label="Open order actions">
                                    <i class="fas fa-ellipsis-v"></i>
                                </summary>

                                <div class="order-actions-dropdown">
                                    @if (!empty($visibleNextStatuses))
                                        <form action="{{ route('order.status.update', $order) }}" method="POST" class="order-status-update-form order-actions-form">
                                            @csrf
                                            @method('PUT')

                                            <div class="order-actions-section-title">Update Status</div>

                                            <select name="status" class="outlet-input order-status-select" required>
                                                <option value="" disabled selected>Select Next Status</option>
                                                @foreach ($visibleNextStatuses as $status)
                                                    <option value="{{ $status }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</option>
                                                @endforeach
                                            </select>

                                            <input type="hidden" class="remaining-due-value" value="{{ number_format($remainingDue, 2, '.', '') }}">

                                            <div class="worker-assign-wrap order-actions-hidden-row">
                                                <select name="worker_id" class="outlet-input">
                                                    <option value="">Select Worker</option>
                                                    @foreach ($workers as $worker)
                                                        <option value="{{ $worker->id }}" @selected((int) $order->worker_id === (int) $worker->id)>{{ $worker->name }}</option>
                                                    @endforeach
                                                </select>
                                                <input
                                                    name="worker_deadline_at"
                                                    type="datetime-local"
                                                    class="outlet-input"
                                                    value="{{ old('worker_deadline_at', $order->worker_deadline_at?->format('Y-m-d\TH:i') ?: $order->delivery_due_at?->format('Y-m-d\TH:i')) }}"
                                                >
                                            </div>

                                            <div class="remaining-payment-wrap order-actions-hidden-row">
                                                <input
                                                    name="remaining_payment_amount"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    class="outlet-input remaining-payment-input"
                                                    placeholder="Remaining payment"
                                                    value="{{ number_format($remainingDue, 2, '.', '') }}"
                                                >
                                                <input
                                                    name="payment_method"
                                                    type="text"
                                                    class="outlet-input"
                                                    placeholder="Payment method"
                                                >
                                            </div>

                                            <button type="submit" class="btn btn-sm btn-secondary">Update</button>
                                        </form>
                                    @else
                                        <div class="order-actions-locked">Locked</div>
                                    @endif

                                    <div class="order-actions-links">
                                        @if (($canManageOrders || $authUser?->hasPermission('create-orders')) && $isEditable)
                                            <a href="{{ route('order.edit', $order) }}" class="order-actions-link">Edit</a>
                                        @endif
                                        @if ($canManageOrders || $canViewOrders)
                                            <a href="{{ route('order.bill.customer', $order) }}" target="_blank" class="order-actions-link">Customer Bill</a>
                                        @endif
                                        @if ($canManageOrders || ($canViewAssignedJobs && $isOwnAssignedOrder))
                                            <a href="{{ route('order.bill.worker', $order) }}" target="_blank" class="order-actions-link">Worker Slip</a>
                                        @endif
                                        @if ($canManageOrders)
                                            <a href="{{ route('order.bill.office', $order) }}" target="_blank" class="order-actions-link">Office Bill</a>
                                        @endif
                                    </div>
                                </div>
                            </details>
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
    .order-actions-menu {
        position: relative;
        display: inline-block;
    }

    .order-actions-menu summary {
        list-style: none;
    }

    .order-actions-menu summary::-webkit-details-marker {
        display: none;
    }

    .order-actions-toggle {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #d7dfeb;
        border-radius: 10px;
        background: #fff;
        color: #334155;
        cursor: pointer;
    }

    .order-actions-toggle:hover {
        background: #f8fafc;
    }

    .order-actions-dropdown {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        z-index: 20;
        width: 280px;
        padding: 14px;
        border: 1px solid #d7dfeb;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
    }

    .order-actions-form {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .order-actions-form .outlet-input {
        min-width: 100%;
    }

    .order-actions-hidden-row {
        display: none;
        flex-direction: column;
        gap: 10px;
    }

    .order-actions-section-title {
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
    }

    .order-actions-links {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e5eaf1;
    }

    .order-actions-link {
        display: block;
        padding: 8px 10px;
        border-radius: 10px;
        color: #1e293b;
        text-decoration: none;
        background: #f8fafc;
    }

    .order-actions-link:hover {
        background: #eef2f7;
        color: #0f172a;
    }

    .order-actions-locked {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 8px;
    }

    .order-delivery-cell {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .order-delivery-edit-btn {
        width: 26px;
        height: 26px;
        border: 1px solid #d7dfeb;
        border-radius: 999px;
        background: #fff;
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .order-delivery-edit-btn:hover {
        background: #f8fafc;
    }

    .order-delivery-edit-form {
        margin: 8px 0 4px;
        display: grid;
        gap: 8px;
        max-width: 220px;
    }

    .order-delivery-edit-actions {
        display: flex;
        gap: 8px;
    }

    .order-payment-cell {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .order-payment-btn {
        width: 26px;
        height: 26px;
        border: 1px solid #d7dfeb;
        border-radius: 999px;
        background: #fff;
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .order-payment-btn:hover {
        background: #f8fafc;
    }

    .order-payment-form {
        margin-top: 8px;
        display: grid;
        gap: 8px;
        max-width: 220px;
    }

    .order-payment-hint {
        font-size: 12px;
        color: #64748b;
    }

    .order-payment-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
</style>
@endsection

@section('page-specific-script')
<script>
    (function () {
        const actionMenus = document.querySelectorAll('.order-actions-menu');
        const forms = document.querySelectorAll('.order-status-update-form');

        actionMenus.forEach((menu) => {
            menu.addEventListener('toggle', () => {
                if (!menu.open) {
                    return;
                }

                actionMenus.forEach((otherMenu) => {
                    if (otherMenu !== menu) {
                        otherMenu.open = false;
                    }
                });
            });
        });

        document.addEventListener('click', (event) => {
            actionMenus.forEach((menu) => {
                if (!menu.open) {
                    return;
                }

                if (!menu.contains(event.target)) {
                    menu.open = false;
                }
            });
        });

        document.querySelectorAll('[data-delivery-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const cell = button.closest('td');
                const form = cell?.querySelector('.order-delivery-edit-form');

                document.querySelectorAll('.order-delivery-edit-form').forEach((otherForm) => {
                    if (otherForm !== form) {
                        otherForm.style.display = 'none';
                    }
                });

                if (!form) {
                    return;
                }

                form.style.display = form.style.display === 'none' || form.style.display === '' ? 'grid' : 'none';
            });
        });

        document.querySelectorAll('[data-delivery-cancel]').forEach((button) => {
            button.addEventListener('click', () => {
                const form = button.closest('.order-delivery-edit-form');
                if (form) {
                    form.style.display = 'none';
                }
            });
        });

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

        document.querySelectorAll('[data-payment-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const cell = button.closest('td');
                const form = cell?.querySelector('.order-payment-form');

                document.querySelectorAll('.order-payment-form').forEach((otherForm) => {
                    if (otherForm !== form) {
                        otherForm.style.display = 'none';
                    }
                });

                if (!form) {
                    return;
                }

                form.style.display = form.style.display === 'none' || form.style.display === '' ? 'grid' : 'none';
            });
        });

        document.querySelectorAll('[data-payment-cancel]').forEach((button) => {
            button.addEventListener('click', () => {
                const form = button.closest('.order-payment-form');
                if (form) {
                    form.style.display = 'none';
                }
            });
        });

        document.querySelectorAll('[data-payment-full]').forEach((button) => {
            button.addEventListener('click', () => {
                const form = button.closest('.order-payment-form');
                const amountInput = form?.querySelector('input[name="payment_amount"]');

                if (amountInput) {
                    amountInput.value = button.getAttribute('data-full-amount') || '0.00';
                    amountInput.focus();
                }
            });
        });
    })();
</script>
@endsection
