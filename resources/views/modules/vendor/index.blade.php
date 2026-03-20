@extends('layouts.app')

@section('title', 'Vendor Management')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Vendor Management</h1>
        <p>Manage vendors and dynamic vendor types.</p>
    </div>
    @canany(['manage-vendors', 'create-vendors'])
        <div class="page-actions">
            <a href="{{ route('vendor.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Vendor
            </a>
        </div>
    @endcanany
</div>

@php
    $query = trim((string) request('q', ''));
    $selectedVendorTypeId = (int) request('vendor_type_id', 0);
@endphp

<div class="directory-reporting" style="margin-bottom: 16px;">
    <div class="directory-reporting__filter-bar">
        <div class="directory-reporting__filter-head">
            <h3 class="directory-reporting__filter-title">Filter Records</h3>
            @if ($query !== '' || $selectedVendorTypeId > 0)
                <a href="{{ url()->current() }}" class="btn btn-light btn-sm">Clear Filters</a>
            @endif
        </div>

        <form method="GET" class="listing-filter-form">
            <div class="listing-filter-form__fields listing-filter-form__fields--vendor">
                <div class="outlet-form-group listing-filter-form__field listing-filter-form__field--search">
                    <label for="q_filter">Search</label>
                    <input id="q_filter" type="text" name="q" class="outlet-input" value="{{ $query }}" placeholder="Search by vendor name, email, phone...">
                </div>

                <div class="outlet-form-group listing-filter-form__field">
                    <label for="vendor_type_filter">Vendor Type</label>
                    <select id="vendor_type_filter" name="vendor_type_id" class="outlet-input">
                        <option value="">All Types</option>
                        @foreach ($vendorTypes as $vendorType)
                            <option value="{{ $vendorType->id }}" @selected($selectedVendorTypeId === (int) $vendorType->id)>
                                {{ $vendorType->name }}
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
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-header">
        <div class="table-title">Vendors</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Contact Person</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vendors as $vendor)
                    <tr>
                        <td>{{ $vendor->name }}</td>
                        <td>{{ $vendor->vendorType?->name ?: '-' }}</td>
                        <td>{{ $vendor->contact_person ?: '-' }}</td>
                        <td>{{ $vendor->email ?: '-' }}</td>
                        <td>{{ $vendor->phone ?: '-' }}</td>
                        <td>
                            <div class="actions">
                                @canany(['manage-vendors', 'edit-vendors'])
                                    <a href="{{ route('vendor.edit', $vendor) }}" class="btn btn-sm btn-secondary">Edit</a>
                                @endcanany
                                @canany(['manage-raw-material-purchases', 'create-raw-material-purchases'])
                                    <a href="{{ route('rawMaterialPurchase.create', ['vendor_id' => $vendor->id]) }}" class="btn btn-sm btn-primary">
                                        Purchase
                                    </a>
                                @endcanany

                                @canany(['manage-vendors', 'delete-vendors'])
                                    <form
                                        action="{{ route('vendor.destroy', $vendor) }}"
                                        method="POST"
                                        class="inline-form"
                                        onsubmit="return confirm('Delete this vendor?');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                @endcanany
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">No vendors found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($vendors->hasPages())
        <div class="pagination">
            {{ $vendors->links() }}
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

    .listing-filter-form__fields--vendor {
        grid-template-columns: minmax(320px, 1.7fr) minmax(240px, 1fr);
    }

    .listing-filter-form__field {
        margin-bottom: 0;
    }

    .listing-filter-form__actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    @media (max-width: 768px) {
        .listing-filter-form__fields,
        .listing-filter-form__fields--vendor {
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
