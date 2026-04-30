@extends('layouts.app')

@section('title', 'Office Internal Bill')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Office / Internal Bill</h1>
        <p>Internal cost and margin report for order {{ $order->order_number }}.</p>
    </div>
</div>

<div class="bill-wrap pos-receipt-print">
    <div class="bill-actions">
        <button type="button" class="btn btn-secondary" onclick="window.print()">Print</button>
    </div>

    @php
        $printerPhoneNumber = \App\Models\Setting::valueFor('printer_phone_number', '');
        $taskWorkers = $order->tasks->pluck('worker.name')->filter()->unique()->values();
        $formatMoney = function ($amount): string {
            $amount = (float) $amount;
            return abs($amount - round($amount)) < 0.005
                ? number_format($amount, 0)
                : number_format($amount, 2);
        };
    @endphp

    <div class="bill-card bill-receipt">

        <div class="bill-receipt-header">
            <div class="bill-receipt-brand">{{ config('app.name', 'SUIT LAND') }}</div>
            @if (filled($printerPhoneNumber))
                <div>{{ strtoupper($printerPhoneNumber) }}</div>
            @endif
            <div>OFFICE INTERNAL BILL</div>
        </div>

        <div class="bill-rule"></div>

        <div class="bill-receipt-meta">
            <div class="bill-meta-row">
                <span>Order #</span>
                <span>{{ $order->order_number }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Order Date</span>
                <span>{{ $order->ordered_at?->format('d/m/Y h:i A') ?: '-' }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Customer</span>
                <span>{{ $order->customer?->name ?: 'Walk-in' }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Phone</span>
                <span>{{ $order->customer?->phone ?: '-' }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Delivery</span>
                <span>{{ $order->delivery_due_at?->format('d/m/Y h:i A') ?: '-' }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Workers</span>
                <span>{{ $taskWorkers->isNotEmpty() ? $taskWorkers->implode(', ') : 'Unassigned' }}</span>
            </div>
        </div>

        <div class="bill-rule"></div>

        <table class="bill-table bill-receipt-table">
            <colgroup>
                <col class="bill-col-sn">
                <col class="bill-col-item">
                <col class="bill-col-qty">
                <col class="bill-col-rate">
                <col class="bill-col-amount">
            </colgroup>
            <thead>
                <tr>
                    <th class="bill-center">Sn</th>
                    <th class="bill-left">Item</th>
                    <th class="bill-right">Qty</th>
                    <th class="bill-right">Rate</th>
                    <th class="bill-right">Amt</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $index => $item)
                    @php
                        $isCustom = (string) $item->item_category === 'custom';
                        $garments = collect((array) data_get($item->custom_details, 'garments', []));
                        $fabricProduct = $isCustom
                            ? $customFabricProducts->get((int) data_get($item->custom_details, 'fabric_product_id', 0))
                            : null;
                        $itemName = $isCustom
                            ? ($fabricProduct?->name ?: 'Custom Fabric')
                            : ($item->product?->name ?: 'Product');
                        if ((string) $item->item_category === 'readymade' && filled(data_get($item->custom_details, 'size'))) {
                            $itemName .= ' (Size: ' . data_get($item->custom_details, 'size') . ')';
                        }
                        $lineAmount = (float) $item->line_total;
                    @endphp
                    <tr>
                        <td class="bill-center">{{ $index + 1 }}</td>
                        <td class="bill-left">
                            <div>{{ strtoupper($itemName) }}</div>
                            <div class="bill-print-rate">RATE: {{ $formatMoney($item->unit_price) }}</div>
                        </td>
                        <td class="bill-right">{{ $isCustom ? '' : rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                        <td class="bill-right">{{ $formatMoney($item->unit_price) }}</td>
                        <td class="bill-right">{{ $formatMoney($lineAmount) }}</td>
                    </tr>
                    @if ($isCustom && $garments->isNotEmpty())
                        @foreach ($garments as $garment)
                            @php
                                $garmentQty = (float) ($garment['quantity'] ?? 0);
                                $tailoringAmount = (float) ($garment['tailoring_amount'] ?? 0);
                                $garmentNotes = collect((array) ($garment['design_note'] ?? []))
                                    ->map(fn ($note) => trim((string) $note))
                                    ->filter()
                                    ->values();
                            @endphp
                            <tr class="bill-sub-row">
                                <td class="bill-center"></td>
                                <td class="bill-left">
                                    {{ strtoupper((string) ($garment['garment_title'] ?? 'Tailoring')) }}
                                    @if (filled($garment['tailoring_package'] ?? null))
                                        ({{ $garment['tailoring_package'] }})
                                    @endif
                                    @if ($garmentNotes->isNotEmpty())
                                        <div class="bill-item-subline">Note: {{ $garmentNotes->implode(', ') }}</div>
                                    @endif
                                    <div class="bill-print-rate">RATE: {{ $formatMoney($tailoringAmount) }}</div>
                                </td>
                                <td class="bill-right">{{ rtrim(rtrim(number_format($garmentQty, 2), '0'), '.') }}</td>
                                <td class="bill-right">{{ $formatMoney($tailoringAmount) }}</td>
                                <td class="bill-right">{{ $formatMoney($garmentQty * $tailoringAmount) }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            </tbody>
        </table>

        <div class="bill-rule"></div>

        <div class="bill-receipt-totals">
            <div class="bill-meta-row">
                <span>Subtotal</span>
                <span>{{ $formatMoney($subtotal) }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Tailoring</span>
                <span>{{ $formatMoney($stitchingCharges) }}</span>
            </div>
            @if ($discount > 0)
                <div class="bill-meta-row">
                    <span>Discount</span>
                    <span>-{{ $formatMoney($discount) }}</span>
                </div>
            @endif
            @if ($taxAmount > 0)
                <div class="bill-meta-row">
                    <span>VAT</span>
                    <span>{{ $formatMoney($taxAmount) }}</span>
                </div>
            @endif
            <div class="bill-rule bill-rule-tight"></div>
            <div class="bill-meta-row bill-total-row">
                <span>Net Amount</span>
                <span>{{ $formatMoney($netPayable) }}</span>
            </div>
            <div class="bill-rule bill-rule-tight"></div>
            <div class="bill-meta-row">
                <span>Vendor Cost</span>
                <span>{{ $formatMoney($vendorCost) }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Worker Payment</span>
                <span>{{ $formatMoney($workerPayment) }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Total Internal Cost</span>
                <span>{{ $formatMoney($vendorCost + $workerPayment) }}</span>
            </div>
            <div class="bill-rule bill-rule-tight"></div>
            <div class="bill-meta-row">
                <span>Profit Margin</span>
                <span>{{ $formatMoney($profitMargin) }}</span>
            </div>
        </div>

        <div class="bill-rule"></div>

        <div class="bill-receipt-header">MEASUREMENT DETAILS</div>

        @forelse ($customItems as $customItem)
            @php
                $garments = collect((array) data_get($customItem->custom_details, 'garments', []));
                $fabricProduct = $customFabricProducts->get((int) data_get($customItem->custom_details, 'fabric_product_id', 0));
                $displayName = $fabricProduct?->name
                    ?: (data_get($customItem->custom_details, 'garment_title')
                        ?: ($garments->pluck('garment_title')->filter()->implode(', ') ?: 'Custom Garment'));
            @endphp

            @if ($garments->isNotEmpty())
                @foreach ($garments as $garment)
                    @php
                        $garmentNotes = collect((array) ($garment['design_note'] ?? []))
                            ->map(fn ($note) => trim((string) $note))
                            ->filter()
                            ->values();
                    @endphp

                    <div class="bill-rule bill-rule-tight"></div>

                    <div class="bill-receipt-meta">
                        <div class="bill-meta-row">
                            <span>Garment</span>
                            <span>{{ strtoupper((string) ($garment['garment_title'] ?? 'Garment')) }} x {{ rtrim(rtrim(number_format((float) ($garment['quantity'] ?? 1), 2), '0'), '.') }}</span>
                        </div>
                        <div class="bill-meta-row">
                            <span>Package</span>
                            <span>{{ $garment['tailoring_package'] ?? '-' }}</span>
                        </div>
                        @if ($garmentNotes->isNotEmpty())
                            <div class="bill-meta-row">
                                <span>Design Note</span>
                                <span>{{ $garmentNotes->implode(', ') }}</span>
                            </div>
                        @endif
                    </div>

                    <table class="bill-table bill-receipt-table" style="margin-top:8px;">
                        <colgroup>
                            <col class="bill-col-sn">
                            <col class="bill-col-item">
                            <col class="bill-col-amount">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="bill-center">Sn</th>
                                <th class="bill-left">Type</th>
                                <th class="bill-right">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ((array) ($garment['measurements'] ?? []) as $mIndex => $measurement)
                                <tr>
                                    <td class="bill-center">{{ $mIndex + 1 }}</td>
                                    <td class="bill-left">{{ strtoupper((string) ($measurement['type'] ?? '-')) }}</td>
                                    <td class="bill-right">
                                        {{ $measurement['measurement'] ?? '-' }}
                                        {{ filled($measurement['unit'] ?? null) ? $measurement['unit'] : '' }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="bill-left">No measurements captured.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @endforeach
            @endif
        @empty
            <div class="bill-rule bill-rule-tight"></div>
            <div class="bill-receipt-meta">
                <div class="bill-meta-row">
                    <span>Measurements</span>
                    <span>No custom items.</span>
                </div>
            </div>
        @endforelse

        <div class="bill-rule"></div>

        <div class="bill-receipt-footer">
            <div>INTERNAL USE ONLY</div>
            <div>{{ strtoupper((string) config('app.name', 'SUIT LAND')) }}</div>
        </div>

    </div>
</div>
@endsection

@section('page-specific-style')
@include('modules.order.bills.partials.style')
<style>
    .bill-receipt-table col.bill-col-sn {
        width: 8%;
    }

    .bill-receipt-table col.bill-col-item {
        width: 60%;
    }

    .bill-receipt-table col.bill-col-amount {
        width: 32%;
    }

    @page {
        size: 80mm auto;
        margin: 0;
    }

    @media print {
        html,
        body {
            width: 80mm;
            min-width: 80mm;
            height: auto !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }

        body {
            overflow: visible !important;
        }

        body::before,
        body::after,
        .main-content::before,
        .main-content::after {
            display: none !important;
            content: none !important;
        }

        .main-content,
        .bill-wrap {
            width: 80mm !important;
            min-width: 80mm !important;
            max-width: 80mm !important;
            height: auto !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }

        .pos-receipt-print,
        .pos-receipt-print .bill-receipt {
            page-break-before: avoid !important;
            page-break-after: avoid !important;
            page-break-inside: avoid !important;
            break-before: avoid !important;
            break-after: avoid !important;
            break-inside: avoid !important;
        }

        .bill-receipt-table col.bill-col-sn {
            width: 5mm;
        }

        .bill-receipt-table col.bill-col-item {
            width: 46mm;
        }

        .bill-receipt-table col.bill-col-amount {
            width: 25mm;
        }
    }
</style>
@endsection
