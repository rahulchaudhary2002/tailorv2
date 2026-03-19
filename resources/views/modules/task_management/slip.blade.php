@extends('layouts.app')

@section('title', 'Task Assignment Slip')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Task Assignment Slip</h1>
        <p>Printable task slip for {{ $task->task_number }}.</p>
    </div>
</div>

<div class="table-card bill-wrap">
    <div class="bill-actions">
        <button type="button" class="btn btn-secondary" onclick="window.print()">Print</button>
    </div>

    <div class="bill-card">
        <div class="bill-head">
            <div>
                <h2 class="bill-title">Task Slip</h2>
                <div class="bill-muted">Task No: {{ $task->task_number }}</div>
            </div>
            <div class="bill-muted">
                <div>Order No: {{ $task->order?->order_number ?: '-' }}</div>
                <div>Outlet: {{ $task->order?->outlet?->name ?: '-' }}</div>
            </div>
        </div>

        <div class="bill-grid">
            <div class="bill-grid-item"><span class="bill-grid-label">Customer:</span> {{ $task->order?->customer?->name ?: '-' }}</div>
            <div class="bill-grid-item"><span class="bill-grid-label">Phone:</span> {{ $task->order?->customer?->phone ?: '-' }}</div>
            <div class="bill-grid-item"><span class="bill-grid-label">Worker:</span> {{ $task->worker?->name ?: 'Unassigned' }}</div>
            <div class="bill-grid-item"><span class="bill-grid-label">Deadline:</span> {{ $task->worker_deadline_at?->format('M d, Y h:i A') ?: ($task->order?->delivery_due_at?->format('M d, Y h:i A') ?: '-') }}</div>
            <div class="bill-grid-item"><span class="bill-grid-label">Garment:</span> {{ $task->task_title }}</div>
            <div class="bill-grid-item"><span class="bill-grid-label">Payable:</span> {{ number_format((float) $task->payable_amount, 2) }}</div>
        </div>
    </div>

    <div class="bill-card">
        <h3 class="bill-title" style="font-size:18px;">Measurement Details</h3>
        <table class="bill-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Measurement</th>
                    <th>Unit</th>
                </tr>
            </thead>
            <tbody>
                @forelse ((array) ($garment['measurements'] ?? []) as $measurement)
                    <tr>
                        <td>{{ $measurement['type'] ?? '-' }}</td>
                        <td>{{ $measurement['measurement'] ?? '-' }}</td>
                        <td>{{ $measurement['unit'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">No measurements captured.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bill-card">
        <h3 class="bill-title" style="font-size:18px;">Task Notes</h3>
        <div class="bill-muted">Tailoring Package: {{ $garment['tailoring_package'] ?? '-' }}</div>
        <div class="bill-muted">Design Note: {{ data_get($customDetails, 'design_note', '-') ?: '-' }}</div>
        <div class="bill-muted">Slip Required For Payment: Yes</div>
    </div>
</div>
@endsection

@section('page-specific-style')
@include('modules.order.bills.partials.style')
@endsection
