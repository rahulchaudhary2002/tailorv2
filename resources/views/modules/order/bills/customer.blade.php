@extends('layouts.app')

@section('title', 'Customer Bill')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Customer Bill</h1>
        <p>Customer-facing bill for order {{ $order->order_number }}.</p>
    </div>
</div>

<div class="table-card bill-wrap">
    <div class="bill-actions">
        <button type="button" class="btn btn-secondary" onclick="window.print()">Print</button>
    </div>

    <div class="bill-card">
        <div class="bill-head">
            <div>
                <h2 class="bill-title">Customer Bill</h2>
                <div class="bill-muted">Order #{{ $order->order_number }}</div>
            </div>
            <div class="bill-muted">
                <div>Date: {{ $order->ordered_at?->format('M d, Y h:i A') ?: '-' }}</div>
                <div>Outlet: {{ $order->outlet?->name ?: '-' }}</div>
            </div>
        </div>

        <div class="bill-grid">
            <div class="bill-grid-item"><span class="bill-grid-label">Customer:</span> {{ $order->customer?->name ?: 'Walk-in' }}</div>
            <div class="bill-grid-item"><span class="bill-grid-label">Phone:</span> {{ $order->customer?->phone ?: '-' }}</div>
            <div class="bill-grid-item"><span class="bill-grid-label">Email:</span> {{ $order->customer?->email ?: '-' }}</div>
            <div class="bill-grid-item"><span class="bill-grid-label">Delivery Due:</span> {{ $order->delivery_due_at?->format('M d, Y h:i A') ?: '-' }}</div>
        </div>

        <table class="bill-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @php
                        $isCustom = (string) $item->item_category === 'custom';
                        $itemName = $isCustom
                            ? (data_get($item->custom_details, 'garment_title') ?: 'Custom Garment')
                            : ($item->product?->name ?: 'Product');
                    @endphp
                    <tr>
                        <td>{{ $itemName }}</td>
                        <td>{{ ucfirst((string) $item->item_category) }}</td>
                        <td class="bill-right">{{ number_format((float) $item->quantity, 2) }} {{ $item->unit?->symbol ?: data_get($item->custom_details, 'quantity_unit', '') }}</td>
                        <td class="bill-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="bill-right">{{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bill-card">
        <h3 class="bill-title" style="font-size:18px;">Measurements Reference</h3>
        @forelse ($customItems as $customItem)
            <div class="bill-muted" style="margin-top:10px;">
                {{ data_get($customItem->custom_details, 'garment_title', 'Custom Garment') }}
            </div>
            <table class="bill-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Measurement</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ((array) data_get($customItem->custom_details, 'measurements', []) as $measurement)
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

            @php
                $designImages = collect((array) data_get($customItem->custom_details, 'design_images', []))
                    ->push(data_get($customItem->custom_details, 'design_image'))
                    ->filter(fn ($path) => filled($path))
                    ->unique()
                    ->values();
            @endphp
            @if ($designImages->isNotEmpty())
                <div class="bill-muted" style="margin-top:8px;">Design Images</div>
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
            @endif
        @empty
            <div class="bill-muted">No custom measurement data for this order.</div>
        @endforelse
    </div>

    <div class="bill-card">
        <h3 class="bill-title" style="font-size:18px;">Fabric Details</h3>
        <table class="bill-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Source</th>
                    <th>Fabric/Material</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($fabricItems as $item)
                    @if ((string) $item->item_category === 'custom')
                        @php
                            $source = ucfirst((string) data_get($item->custom_details, 'fabric_source', 'own'));
                            $fabricLabel = data_get($item->custom_details, 'fabric_source') === 'stock'
                                ? ((string) data_get($item->custom_details, 'fabric_product_id') ? 'Stock Fabric' : 'Stock Fabric')
                                : 'Customer Own Fabric';
                        @endphp
                        <tr>
                            <td>{{ data_get($item->custom_details, 'garment_title', 'Custom Garment') }}</td>
                            <td>{{ $source }}</td>
                            <td>{{ $fabricLabel }}</td>
                            <td>{{ number_format((float) data_get($item->custom_details, 'fabric_quantity', 0), 2) }} {{ data_get($item->custom_details, 'fabric_quantity_unit', '') }}</td>
                        </tr>
                    @else
                        <tr>
                            <td>{{ $item->product?->name ?: '-' }}</td>
                            <td>Stock</td>
                            <td>{{ $item->variant?->material ?: ($item->product?->sku ?: '-') }}</td>
                            <td>{{ number_format((float) $item->quantity, 2) }} {{ $item->unit?->symbol ?: '' }}</td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="4">No fabric details available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bill-card">
        <h3 class="bill-title" style="font-size:18px;">Charges, VAT, Discounts, and Payment</h3>
        <div class="bill-kpi">
            <div class="bill-kpi-card"><div class="bill-kpi-label">Subtotal</div><div class="bill-kpi-value">{{ number_format($subtotal, 2) }}</div></div>
            <div class="bill-kpi-card"><div class="bill-kpi-label">Stitching Charges</div><div class="bill-kpi-value">{{ number_format($stitchingCharges, 2) }}</div></div>
            <div class="bill-kpi-card"><div class="bill-kpi-label">VAT (13%)</div><div class="bill-kpi-value">{{ number_format($taxAmount, 2) }}</div></div>
            <div class="bill-kpi-card"><div class="bill-kpi-label">Discount</div><div class="bill-kpi-value">{{ number_format($discount, 2) }}</div></div>
            <div class="bill-kpi-card"><div class="bill-kpi-label">Net Payable</div><div class="bill-kpi-value">{{ number_format($netPayable, 2) }}</div></div>
            <div class="bill-kpi-card"><div class="bill-kpi-label">Paid Amount</div><div class="bill-kpi-value">{{ number_format($paidAmount, 2) }}</div></div>
            <div class="bill-kpi-card"><div class="bill-kpi-label">Due Amount</div><div class="bill-kpi-value">{{ number_format($dueAmount, 2) }}</div></div>
            <div class="bill-kpi-card"><div class="bill-kpi-label">Payment Status</div><div class="bill-kpi-value">{{ ucfirst((string) $order->payment_status) }}</div></div>
        </div>
        <div class="bill-muted" style="margin-top:8px;">Payment Method: {{ $order->payment_method ?: '-' }}</div>
    </div>
</div>
@endsection

@section('page-specific-style')
@include('modules.order.bills.partials.style')
@endsection

@section('page-specific-script')
@if (request()->boolean('autoprint'))
<script>
window.addEventListener('load', function () {
    window.print();
});
</script>
@endif
@endsection
