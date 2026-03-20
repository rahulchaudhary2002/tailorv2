@extends('layouts.app')

@section('title', 'Task Assignment Slip')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Task Assignment Slip</h1>
        <p>Printable task slip for {{ $task->task_number }}.</p>
    </div>
</div>

<div class="bill-wrap">
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
        @php
            $garmentDesignNotes = collect((array) ($garment['design_note'] ?? []))
                ->map(fn ($note) => trim((string) $note))
                ->filter()
                ->values();
            $designNoteText = $garmentDesignNotes->isNotEmpty()
                ? $garmentDesignNotes->implode(', ')
                : (data_get($customDetails, 'design_note', '-') ?: '-');
            $designImages = collect((array) ($garment['design_images'] ?? []))
                ->push($garment['design_image'] ?? null)
                ->when(function ($collection) {
                    return $collection->filter(fn ($path) => filled($path))->isEmpty();
                }, function ($collection) use ($customDetails) {
                    return $collection
                        ->concat((array) data_get($customDetails, 'design_images', []))
                        ->push(data_get($customDetails, 'design_image'));
                })
                ->filter(fn ($path) => filled($path))
                ->unique()
                ->values();
        @endphp
        <div class="bill-muted">Tailoring Package: {{ $garment['tailoring_package'] ?? '-' }}</div>
        <div class="bill-muted">Design Note: {{ $designNoteText }}</div>
        <div class="bill-muted">Slip Required For Payment: Yes</div>
    </div>

    <div class="bill-card">
        <h3 class="bill-title" style="font-size:18px;">Design Sample</h3>
        @if ($designImages->isNotEmpty())
            <div class="bill-design-grid" style="margin-top:6px;">
                @foreach ($designImages as $imagePath)
                    @php
                        $imagePath = (string) $imagePath;
                        $imageUrl = str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')
                            ? $imagePath
                            : \Illuminate\Support\Facades\Storage::url(ltrim($imagePath, '/'));
                    @endphp
                    <a href="{{ $imageUrl }}" target="_blank" rel="noopener">
                        <img src="{{ $imageUrl }}" alt="Design Sample" class="bill-design-thumb">
                    </a>
                @endforeach
            </div>
        @else
            <div class="bill-muted">No design sample attached.</div>
        @endif
    </div>
</div>
@endsection

@section('page-specific-style')
@include('modules.order.bills.partials.style')
@endsection
