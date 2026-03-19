@extends('layouts.app')

@section('title', 'Assigned Jobs')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Assigned Jobs</h1>
        <p>Update the status of your assigned tasks here.</p>
    </div>
</div>

@include('includes.reporting-filter', ['paginator' => $tasks, 'placeholder' => 'Search by task no, order no, garment, customer...', 'reporting' => $reporting])

<div class="table-card">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="table-header">
        <div class="table-title">My Tasks</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Task No</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Garment</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th>Slip</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tasks as $task)
                    @php
                        $nextStatuses = match ((string) $task->status) {
                            \App\Models\OrderTask::STATUS_ASSIGNED => [\App\Models\OrderTask::STATUS_IN_PROGRESS, \App\Models\OrderTask::STATUS_COMPLETED],
                            \App\Models\OrderTask::STATUS_IN_PROGRESS => [\App\Models\OrderTask::STATUS_COMPLETED],
                            default => [],
                        };
                    @endphp
                    <tr>
                        <td>{{ $task->task_number ?: '-' }}</td>
                        <td>
                            <div>{{ $task->order?->order_number ?: '-' }}</div>
                            <small>{{ $task->order?->outlet?->name ?: '-' }}</small>
                        </td>
                        <td>
                            {{ $task->order?->customer?->name ?: '-' }}
                            @if ($task->order?->customer?->phone)
                                <div><small>{{ $task->order->customer->phone }}</small></div>
                            @endif
                        </td>
                        <td>{{ $task->task_title }}</td>
                        <td>{{ $task->worker_deadline_at?->format('M d, Y h:i A') ?: ($task->order?->delivery_due_at?->format('M d, Y h:i A') ?: '-') }}</td>
                        <td>{{ $task->statusLabel() }}</td>
                        <td>
                            <a href="{{ route('taskManagement.slip', $task) }}" class="btn btn-sm btn-light" target="_blank">Print Slip</a>
                        </td>
                        <td>
                            @if ($nextStatuses !== [])
                                <form action="{{ route('taskManagement.workerUpdate', $task) }}" method="POST" style="display:flex; gap:8px; align-items:center;">
                                    @csrf
                                    @method('PUT')
                                    <select name="status" class="outlet-input" style="min-width: 180px;" required>
                                        <option value="" disabled selected>Update Status</option>
                                        @foreach ($nextStatuses as $status)
                                            <option value="{{ $status }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-secondary">Save</button>
                                </form>
                            @else
                                <span class="empty">Locked</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty">No assigned tasks found.</td>
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
