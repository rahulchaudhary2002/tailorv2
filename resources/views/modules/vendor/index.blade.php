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

@include('includes.reporting-filter', ['paginator' => $vendors, 'placeholder' => 'Search by vendor name, email, phone...', 'reporting' => $reporting])

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
