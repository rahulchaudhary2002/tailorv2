@extends('layouts.app')

@section('title', 'Customer Bill')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Customer Bill</h1>
        <p>Customer-facing bill for order {{ $order->order_number }}.</p>
    </div>
</div>

<div class="bill-wrap">
    <div class="bill-actions">
        <button type="button" class="btn btn-secondary" onclick="window.print()">Print</button>
    </div>

    @php
        $totalQty = (float) $items->sum('quantity');
    @endphp

    <div class="bill-card bill-receipt">
        <div class="bill-receipt-header">
            <div class="bill-receipt-brand">{{ strtoupper($order->outlet?->name ?: config('app.name', 'Tailor Shop')) }}</div>
            <div>{{ strtoupper((string) config('app.name', 'Tailor Management System')) }}</div>
            <div>CUSTOMER INVOICE</div>
        </div>

        <div class="bill-rule"></div>

        <div class="bill-receipt-meta">
            <div class="bill-meta-row">
                <span>Bill #</span>
                <span>{{ $order->order_number }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Trans. Date</span>
                <span>{{ $order->ordered_at?->format('d/m/Y h:i A') ?: '-' }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Invoice Date</span>
                <span>{{ now()->format('d/m/Y') }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Payment Mode</span>
                <span>{{ ucfirst((string) ($order->payment_method ?: 'Cash')) }}</span>
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
            <thead>
                <tr>
                    <th>Sn</th>
                    <th>Particulars</th>
                    <th class="bill-right">Qty</th>
                    <th class="bill-right">Rate</th>
                    <th class="bill-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $index => $item)
                    @php
                        $isCustom = (string) $item->item_category === 'custom';
                        $quantityUnit = $isCustom
                            ? (string) data_get($item->custom_details, 'quantity_unit', 'pcs')
                            : ((string) $item->item_category === 'fabric' ? 'm' : 'pcs');
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
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div>{{ strtoupper($itemName) }}</div>
                            @if ($isCustom && $garments->isNotEmpty())
                                <div class="bill-item-subline">
                                    {{ $garments->pluck('tailoring_package')->filter()->implode(', ') ?: 'TAILORING' }}
                                </div>
                            @endif
                        </td>
                        <td class="bill-right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }} {{ $quantityUnit }}</td>
                        <td class="bill-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="bill-right">{{ number_format($lineAmount, 2) }}</td>
                    </tr>
                    @if ($isCustom && $garments->isNotEmpty())
                        @foreach ($garments as $garment)
                            @php
                                $garmentQty = (float) ($garment['quantity'] ?? 0);
                                $tailoringAmount = (float) ($garment['tailoring_amount'] ?? 0);
                            @endphp
                            <tr class="bill-sub-row">
                                <td></td>
                                <td>{{ strtoupper((string) ($garment['garment_title'] ?? 'Tailoring')) }}</td>
                                <td class="bill-right">{{ rtrim(rtrim(number_format($garmentQty, 2), '0'), '.') }}</td>
                                <td class="bill-right">{{ number_format($tailoringAmount, 2) }}</td>
                                <td class="bill-right">{{ number_format($garmentQty * $tailoringAmount, 2) }}</td>
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
                <span>{{ number_format($subtotal, 2) }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Tailoring</span>
                <span>{{ number_format($stitchingCharges, 2) }}</span>
            </div>
            @if ($discount > 0)
                <div class="bill-meta-row">
                    <span>Discount</span>
                    <span>-{{ number_format($discount, 2) }}</span>
                </div>
            @endif
            @if ($taxAmount > 0)
                <div class="bill-meta-row">
                    <span>VAT</span>
                    <span>{{ number_format($taxAmount, 2) }}</span>
                </div>
            @endif
            <div class="bill-rule bill-rule-tight"></div>
            <div class="bill-meta-row bill-total-row">
                <span>Net Amount</span>
                <span>{{ number_format($netPayable, 2) }}</span>
            </div>
            <div class="bill-rule bill-rule-tight"></div>
            <div class="bill-meta-row">
                <span>Advance Payment</span>
                <span>{{ number_format($paidAmount, 2) }}</span>
            </div>
            <div class="bill-meta-row">
                <span>Balance</span>
                <span>{{ number_format($dueAmount, 2) }}</span>
            </div>
            <div class="bill-rule bill-rule-tight"></div>
            <div class="bill-meta-row">
                <span>Total Qty</span>
                <span>{{ rtrim(rtrim(number_format($totalQty, 2), '0'), '.') }}</span>
            </div>
        </div>

        <div class="bill-rule"></div>

        <div class="bill-receipt-footer">
            <div>THANK YOU FOR YOUR ORDER</div>
            <div>PLEASE KEEP THIS INVOICE FOR DELIVERY</div>
            @if (filled($order->customer?->address))
                <div>{{ strtoupper($order->customer?->address) }}</div>
            @endif
            <div>{{ strtoupper((string) config('app.name', 'Tailor Management System')) }}</div>
        </div>
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
