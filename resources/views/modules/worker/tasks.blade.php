@extends('layouts.app')

@section('title', 'Worker Tasks')

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
@endphp

<div class="stats-grid" style="margin-bottom: 16px;">
    <div class="stat-card"><div class="stat-label">Assigned</div><div class="stat-value">{{ number_format((int) $reporting['assigned']) }}</div></div>
    <div class="stat-card"><div class="stat-label">In Progress</div><div class="stat-value">{{ number_format((int) $reporting['in_progress']) }}</div></div>
    <div class="stat-card"><div class="stat-label">Completed</div><div class="stat-value">{{ number_format((int) $reporting['completed']) }}</div></div>
    <div class="stat-card"><div class="stat-label">Cancelled</div><div class="stat-value">{{ number_format((int) $reporting['cancelled']) }}</div></div>
    <div class="stat-card"><div class="stat-label">Total Payable</div><div class="stat-value">{{ number_format((float) $reporting['total_payable'], 2) }}</div></div>
</div>

<div class="directory-reporting" style="margin-bottom: 16px;">
    <div class="directory-reporting__filter-bar">
        <div class="directory-reporting__filter-head">
            <h3 class="directory-reporting__filter-title">Filter Records</h3>
            @if ($query !== '' || $selectedStatus !== '')
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
                    <th>Deadline</th>
                    <th>Slip</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tasks as $task)
                    <tr>
                        <td>{{ $task->task_number ?: '-' }}</td>
                        <td>
                            <div>{{ $task->order?->order_number ?: '-' }}</div>
                            <small>Due: {{ $task->order?->delivery_due_at?->format('M d, Y h:i A') ?: '-' }}</small>
                        </td>
                        <td>
                            {{ $task->order?->customer?->name ?: '-' }}
                            @if ($task->order?->customer?->phone)
                                <div><small>{{ $task->order->customer->phone }}</small></div>
                            @endif
                        </td>
                        <td>{{ $task->task_title }}</td>
                        <td>{{ number_format((float) $task->quantity, 2) }}</td>
                        <td>{{ number_format((float) $task->payable_amount, 2) }}</td>
                        <td>
                            <span class="task-status-badge task-status-{{ str_replace('_', '-', (string) $task->status) }}">
                                {{ $task->statusLabel() }}
                            </span>
                        </td>
                        <td>{{ $task->worker_deadline_at?->format('M d, Y h:i A') ?: '-' }}</td>
                        <td>
                            <div><small>Slip: {{ $task->slip_received_at ? 'Received' : 'Pending' }}</small></div>
                            <div style="margin-top: 8px;">
                                <a href="{{ route('taskManagement.slip', $task) }}" class="btn btn-sm btn-light" target="_blank">Print Slip</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="empty">No tasks found for this worker.</td>
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
        grid-template-columns: minmax(320px, 1.7fr) minmax(240px, 1fr);
    }

    .listing-filter-form__field {
        margin-bottom: 0;
    }

    .listing-filter-form__actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .task-status-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 600;
        line-height: 1;
        white-space: nowrap;
    }

    .task-status-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .task-status-assigned {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .task-status-in-progress {
        background: #e0f2fe;
        color: #0369a1;
    }

    .task-status-completed {
        background: #dcfce7;
        color: #166534;
    }

    .task-status-cancelled {
        background: #fee2e2;
        color: #b91c1c;
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
