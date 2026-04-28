@extends('layouts.app')

@section('title', 'Worker Tasks')

@section('page-specific-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
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

    .listing-filter-form__fields--worker-task {
        grid-template-columns: minmax(320px, 1.5fr) minmax(180px, 1fr) minmax(320px, 1.2fr);
    }

    .listing-filter-form__field {
        margin-bottom: 0;
    }

    .date-range-picker-input {
        cursor: pointer;
        background: #fff;
    }

    .listing-filter-form__actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    @media (max-width: 768px) {
        .listing-filter-form__fields,
        .listing-filter-form__fields--worker-task {
            grid-template-columns: 1fr;
        }

        .listing-filter-form__actions {
            flex-direction: column;
        }

        .listing-filter-form__actions .btn {
            width: 100%;
        }
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">{{ $worker->name }} Tasks</h1>
        <p>Track this worker's assigned tasks with status-wise reporting.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('worker.index') }}" class="btn btn-light">Back to Workers</a>
    </div>
</div>

@php
    $query = trim((string) request('q', ''));
    $authUser = auth()->user();
    $canManageOrders = (bool) $authUser?->hasPermission('manage-orders');
    $canManageTaskManagement = (bool) $authUser?->hasPermission('manage-task-management');
    $canMarkTaskPaid = $canManageOrders || $canManageTaskManagement;
@endphp

<div class="stats-grid" style="margin-bottom: 16px;">
    <div class="stat-card"><div class="stat-label">Assigned</div><div class="stat-value">{{ number_format((int) $reporting['assigned']) }}</div></div>
    <div class="stat-card"><div class="stat-label">In Progress</div><div class="stat-value">{{ number_format((int) $reporting['in_progress']) }}</div></div>
    <div class="stat-card"><div class="stat-label">Completed</div><div class="stat-value">{{ number_format((int) $reporting['completed']) }}</div></div>
    <div class="stat-card"><div class="stat-label">Cancelled</div><div class="stat-value">{{ number_format((int) $reporting['cancelled']) }}</div></div>
    <div class="stat-card"><div class="stat-label">Total Payable</div><div class="stat-value">{{ number_format((float) $reporting['total_payable'], 2) }}</div></div>
    <div class="stat-card"><div class="stat-label">Total Paid</div><div class="stat-value">{{ number_format((float) $reporting['total_paid'], 2) }}</div></div>
</div>

<div class="directory-reporting" style="margin-bottom: 16px;">
    <div class="directory-reporting__filter-bar">
        <div class="directory-reporting__filter-head">
            <h3 class="directory-reporting__filter-title">Filter Records</h3>
            @if ($query !== '' || $selectedStatus !== '' || $selectedDeadlineFrom !== '' || $selectedDeadlineTo !== '')
                <a href="{{ route('worker.tasks', $worker) }}" class="btn btn-light btn-sm">Clear Filters</a>
            @endif
        </div>

        <form method="GET" class="listing-filter-form">
            <div class="listing-filter-form__fields listing-filter-form__fields--worker-task">
                <div class="outlet-form-group listing-filter-form__field listing-filter-form__field--search">
                    <label for="q_filter">Search</label>
                    <input id="q_filter" type="text" name="q" class="outlet-input" value="{{ $query }}" placeholder="Search by task no, order no, garment, customer...">
                </div>

                <div class="outlet-form-group listing-filter-form__field">
                    <label for="status_filter">Status</label>
                    <select id="status_filter" name="status" class="outlet-input">
                        <option value="">All Statuses</option>
                        @foreach ($statusLabels as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" @selected($selectedStatus === $statusKey)>{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="outlet-form-group listing-filter-form__field listing-filter-form__field--range">
                    <label for="deadline_range_filter">Deadline Range</label>
                    <input
                        id="deadline_range_filter"
                        type="text"
                        class="outlet-input date-range-picker-input"
                        value="{{ $selectedDeadlineFrom !== '' && $selectedDeadlineTo !== '' ? $selectedDeadlineFrom . ' - ' . $selectedDeadlineTo : '' }}"
                        placeholder="Select deadline range"
                        autocomplete="off"
                    >
                    <input id="deadline_from_filter" type="hidden" name="deadline_from" value="{{ $selectedDeadlineFrom }}">
                    <input id="deadline_to_filter" type="hidden" name="deadline_to" value="{{ $selectedDeadlineTo }}">
                </div>
            </div>

            <div class="listing-filter-form__actions">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="{{ route('worker.tasks', $worker) }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="table-card">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="table-header">
        <div class="table-title">Worker Tasks</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Task No</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Garment</th>
                    <th>Qty</th>
                    <th>Payable</th>
                    <th>Status</th>
                    <th>Paid</th>
                    <th>Deadline</th>
                    <th>Slip</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tasks as $task)
                    @php
                        $canEditTaskDeadline = $canManageOrders || $canManageTaskManagement;
                    @endphp
                    <tr>
                        <td>{{ $task->task_number ?: '-' }}</td>
                        <td>
                            <div>
                                @if ($task->order)
                                    @canany(['view-orders', 'manage-orders'])
                                        <a href="{{ route('order.show', $task->order) }}" style="text-decoration: underline;">{{ $task->order->order_number }}</a>
                                    @else
                                        {{ $task->order->order_number }}
                                    @endcanany
                                @else
                                    -
                                @endif
                            </div>
                            <small>Due: {{ $task->order?->delivery_due_at?->format('M d, Y h:i A') ?: '-' }}</small>
                        </td>
                        <td>
                            @if ($task->order?->customer)
                                @canany(['view-customers', 'manage-customers'])
                                    <a href="{{ route('customer.show', $task->order->customer) }}" style="text-decoration: underline;">{{ $task->order->customer->name }}</a>
                                @else
                                    {{ $task->order->customer->name }}
                                @endcanany
                            @else
                                -
                            @endif
                            @if ($task->order?->customer?->phone)
                                <div><small>{{ $task->order->customer->phone }}</small></div>
                            @endif
                        </td>
                        <td>{{ $task->task_title }}</td>
                        <td>{{ number_format((float) $task->quantity, 2) }}</td>
                        <td>{{ number_format((float) $task->payable_amount, 2) }}</td>
                        <td>
                            <span class="app-badge {{ \App\Models\OrderTask::statusBadgeClass((string) $task->status) }}">
                                {{ $task->statusLabel() }}
                            </span>
                        </td>
                        <td>{{ $task->is_paid ? 'Yes' : 'No' }}</td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <span>{{ $task->worker_deadline_at?->format('M d, Y h:i A') ?: '-' }}</span>
                                @if ($canEditTaskDeadline)
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-light"
                                        data-worker-task-deadline-toggle
                                        aria-label="Edit task deadline"
                                        title="Edit task deadline"
                                        style="width:26px; height:26px; border-radius:999px; padding:0; display:inline-flex; align-items:center; justify-content:center;"
                                    >
                                        <i class="fas fa-pen"></i>
                                    </button>
                                @endif
                            </div>
                            @if ($canEditTaskDeadline)
                                <form action="{{ route('taskManagement.update', $task) }}" method="POST" class="worker-task-deadline-form" style="display:none; margin-top:8px; align-items:flex-end; gap:8px; flex-wrap:wrap;">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="worker_id" value="{{ (int) ($task->worker_id ?? 0) }}">
                                    <input type="hidden" name="notes" value="{{ $task->notes }}">
                                    <input type="hidden" name="slip_received" value="{{ $task->slip_received_at ? '1' : '0' }}">
                                    <input type="hidden" name="is_paid" value="{{ $task->is_paid ? '1' : '0' }}">
                                    <select name="status" class="outlet-input" style="min-width:180px;">
                                        @foreach ($statusLabels as $statusKey => $statusLabel)
                                            <option value="{{ $statusKey }}" @selected((string) $task->status === (string) $statusKey)>{{ $statusLabel }}</option>
                                        @endforeach
                                    </select>
                                    <input
                                        type="datetime-local"
                                        name="worker_deadline_at"
                                        class="outlet-input"
                                        value="{{ $task->worker_deadline_at?->format('Y-m-d\\TH:i') }}"
                                        style="min-width:220px;"
                                    >
                                    <button type="submit" class="btn btn-sm btn-secondary">Save</button>
                                    <button type="button" class="btn btn-sm btn-light" data-worker-task-deadline-cancel>Cancel</button>
                                </form>
                            @endif
                        </td>
                        <td>
                            <div><small>Slip: {{ $task->slip_received_at ? 'Received' : 'Pending' }}</small></div>
                            <div style="margin-top: 8px;">
                                <a href="{{ route('taskManagement.slip', $task) }}" class="btn btn-sm btn-light" target="_blank">Print Slip</a>
                            </div>
                        </td>
                        <td>
                            @if (! $task->is_paid && $canMarkTaskPaid)
                                <form action="{{ route('taskManagement.update', $task) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="worker_id" value="{{ (int) ($task->worker_id ?? 0) }}">
                                    <input type="hidden" name="notes" value="{{ $task->notes }}">
                                    <input type="hidden" name="slip_received" value="1">
                                    <input type="hidden" name="is_paid" value="1">
                                    <input type="hidden" name="status" value="{{ $task->status }}">
                                    <input type="hidden" name="worker_deadline_at" value="{{ $task->worker_deadline_at?->format('Y-m-d H:i:s') }}">
                                    <button type="submit" class="btn btn-sm btn-primary">Mark as Paid</button>
                                </form>
                            @elseif ($task->is_paid && $canMarkTaskPaid)
                                <form action="{{ route('taskManagement.update', $task) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="worker_id" value="{{ (int) ($task->worker_id ?? 0) }}">
                                    <input type="hidden" name="notes" value="{{ $task->notes }}">
                                    <input type="hidden" name="slip_received" value="{{ $task->slip_received_at ? '1' : '0' }}">
                                    <input type="hidden" name="is_paid" value="0">
                                    <input type="hidden" name="status" value="{{ $task->status }}">
                                    <input type="hidden" name="worker_deadline_at" value="{{ $task->worker_deadline_at?->format('Y-m-d H:i:s') }}">
                                    <button type="submit" class="btn btn-sm btn-light">Mark as Unpaid</button>
                                </form>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="empty">No tasks found for this worker.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($tasks->hasPages())
        <div class="pagination">
            {{ $tasks->links() }}
        </div>
    @endif
</div>
@endsection

@section('page-specific-script')
<script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/min/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    (() => {
        const rangeInput = document.getElementById('deadline_range_filter');
        const fromInput = document.getElementById('deadline_from_filter');
        const toInput = document.getElementById('deadline_to_filter');

        if (!rangeInput || !fromInput || !toInput || !window.jQuery || !window.jQuery.fn?.daterangepicker) {
            return;
        }

        const options = {
            autoUpdateInput: false,
            alwaysShowCalendars: true,
            locale: {
                cancelLabel: 'Clear',
                format: 'YYYY-MM-DD',
            },
        };

        if (fromInput.value && toInput.value) {
            options.startDate = fromInput.value;
            options.endDate = toInput.value;
            rangeInput.value = `${fromInput.value} - ${toInput.value}`;
        }

        window.jQuery(rangeInput).daterangepicker(options);

        window.jQuery(rangeInput).on('apply.daterangepicker', function (_event, picker) {
            const start = picker.startDate.format('YYYY-MM-DD');
            const end = picker.endDate.format('YYYY-MM-DD');

            fromInput.value = start;
            toInput.value = end;
            rangeInput.value = `${start} - ${end}`;
        });

        window.jQuery(rangeInput).on('cancel.daterangepicker', function () {
            fromInput.value = '';
            toInput.value = '';
            rangeInput.value = '';
        });

        document.querySelectorAll('[data-worker-task-deadline-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const cell = button.closest('td');
                const form = cell?.querySelector('.worker-task-deadline-form');

                document.querySelectorAll('.worker-task-deadline-form').forEach((otherForm) => {
                    if (otherForm !== form) {
                        otherForm.style.display = 'none';
                    }
                });

                if (form) {
                    form.style.display = form.style.display === 'none' || form.style.display === '' ? 'inline-flex' : 'none';
                }
            });
        });

        document.querySelectorAll('[data-worker-task-deadline-cancel]').forEach((button) => {
            button.addEventListener('click', () => {
                const form = button.closest('.worker-task-deadline-form');
                if (form) {
                    form.style.display = 'none';
                }
            });
        });
    })();
</script>
@endsection
