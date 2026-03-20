@extends('layouts.app')

@section('title', 'View Order')

@section('content')
@php
    $taskWorkers = $order->tasks->pluck('worker.name')->filter()->unique()->values();
    $taskDeadline = $order->tasks->pluck('worker_deadline_at')->filter()->sort()->first();
@endphp
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
            <label>Task Workers</label>
            <div>{{ $taskWorkers->isNotEmpty() ? $taskWorkers->implode(', ') : 'Unassigned' }}</div>
        </div>
        <div class="outlet-form-group">
            <label>Task Deadline</label>
            <div>{{ $taskDeadline?->format('M d, Y h:i A') ?? '-' }}</div>
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
    <style>
        .order-item-meta {
            margin-top: 6px;
            color: #5f7083;
            font-size: 13px;
        }

        .order-custom-block {
            margin-top: 12px;
            padding: 12px 14px;
            border: 1px solid #e3eaf3;
            border-radius: 10px;
            background: #f8fbff;
        }

        .order-custom-title {
            font-weight: 700;
            color: #1f3d5a;
        }

        .order-custom-grid {
            display: grid;
            gap: 10px;
            margin-top: 10px;
        }

        .order-custom-card {
            padding: 12px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #dbe3ee;
        }

        .order-custom-card-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }

        .order-custom-card-title {
            font-weight: 700;
            color: #1f3d5a;
        }

        .order-custom-card-meta {
            margin-top: 4px;
            color: #5f7083;
            font-size: 13px;
        }

        .order-custom-measurements,
        .order-custom-notes {
            margin-top: 10px;
            color: #3b4b5c;
            font-size: 13px;
        }

        .order-custom-images {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .order-custom-images img {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #dbe3ee;
            background: #fff;
        }
    </style>
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
                    @php
                        $isCustom = (string) $item->item_category === 'custom';
                        $customDetails = (array) ($item->custom_details ?? []);
                        $garments = collect((array) data_get($customDetails, 'garments', []));
                        $displayName = $isCustom
                            ? (data_get($customDetails, 'garment_title')
                                ?: ($garments->pluck('garment_title')->filter()->implode(', ') ?: 'Custom Garment'))
                            : ($item->product?->name ?? '-');
                        $displayCode = $isCustom
                            ? strtoupper((string) data_get($customDetails, 'fabric_source', 'custom'))
                            : ($item->product?->code ?? '-');
                        $displayUnit = $isCustom
                            ? (data_get($customDetails, 'fabric_quantity_unit') ?: data_get($customDetails, 'quantity_unit') ?: 'm')
                            : ($item->unit?->symbol ?: ($item->unit?->name ?: '-'));
                    @endphp
                    <tr>
                        <td>
                            {{ $displayName }}
                            @if ($isCustom)
                                <div class="order-item-meta">
                                    Fabric Source: {{ ucfirst((string) data_get($customDetails, 'fabric_source', 'own')) }}
                                </div>
                            @endif
                        </td>
                        <td>{{ $displayCode }}</td>
                        <td>{{ number_format((float) $item->quantity, 2) }}</td>
                        <td>{{ $displayUnit }}</td>
                        <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td>{{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                    @if ($isCustom)
                        <tr>
                            <td colspan="6" style="background: #fcfdff;">
                                <div class="order-custom-block">
                                    <div class="order-custom-title">Custom Garment Details</div>
                                    <div class="order-item-meta">
                                        Tailoring Total: {{ number_format((float) data_get($customDetails, 'tailoring_total_price', 0), 2) }}
                                    </div>
                                    @if ($garments->isNotEmpty())
                                        <div class="order-custom-grid">
                                            @foreach ($garments as $garment)
                                                @php
                                                    $garmentNotes = collect((array) ($garment['design_note'] ?? []))
                                                        ->map(fn ($note) => trim((string) $note))
                                                        ->filter()
                                                        ->values();
                                                    $garmentImages = collect((array) ($garment['design_images'] ?? []))
                                                        ->push($garment['design_image'] ?? null)
                                                        ->filter(fn ($path) => filled($path))
                                                        ->unique()
                                                        ->values();
                                                @endphp
                                                <div class="order-custom-card">
                                                    <div class="order-custom-card-head">
                                                        <div>
                                                            <div class="order-custom-card-title">
                                                                {{ $garment['garment_title'] ?? 'Garment' }} x {{ number_format((float) ($garment['quantity'] ?? 1), 2) }}
                                                            </div>
                                                            <div class="order-custom-card-meta">
                                                                {{ $garment['tailoring_package'] ?? 'Tailoring' }} | NPR {{ number_format((float) ($garment['tailoring_amount'] ?? 0), 2) }}
                                                            </div>
                                                        </div>
                                                        <div style="font-weight:700;color:#1f3d5a;">
                                                            NPR {{ number_format((float) ($garment['tailoring_total_amount'] ?? 0), 2) }}
                                                        </div>
                                                    </div>
                                                    <div class="order-custom-measurements">
                                                        <strong>Measurements:</strong>
                                                        {{ collect((array) ($garment['measurements'] ?? []))->map(function ($measurement) {
                                                            return trim((string) ($measurement['type'] ?? '-')) . ': '
                                                                . trim((string) ($measurement['measurement'] ?? '-'))
                                                                . (filled($measurement['unit'] ?? null) ? ' ' . trim((string) $measurement['unit']) : '');
                                                        })->implode(', ') ?: '-' }}
                                                    </div>
                                                    <div class="order-custom-notes">
                                                        <strong>Design Note:</strong> {{ $garmentNotes->isNotEmpty() ? $garmentNotes->implode(', ') : '-' }}
                                                    </div>
                                                    @if ($garmentImages->isNotEmpty())
                                                        <div class="order-custom-images">
                                                            @foreach ($garmentImages as $imagePath)
                                                                @php
                                                                    $imagePath = (string) $imagePath;
                                                                    $imageUrl = str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')
                                                                        ? $imagePath
                                                                        : \Illuminate\Support\Facades\Storage::disk('public')->url(ltrim($imagePath, '/'));
                                                                @endphp
                                                                <a href="{{ $imageUrl }}" target="_blank" rel="noopener">
                                                                    <img src="{{ $imageUrl }}" alt="Design Sample">
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="order-item-meta">No garment breakdown saved for this custom item.</div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="6" class="empty">No order items found.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" style="text-align: right;">Subtotal</th>
                    <th>{{ number_format((float) ($order->subtotal_amount ?? 0), 2) }}</th>
                </tr>
                <tr>
                    <th colspan="5" style="text-align: right;">Discount</th>
                    <th>{{ number_format((float) ($order->discount_amount ?? 0), 2) }}</th>
                </tr>
                <tr>
                    <th colspan="5" style="text-align: right;">Tailoring Amount</th>
                    <th>{{ number_format((float) ($order->tailoring_amount ?? 0), 2) }}</th>
                </tr>
                <tr>
                    <th colspan="5" style="text-align: right;">VAT {{ $order->vat_enabled ? '(13%)' : '' }}</th>
                    <th>{{ number_format((float) ($order->vat_enabled ? ($order->vat_amount ?? 0) : 0), 2) }}</th>
                </tr>
                <tr>
                    <th colspan="5" style="text-align: right;">Payable Amount</th>
                    <th>{{ number_format($order->payableAmount(), 2) }}</th>
                </tr>
                <tr>
                    <th colspan="5" style="text-align: right;">Advance Paid</th>
                    <th>{{ number_format($order->paidAmount(), 2) }}</th>
                </tr>
                <tr>
                    <th colspan="5" style="text-align: right;">Due Amount</th>
                    <th>{{ number_format($order->dueAmount(), 2) }}</th>
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
