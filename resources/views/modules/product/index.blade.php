@extends('layouts.app')

@section('title', 'Product Management')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Product Management</h1>
        <p>Manage ready made, accessories, and fabrics products.</p>
    </div>
    @canany(['manage-products', 'create-products'])
        <div class="page-actions">
            <a href="{{ route('product.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Product
            </a>
        </div>
    @endcanany
</div>

@include('includes.reporting-filter', ['paginator' => $products, 'placeholder' => 'Search by product name, code, amount...', 'reporting' => $reporting])

<div class="table-card">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-header">
        <div class="table-title">Products</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Amount</th>
                    <th>Category</th>
                    <th>Inventory Qty</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->code }}</td>
                        <td>Rs {{ number_format((float) $product->amount, 2) }}</td>
                        <td>{{ $product->category?->name ?? '-' }}</td>
                        <td>{{ number_format((float) ($product->inventory_total_quantity ?? 0), 2) }}</td>
                        <td>{{ $product->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="actions">
                                @canany(['manage-products', 'edit-products'])
                                    <a href="{{ route('product.edit', $product) }}" class="btn btn-sm btn-secondary">Edit</a>
                                @endcanany

                                @canany(['manage-products', 'delete-products'])
                                    <form
                                        action="{{ route('product.destroy', $product) }}"
                                        method="POST"
                                        class="inline-form"
                                        onsubmit="return confirm('Delete this product?');"
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
                        <td colspan="7" class="empty">No products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($products->hasPages())
        <div class="pagination">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection
