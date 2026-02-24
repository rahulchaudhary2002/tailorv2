@extends('layouts.app')

@section('title', 'Office Internal Bill')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Office / Internal Bill</h1>
        <p>Internal cost and margin report for order {{ $order->order_number }}.</p>
    </div>
</div>

<div class="table-card bill-wrap">
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
                <div>Worker: {{ $order->worker?->name ?: 'Unassigned' }}</div>
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
                    <tr>
                        <td>
                            @if ((string) $item->item_category === 'custom')
                                {{ data_get($item->custom_details, 'garment_title', 'Custom Garment') }}
                            @else
                                {{ $item->product?->name ?: '-' }}
                            @endif
                        </td>
                        <td>{{ ucfirst((string) $item->item_category) }}</td>
                        <td class="bill-right">{{ number_format((float) $item->quantity, 2) }}</td>
                        <td class="bill-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="bill-right">{{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('page-specific-style')
@include('modules.order.bills.partials.style')
@endsection
