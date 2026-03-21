@extends('layouts.app')

@section('title', 'Office Internal Bill')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Office / Internal Bill</h1>
        <p>Internal cost and margin report for order {{ $order->order_number }}.</p>
    </div>
</div>

<div class="bill-wrap">
    @php
        $taskWorkers = $order->tasks->pluck('worker.name')->filter()->unique()->values();
    @endphp
    <div class="bill-actions">
        <button type="button" class="btn btn-secondary" onclick="window.print()">Print</button>
    </div>

    <div class="bill-card">
        <div class="bill-head">
            <div>
                <h2 class="bill-title">Office / Internal Bill</h2>
                <div class="bill-muted">Order #{{ $order->order_number }}</div>
            </div>
            <div class="bill-muted">
                <div>Order Date: {{ $order->ordered_at?->format('M d, Y h:i A') ?: '-' }}</div>
                <div>Customer: {{ $order->customer?->name ?: 'Walk-in' }}</div>
                <div>Task Workers: {{ $taskWorkers->isNotEmpty() ? $taskWorkers->implode(', ') : 'Unassigned' }}</div>
            </div>
        </div>

        <table class="bill-table">
            <thead>
                <tr>
                    <th>Cost Breakdown</th>
                    <th class="bill-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Sales Subtotal</td>
                    <td class="bill-right">{{ number_format($subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td>Discount</td>
                    <td class="bill-right">{{ number_format($discount, 2) }}</td>
                </tr>
                <tr>
                    <td>Net Sales</td>
                    <td class="bill-right">{{ number_format($netPayable, 2) }}</td>
                </tr>
                <tr>
                    <td>Vendor Cost (Inventory Consumption)</td>
                    <td class="bill-right">{{ number_format($vendorCost, 2) }}</td>
                </tr>
                <tr>
                    <td>Worker Payment</td>
                    <td class="bill-right">{{ number_format($workerPayment, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Total Internal Cost</strong></td>
                    <td class="bill-right"><strong>{{ number_format($vendorCost + $workerPayment, 2) }}</strong></td>
                </tr>
                <tr>
                    <td><strong>Profit Margin</strong></td>
                    <td class="bill-right"><strong>{{ number_format($profitMargin, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="bill-card">
        <h3 class="bill-title" style="font-size:18px;">Item Cost References</h3>
        <table class="bill-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th class="bill-right">Qty</th>
                    <th class="bill-right">Sale Unit Price</th>
                    <th class="bill-right">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @php
                        $isCustom = (string) $item->item_category === 'custom';
                        $garments = collect((array) data_get($item->custom_details, 'garments', []));
                        $quantityUnit = $isCustom
                            ? (string) data_get($item->custom_details, 'quantity_unit', 'm')
                            : ((string) $item->item_category === 'fabric' ? 'm' : 'pcs');
                        $fabricProduct = $isCustom
                            ? $customFabricProducts->get((int) data_get($item->custom_details, 'fabric_product_id', 0))
                            : null;
                    @endphp
                    <tr>
                        <td>
                            @if ($isCustom)
                                {{ $fabricProduct?->name ?: 'Custom Fabric' }}
                            @else
                                {{ $item->product?->name ?: '-' }}
                                @if ((string) $item->item_category === 'readymade' && filled(data_get($item->custom_details, 'size')))
                                    (Size: {{ data_get($item->custom_details, 'size') }})
                                @endif
                            @endif
                        </td>
                        <td>{{ ucfirst((string) $item->item_category) }}</td>
                        <td class="bill-right">{{ number_format((float) $item->quantity, 2) }} {{ $quantityUnit }}</td>
                        <td class="bill-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="bill-right">{{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                    @if ($isCustom && $garments->isNotEmpty())
                        <tr>
                            <td colspan="5" style="padding: 12px;">
                                <div class="bill-detail-box">
                                    <div class="bill-detail-grid">
                                        @foreach ($garments as $garment)
                                            @php
                                                $measurementSummary = collect((array) ($garment['measurements'] ?? []))
                                                    ->map(function ($measurement) {
                                                        $type = (string) ($measurement['type'] ?? '');
                                                        $value = (string) ($measurement['measurement'] ?? '');
                                                        $unit = (string) ($measurement['unit'] ?? '');

                                                        return trim($type . ': ' . $value . ($unit !== '' ? ' ' . $unit : ''));
                                                    })
                                                    ->filter()
                                                    ->implode(', ');
                                                $garmentQty = (float) ($garment['quantity'] ?? 0);
                                                $tailoringAmount = (float) ($garment['tailoring_amount'] ?? 0);
                                            @endphp
                                            <div class="bill-detail-row">
                                                <div>
                                                    <div class="bill-detail-name">
                                                        {{ $garment['garment_title'] ?? 'Garment' }}
                                                        x {{ number_format($garmentQty, 2) }}
                                                    </div>
                                                    <div class="bill-detail-meta">
                                                        {{ $garment['tailoring_package'] ?? 'Tailoring' }}
                                                        @if ($measurementSummary !== '')
                                                            | {{ $measurementSummary }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="bill-detail-price">
                                                    {{ number_format($garmentQty * $tailoringAmount, 2) }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @endforeach
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
</div>
@endsection

@section('page-specific-style')
@include('modules.order.bills.partials.style')
@endsection
