@extends('layouts.app')

@section('title', 'Payment Management')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Payment Management</h1>
        <p>Track customer receipts and worker task payouts in one place.</p>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 16px;">
    <div class="stat-card"><div class="stat-label">Customer Received</div><div class="stat-value">{{ number_format((float) $reporting['customer_received'], 2) }}</div></div>
    <div class="stat-card"><div class="stat-label">Worker Payable</div><div class="stat-value">{{ number_format((float) $reporting['worker_payable'], 2) }}</div></div>
    <div class="stat-card"><div class="stat-label">Worker Paid</div><div class="stat-value">{{ number_format((float) $reporting['worker_paid'], 2) }}</div></div>
    <div class="stat-card"><div class="stat-label">Worker Due</div><div class="stat-value">{{ number_format((float) $reporting['pending_worker_due'], 2) }}</div></div>
</div>

<div class="table-card" style="margin-bottom: 16px;">
    <div class="table-header">
        <div class="table-title">Customer Receipts</div>
    </div>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Orders</th>
                    <th>Total Payable</th>
                    <th>Received</th>
                    <th>Due</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customerPayments as $row)
                    <tr>
                        <td>{{ $row['customer']?->name ?: 'Walk-in' }}</td>
                        <td>{{ $row['orders'] }}</td>
                        <td>{{ number_format((float) $row['payable'], 2) }}</td>
                        <td>{{ number_format((float) $row['received'], 2) }}</td>
                        <td>{{ number_format((float) $row['due'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">No customer payment records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="table-card" style="margin-bottom: 16px;">
    <div class="table-header">
        <div class="table-title">Worker Payables</div>
    </div>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Worker</th>
                    <th>Tasks</th>
                    <th>Total Payable</th>
                    <th>Paid</th>
                    <th>Due</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($workerPayments as $row)
                    <tr>
                        <td>{{ $row['worker']?->name ?: '-' }}</td>
                        <td>{{ $row['tasks'] }}</td>
                        <td>{{ number_format((float) $row['payable'], 2) }}</td>
                        <td>{{ number_format((float) $row['paid'], 2) }}</td>
                        <td>{{ number_format((float) $row['due'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">No worker payout records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="table-card">
    <div class="table-header">
        <div class="table-title">Pending Worker Payments</div>
    </div>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Order</th>
                    <th>Worker</th>
                    <th>Customer</th>
                    <th>Payable</th>
                    <th>Slip</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payableTasks as $task)
                    <tr>
                        <td>{{ $task->task_title }}</td>
                        <td>{{ $task->order?->order_number ?: '-' }}</td>
                        <td>{{ $task->worker?->name ?: '-' }}</td>
                        <td>{{ $task->order?->customer?->name ?: '-' }}</td>
                        <td>{{ number_format((float) $task->payable_amount, 2) }}</td>
                        <td>{{ $task->slip_received_at ? 'Received' : 'Pending' }}</td>
                        <td>
                            @if (!$task->paid_at)
                                <form action="{{ route('paymentManagement.task.pay', $task) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-secondary">Mark Paid</button>
                                </form>
                            @else
                                Paid
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">No payable worker tasks found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
