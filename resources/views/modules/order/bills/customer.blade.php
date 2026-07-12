@extends('layouts.app')

@section('title', 'Customer Bill')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Customer Bill</h1>
        <p>Customer-facing bill for order {{ $order->order_number }}.</p>
    </div>
</div>

<div class="bill-wrap pos-receipt-print">
    <div class="bill-actions">
        <button type="button" class="btn btn-secondary" onclick="window.print()">Print</button>
    </div>

    @php
        $totalItems = $items->sum(function ($item) {
            if ((string) $item->item_category === 'custom') {
                $count = count((array) data_get($item->custom_details, 'garments', []));
                return $count > 0 ? $count : 1;
            }
            return 1;
        });
        $printerPhoneNumber = \App\Models\Setting::valueFor('printer_phone_number', '');
        $formatMoney = fn ($amount): string => \App\Support\AmountFormatter::format($amount);
    @endphp

    <div class="bill-card bill-receipt">
        <div class="bill-receipt-header">
            <div class="bill-receipt-brand">{{ config('app.name', 'SUIT LAND') }}</div>
            @if (filled($printerPhoneNumber))
                <div>{{ strtoupper($printerPhoneNumber) }}</div>
            @endif
            <div>ESTIMATED BILL</div>
        </div>

        <div class="bill-rule"></div>

        <div class="bill-receipt-meta">
            <div class="bill-meta-row">
                <span>Bill #</span>
                <span>{{ $order->order_number }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Bill Date</span>
                <span>{{ now()->format('d/m/Y h:i A') }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Trans. Date</span>
                <span>{{ $order->ordered_at?->format('d/m/Y h:i A') ?: '-' }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Payment Mode</span>
                <span>{{ \App\Models\Order::paymentMethodLabel($order->payment_method ?: \App\Models\Order::PAYMENT_METHOD_CASH) }}</span>
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
                        $isFabric = (string) $item->item_category === 'fabric';
                        $quantityUnit = $isCustom
                            ? (string) data_get($item->custom_details, 'quantity_unit', 'pcs')
                            : ($isFabric ? 'm' : 'pcs');
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
                        <td class="bill-right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}{{ ($isCustom || $isFabric) ? ' '.$quantityUnit : '' }}</td>
                        <td class="bill-right">{{ $formatMoney($item->unit_price) }}</td>
                        <td class="bill-right">{{ $formatMoney($lineAmount) }}</td>
                    </tr>
                    @if ($isCustom && $garments->isNotEmpty())
                        @foreach ($garments as $garment)
                            @php
                                $garmentQty = (float) ($garment['quantity'] ?? 0);
                                $tailoringAmount = (float) ($garment['tailoring_amount'] ?? 0);
                            @endphp
                            <tr class="bill-sub-row">
                                <td class="bill-center"></td>
                                <td class="bill-left">
                                    {{ strtoupper((string) ($garment['garment_title'] ?? 'Tailoring')) }}
                                    @if (filled($garment['tailoring_package'] ?? null))
                                        ({{ $garment['tailoring_package'] }})
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
                <span>Advance Payment</span>
                <span>{{ $formatMoney($paidAmount) }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Balance</span>
                <span>{{ $formatMoney($dueAmount) }}</span>
            </div>
            <div class="bill-rule bill-rule-tight"></div>
            <div class="bill-meta-row">
                <span>Total Items</span>
                <span>{{ $totalItems }}</span>
            </div>
        </div>

        <div class="bill-rule"></div>

        <div class="bill-receipt-footer">
            <div>THANK YOU FOR YOUR ORDER</div>
            <div>PLEASE KEEP THIS INVOICE FOR DELIVERY</div>
            @if (filled($order->customer?->address))
                <div>{{ strtoupper($order->customer?->address) }}</div>
            @endif
            <div>{{ strtoupper((string) config('app.name', 'SUIT LAND')) }}</div>
        </div>
    </div>

</div>
@endsection

@section('page-specific-style')
@include('modules.order.bills.partials.style')
<style>
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
    }
</style>
@endsection

@section('page-specific-script')
@if (request()->boolean('autoprint'))
<script>
window.addEventListener('load', function () {
    var url = new URL(window.location.href);
    url.searchParams.delete('autoprint');
    window.history.replaceState({}, '', url.toString());
    window.print();
});
</script>
@endif
@endsection
