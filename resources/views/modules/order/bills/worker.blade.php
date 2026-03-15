@extends('layouts.app')

@section('title', 'Worker Job Slip')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Worker Bill / Job Slip</h1>
        <p>Worker-facing job details for order {{ $order->order_number }}.</p>
    </div>
</div>

<div class="table-card bill-wrap">
    <div class="bill-actions">
        <button type="button" class="btn btn-secondary" onclick="window.print()">Print</button>
    </div>

    <div class="bill-card">
        <div class="bill-head">
            <div>
                <h2 class="bill-title">Worker Bill / Job Slip</h2>
                <div class="bill-muted">Job ID: {{ $order->order_number }}</div>
            </div>
            <div class="bill-muted">
                <div>Order Date: {{ $order->ordered_at?->format('M d, Y h:i A') ?: '-' }}</div>
                <div>Outlet: {{ $order->outlet?->name ?: '-' }}</div>
            </div>
        </div>

        @php
            $workTypes = $customItems
                ->flatMap(function ($item) {
                    $garments = collect((array) data_get($item->custom_details, 'garments', []))
                        ->pluck('garment_title')
                        ->filter();

                    if ($garments->isNotEmpty()) {
                        return $garments;
                    }

                    return [trim((string) data_get($item->custom_details, 'garment_title', ''))];
                })
                ->filter()
                ->unique()
                ->values();
        @endphp

        <div class="bill-grid">
            <div class="bill-grid-item"><span class="bill-grid-label">Worker Name:</span> {{ $order->worker?->name ?: 'Unassigned' }}</div>
            <div class="bill-grid-item"><span class="bill-grid-label">Deadline:</span> {{ $order->worker_deadline_at?->format('M d, Y h:i A') ?: '-' }}</div>
            <div class="bill-grid-item"><span class="bill-grid-label">Fabric Issued:</span> {{ $order->fabric_issued_at?->format('M d, Y h:i A') ?: '-' }}</div>
            <div class="bill-grid-item"><span class="bill-grid-label">Work Type:</span> {{ $workTypes->isNotEmpty() ? $workTypes->implode(', ') : 'General Tailoring' }}</div>
            <div class="bill-grid-item"><span class="bill-grid-label">Payment Amount:</span> {{ number_format($workerPayment, 2) }}</div>
            <div class="bill-grid-item"><span class="bill-grid-label">Status:</span> {{ \App\Models\Order::statusLabel((string) $order->status) }}</div>
        </div>
    </div>

    <div class="bill-card">
        <h3 class="bill-title" style="font-size:18px;">Fabric Issued Details</h3>
        <table class="bill-table">
            <thead>
                <tr>
                    <th>Garment / Item</th>
                    <th>Fabric Source</th>
                    <th>Quantity</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($fabricItems as $item)
                    @if ((string) $item->item_category === 'custom')
                        @php
                            $customName = data_get($item->custom_details, 'garment_title')
                                ?: (collect((array) data_get($item->custom_details, 'garments', []))->pluck('garment_title')->filter()->implode(', ') ?: 'Custom Garment');
                        @endphp
                        <tr>
                            <td>{{ $customName }}</td>
                            <td>{{ ucfirst((string) data_get($item->custom_details, 'fabric_source', 'own')) }}</td>
                            <td>{{ number_format((float) data_get($item->custom_details, 'fabric_quantity', 0), 2) }} {{ data_get($item->custom_details, 'fabric_quantity_unit', '') }}</td>
                            <td>{{ data_get($item->custom_details, 'design_note', '-') ?: '-' }}</td>
                        </tr>
                    @else
                        <tr>
                            <td>{{ $item->product?->name ?: '-' }}</td>
                            <td>Stock</td>
                            <td>{{ number_format((float) $item->quantity, 2) }} {{ (string) $item->item_category === 'fabric' ? 'm' : 'pcs' }}</td>
                            <td>Direct fabric item</td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="4">No fabric issue information available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bill-card">
        <h3 class="bill-title" style="font-size:18px;">Measurement Details</h3>
        @forelse ($customItems as $customItem)
            @php
                $garments = collect((array) data_get($customItem->custom_details, 'garments', []));
                $fallbackMeasurements = (array) data_get($customItem->custom_details, 'measurements', []);
                $displayName = data_get($customItem->custom_details, 'garment_title')
                    ?: ($garments->pluck('garment_title')->filter()->implode(', ') ?: 'Custom Garment');
            @endphp

            <div class="bill-muted" style="margin-top:10px;">
                {{ $displayName }}
            </div>

            @if ($garments->isNotEmpty())
                @foreach ($garments as $garment)
                    <div class="bill-detail-box" style="margin-top:10px;">
                        <div class="bill-detail-row" style="padding-top:0;">
                            <div>
                                <div class="bill-detail-name">
                                    {{ $garment['garment_title'] ?? 'Garment' }} x {{ number_format((float) ($garment['quantity'] ?? 1), 2) }}
                                </div>
                                <div class="bill-detail-meta">
                                    {{ $garment['tailoring_package'] ?? 'Tailoring' }}
                                </div>
                            </div>
                            <div class="bill-detail-price">
                                Stitching
                            </div>
                        </div>

                        <table class="bill-table" style="margin-top:10px;">
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
                @endforeach
            @else
                <table class="bill-table" style="margin-top:10px;">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Measurement</th>
                            <th>Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($fallbackMeasurements as $measurement)
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
            @endif
        @empty
            <div class="bill-muted">No measurement details available for this order.</div>
        @endforelse
    </div>

    <div class="bill-card">
        <h3 class="bill-title" style="font-size:18px;">Design References</h3>
        @forelse ($customItems as $customItem)
            @php
                $designImages = collect((array) data_get($customItem->custom_details, 'design_images', []))
                    ->push(data_get($customItem->custom_details, 'design_image'))
                    ->filter(fn ($path) => filled($path))
                    ->unique()
                    ->values();
            @endphp
            <div class="bill-muted" style="margin-top:10px;">
                {{ data_get($customItem->custom_details, 'garment_title')
                    ?: (collect((array) data_get($customItem->custom_details, 'garments', []))->pluck('garment_title')->filter()->implode(', ') ?: 'Custom Garment') }}
            </div>
            @if ($designImages->isNotEmpty())
                <div class="bill-design-grid" style="margin-top:6px;">
                    @foreach ($designImages as $imagePath)
                        @php
                            $imagePath = (string) $imagePath;
                            $imageUrl = str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')
                                ? $imagePath
                                : \Illuminate\Support\Facades\Storage::disk('public')->url(ltrim($imagePath, '/'));
                        @endphp
                        <a href="{{ $imageUrl }}" target="_blank" rel="noopener">
                            <img src="{{ $imageUrl }}" alt="Design Image" class="bill-design-thumb">
                        </a>
                    @endforeach
                </div>
            @else
                <div class="bill-muted">No design image attached.</div>
            @endif
        @empty
            <div class="bill-muted">No custom item design references for this order.</div>
        @endforelse
    </div>
</div>
@endsection

@section('page-specific-style')
@include('modules.order.bills.partials.style')
@endsection
