@extends('layouts.app')

@section('title', 'Purchases')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Purchases</h1>
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

@php
    $query = trim((string) request('q', ''));
    $selectedProductId = (int) request('product_id', 0);
    $selectedVendorId = (int) request('vendor_id', 0);
    $selectedLocationId = (int) request('location_id', 0);
@endphp

<div class="directory-reporting" style="margin-bottom: 16px;">
    <div class="directory-reporting__filter-bar">
        <div class="directory-reporting__filter-head">
            <h3 class="directory-reporting__filter-title">Filter Records</h3>
            @if ($query !== '' || $selectedProductId > 0 || $selectedVendorId > 0 || $selectedLocationId > 0)
                <a href="{{ url()->current() }}" class="btn btn-light btn-sm">Clear Filters</a>
            @endif
        </div>

        <form method="GET" class="listing-filter-form">
            <div class="listing-filter-form__fields">
                <div class="outlet-form-group listing-filter-form__field listing-filter-form__field--search">
                    <label for="q_filter">Search</label>
                    <input id="q_filter" type="text" name="q" class="outlet-input" value="{{ $query }}" placeholder="Search by vendor, product, or code...">
                </div>

                <div class="outlet-form-group listing-filter-form__field">
                    <label for="product_filter">Product</label>
                    <select id="product_filter" name="product_id" class="outlet-input">
                        <option value="">All Products</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected($selectedProductId === (int) $product->id)>
                                {{ $product->name }}@if($product->code) ({{ $product->code }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="outlet-form-group listing-filter-form__field">
                    <label for="vendor_filter">Vendor</label>
                    <select id="vendor_filter" name="vendor_id" class="outlet-input">
                        <option value="">All Vendors</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected($selectedVendorId === (int) $vendor->id)>
                                {{ $vendor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="outlet-form-group listing-filter-form__field">
                    <label for="location_filter">Location</label>
                    <select id="location_filter" name="location_id" class="outlet-input">
                        <option value="">All Locations</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected($selectedLocationId === (int) $location->id)>
                                {{ $location->name }} ({{ $location->type }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="listing-filter-form__actions">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="{{ url()->current() }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

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
                    <th>Product</th>
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
                        <td>{{ $purchase->unit?->symbol ?: ($purchase->product?->defaultUnitLabel() ?: '-') }}</td>
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

@section('page-specific-script')
<style>
    .listing-filter-form {
        display: grid;
        gap: 14px;
    }

    .listing-filter-form__fields {
        display: grid;
        grid-template-columns: minmax(280px, 1.4fr) repeat(3, minmax(200px, 1fr));
        gap: 12px;
        align-items: end;
    }

    .listing-filter-form__field {
        margin-bottom: 0;
    }

    .listing-filter-form__actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    @media (max-width: 992px) {
        .listing-filter-form__fields {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .listing-filter-form__field--search {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 640px) {
        .listing-filter-form__fields {
            grid-template-columns: 1fr;
        }

        .listing-filter-form__actions {
            flex-direction: column;
        }

        .listing-filter-form__actions .btn {
            width: 100%;
        }
    }
</style>
@endsection
