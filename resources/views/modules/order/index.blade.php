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

@php
    $query = trim((string) request('q', ''));
@endphp

<div class="directory-reporting" style="margin-bottom: 16px;">
    <div class="directory-reporting__filter-bar">
        <div class="directory-reporting__filter-head">
            <h3 class="directory-reporting__filter-title">Filter Records</h3>
            @if ($query !== '' || $status !== '' || $paymentStatus !== '')
                <a href="{{ url()->current() }}" class="btn btn-light btn-sm">Clear Filters</a>
            @endif
        </div>

        <form method="GET" class="listing-filter-form">
            <div class="listing-filter-form__fields listing-filter-form__fields--order">
                <div class="outlet-form-group listing-filter-form__field listing-filter-form__field--search">
                    <label for="q_filter">Search</label>
                    <input id="q_filter" type="text" name="q" class="outlet-input" value="{{ $query }}" placeholder="Search by order no, customer, phone, status...">
                </div>

                <div class="outlet-form-group listing-filter-form__field">
                    <label for="status_filter">Status</label>
                    <select id="status_filter" name="status" class="outlet-input">
                        <option value="">All Statuses</option>
                        @foreach ($statusLabels as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" @selected($status === $statusKey)>{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="outlet-form-group listing-filter-form__field">
                    <label for="payment_status_filter">Payment Status</label>
                    <select id="payment_status_filter" name="payment_status" class="outlet-input">
                        <option value="">All Payment Statuses</option>
                        @foreach ($paymentStatusLabels as $paymentStatusKey => $paymentStatusLabel)
                            <option value="{{ $paymentStatusKey }}" @selected($paymentStatus === $paymentStatusKey)>{{ $paymentStatusLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="listing-filter-form__actions">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="{{ url()->current() }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

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
                    <th>Task Worker</th>
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
                        $taskWorkers = $order->tasks
                            ->pluck('worker.name')
                            ->filter()
                            ->unique()
                            ->values();
                        $taskDeadline = $order->tasks
                            ->pluck('worker_deadline_at')
                            ->filter()
                            ->sort()
                            ->first();
                        $isOwnAssignedOrder = $order->tasks
                            ->contains(fn ($task) => (int) ($task->worker_id ?? 0) === (int) ($authUser?->id ?? 0));
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
                    @endphp
                    <tr>
                        <td>
                            <div>
                                @canany(['view-orders', 'manage-orders'])
                                    <a href="{{ route('order.show', $order) }}">{{ $order->order_number }}</a>
                                @else
                                    {{ $order->order_number }}
                                @endcanany
                            </div>
                            <small>{{ $order->outlet?->name ?: '-' }}</small>
                        </td>
                        <td>{{ $order->ordered_at?->format('M d, Y h:i A') ?: '-' }}</td>
                        <td>
                            @if ($order->customer)
                                @canany(['view-customers', 'manage-customers'])
                                    <a href="{{ route('customer.show', $order->customer) }}">{{ $order->customer->name }}</a>
                                @else
                                    {{ $order->customer->name }}
                                @endcanany
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
                            <div>
                                @if ($order->tasks->isNotEmpty())
                                    @foreach ($order->tasks->filter(fn ($task) => $task->worker)->unique('worker_id')->values() as $taskWorker)
                                        @canany(['view-task-management', 'manage-task-management', 'manage-orders'])
                                            <a href="{{ route('worker.tasks', $taskWorker->worker) }}">{{ $taskWorker->worker->name }}</a>@if (! $loop->last), @endif
                                        @else
                                            {{ $taskWorker->worker->name }}@if (! $loop->last), @endif
                                        @endcanany
                                    @endforeach
                                    @if ($order->tasks->filter(fn ($task) => $task->worker)->isEmpty())
                                        -
                                    @endif
                                @else
                                    -
                                @endif
                            </div>
                            <small>Deadline: {{ $taskDeadline?->format('M d, Y h:i A') ?: '-' }}</small>
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
                                        @if ($canManageOrders || $canViewOrders || ($canViewAssignedJobs && $isOwnAssignedOrder))
                                            <a href="{{ route('order.show', $order) }}" class="order-actions-link">View</a>
                                        @endif
                                        @if (($canManageOrders || $authUser?->hasPermission('create-orders')) && $isEditable)
                                            <a href="{{ route('order.edit', $order) }}" class="order-actions-link">Edit</a>
                                        @endif
                                        @if ($canManageOrders || $canViewOrders)
                                            <a href="{{ route('order.bill.customer', $order) }}" target="_blank" class="order-actions-link">Customer Bill</a>
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
                        <td colspan="9" class="empty">No orders found.</td>
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
    .listing-filter-form {
        display: grid;
        gap: 14px;
    }

    .listing-filter-form__fields {
        display: grid;
        grid-template-columns: minmax(280px, 1.4fr) repeat(3, minmax(200px, 1fr));
        gap: 12px;
        align-items: end;
    }

    .listing-filter-form__fields--order {
        grid-template-columns: minmax(320px, 1.6fr) repeat(2, minmax(220px, 1fr));
    }

    .listing-filter-form__field {
        margin-bottom: 0;
    }

    .listing-filter-form__actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    @media (max-width: 768px) {
        .listing-filter-form__fields,
        .listing-filter-form__fields--order {
            grid-template-columns: 1fr;
        }

        .listing-filter-form__actions {
            flex-direction: column;
        }

        .listing-filter-form__actions .btn {
            width: 100%;
        }
    }

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
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1100;
        width: 280px;
        max-height: min(420px, 70vh);
        overflow-y: auto;
        padding: 14px;
        border: 1px solid #d7dfeb;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
    }

    .order-actions-menu.open-up .order-actions-dropdown {
        top: auto;
    }

    .order-actions-menu.open-down .order-actions-dropdown {
        bottom: auto;
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
        const viewportGap = 16;

        const positionActionMenu = (menu) => {
            const dropdown = menu.querySelector('.order-actions-dropdown');
            const toggle = menu.querySelector('.order-actions-toggle');
            if (!dropdown) {
                return;
            }

            menu.classList.remove('open-up', 'open-down');
            dropdown.style.maxHeight = '';
            dropdown.style.top = '';
            dropdown.style.bottom = '';

            if (!toggle) {
                return;
            }

            const summaryRect = toggle.getBoundingClientRect();
            const dropdownRect = dropdown.getBoundingClientRect();
            const availableBelow = window.innerHeight - summaryRect.bottom - viewportGap;
            const availableAbove = summaryRect.top - viewportGap;
            const shouldOpenUp = availableBelow < Math.min(dropdownRect.height, 320) && availableAbove > availableBelow;
            const availableHeight = Math.max(180, shouldOpenUp ? availableAbove - 8 : availableBelow - 8);
            const desiredLeft = summaryRect.right - dropdownRect.width;
            const maxLeft = window.innerWidth - dropdownRect.width - viewportGap;
            const resolvedLeft = Math.max(viewportGap, Math.min(desiredLeft, maxLeft));

            menu.classList.add(shouldOpenUp ? 'open-up' : 'open-down');
            dropdown.style.maxHeight = `${availableHeight}px`;
            dropdown.style.left = `${resolvedLeft}px`;

            if (shouldOpenUp) {
                dropdown.style.bottom = `${window.innerHeight - summaryRect.top + 8}px`;
            } else {
                dropdown.style.top = `${summaryRect.bottom + 8}px`;
            }
        };

        actionMenus.forEach((menu) => {
            menu.addEventListener('toggle', () => {
                if (!menu.open) {
                    menu.classList.remove('open-up', 'open-down');
                    return;
                }

                actionMenus.forEach((otherMenu) => {
                    if (otherMenu !== menu) {
                        otherMenu.open = false;
                    }
                });

                positionActionMenu(menu);
            });
        });

        const repositionOpenMenus = () => {
            actionMenus.forEach((menu) => {
                if (menu.open) {
                    positionActionMenu(menu);
                }
            });
        };

        window.addEventListener('resize', repositionOpenMenus);
        window.addEventListener('scroll', repositionOpenMenus, true);

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

            if (!statusSelect) {
                return;
            }

            const toggleFields = () => {
                const selected = statusSelect.value;
                const isDelivered = selected === '{{ \App\Models\Order::STATUS_DELIVERED }}';

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
