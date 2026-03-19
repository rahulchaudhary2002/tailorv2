@extends('layouts.app')

@section('title', 'Raw Material Purchases')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Raw Material Purchases</h1>
        <p>Create purchases and update inventory immediately.</p>
    </div>
    @canany(['manage-raw-material-purchases', 'create-raw-material-purchases'])
        <div class="page-actions">
            <a href="{{ route('rawMaterialPurchase.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Purchase
            </a>
        </div>
    @endcanany
</div>

@include('includes.reporting-filter', ['paginator' => $purchases, 'placeholder' => 'Search by vendor, product, code, bill no...', 'reporting' => $reporting])

    <div class="table-card">
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-header">
        <div class="table-title">Purchase Records</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Vendor</th>
                    <th>Raw Material</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Unit Price</th>
                    <th>Total Amount</th>
                    <th>Inventory Location</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchases as $purchase)
                    <tr>
                        <td>{{ $purchase->purchased_at->format('M d, Y') }}</td>
                        <td>{{ $purchase->vendor?->name ?: '-' }}</td>
                        <td>{{ $purchase->product?->name ?: '-' }}</td>
                        <td>{{ $purchase->quantity }}</td>
                        <td>{{ $purchase->unit?->symbol ?: ($purchase->unit?->name ?: '-') }}</td>
                        <td>{{ number_format((float) $purchase->unit_price, 2) }}</td>
                        <td>{{ number_format((float) $purchase->total_amount, 2) }}</td>
                        <td>{{ $purchase->inventoryLocation?->name ?: '-' }}</td>
                        <td>
                            @can('manage-raw-material-purchases')
                                <a href="{{ route('rawMaterialPurchase.edit', $purchase) }}" class="btn btn-sm btn-secondary">Edit</a>
                            @else
                                -
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="empty">No purchases found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($purchases->hasPages())
        <div class="pagination">
            {{ $purchases->links() }}
        </div>
    @endif
</div>
@endsection
