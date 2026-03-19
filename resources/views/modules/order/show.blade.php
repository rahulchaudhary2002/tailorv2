@extends('layouts.app')

@section('title', 'View Order')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Order {{ $order->order_number }}</h1>
        <p>Read-only order details, items, and customer information.</p>
    </div>
</div>

<div class="table-card" style="margin-bottom: 16px;">
    <div class="table-header">
        <div class="table-title">Order Summary</div>
    </div>
    <div class="outlet-form-grid" style="padding: 16px;">
        <div class="outlet-form-group">
            <label>Order Number</label>
            <div>{{ $order->order_number }}</div>
        </div>
        <div class="outlet-form-group">
            <label>Status</label>
            <div>{{ \App\Models\Order::statusLabel((string) $order->status) }}</div>
        </div>
        <div class="outlet-form-group">
            <label>Payment Status</label>
            <div>{{ ucfirst((string) $order->payment_status) }}</div>
        </div>
        <div class="outlet-form-group">
            <label>Outlet</label>
            <div>{{ $order->outlet?->name ?? '-' }}</div>
        </div>
        <div class="outlet-form-group">
            <label>Ordered At</label>
            <div>{{ $order->ordered_at?->format('M d, Y h:i A') ?? '-' }}</div>
        </div>
        <div class="outlet-form-group">
            <label>Delivery Due</label>
            <div>{{ $order->delivery_due_at?->format('M d, Y h:i A') ?? '-' }}</div>
        </div>
        <div class="outlet-form-group">
            <label>Worker</label>
            <div>{{ $order->worker?->name ?? 'Unassigned' }}</div>
        </div>
        <div class="outlet-form-group">
            <label>Created By</label>
            <div>{{ $order->creator?->name ?? '-' }}</div>
        </div>
        <div class="outlet-form-group outlet-form-group-full">
            <label>Notes</label>
            <div>{{ $order->notes ?: '-' }}</div>
        </div>
    </div>
</div>

<div class="table-card" style="margin-bottom: 16px;">
    <div class="table-header">
        <div class="table-title">Customer</div>
    </div>
    <div class="outlet-form-grid" style="padding: 16px;">
        <div class="outlet-form-group">
            <label>Name</label>
            <div>{{ $order->customer?->name ?? '-' }}</div>
        </div>
        <div class="outlet-form-group">
            <label>Phone</label>
            <div>{{ $order->customer?->phone ?? '-' }}</div>
        </div>
        <div class="outlet-form-group">
            <label>Email</label>
            <div>{{ $order->customer?->email ?? '-' }}</div>
        </div>
        <div class="outlet-form-group outlet-form-group-full">
            <label>Address</label>
            <div>{{ $order->customer?->address ?? '-' }}</div>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="table-header">
        <div class="table-title">Order Items</div>
    </div>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Code</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Unit Price</th>
                    <th>Line Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($order->items as $item)
                    <tr>
                        <td>{{ $item->product?->name ?? '-' }}</td>
                        <td>{{ $item->product?->code ?? '-' }}</td>
                        <td>{{ number_format((float) $item->quantity, 2) }}</td>
                        <td>{{ $item->unit?->symbol ?: ($item->unit?->name ?: '-') }}</td>
                        <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td>{{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">No order items found.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" style="text-align: right;">Payable Amount</th>
                    <th>{{ number_format($order->payableAmount(), 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="outlet-form-actions" style="padding: 16px;">
        <a href="{{ route('order.index') }}" class="btn btn-secondary">Back to Orders</a>
        @if ($order->customer_id)
            <a href="{{ route('customer.show', $order->customer_id) }}" class="btn btn-secondary">Back to Customer</a>
        @endif
        @if (in_array((string) $order->status, [\App\Models\Order::STATUS_PENDING, \App\Models\Order::STATUS_CONFIRMED, \App\Models\Order::STATUS_FABRIC_ISSUED], true))
            <a href="{{ route('order.edit', $order) }}" class="btn btn-primary">Edit Order</a>
        @endif
    </div>
</div>
@endsection
