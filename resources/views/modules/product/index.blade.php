@extends('layouts.app')

@section('title', 'Product Management')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Product Management</h1>
        <p>Manage ready made, accessories, and fabrics products.</p>
    </div>
    <div class="page-actions">
        <a
            href="{{ route('product.barcodes.pdf', request()->only(['q', 'category_id'])) }}"
            class="btn btn-secondary"
            target="_blank"
            rel="noopener"
        >
            <i class="fas fa-file-pdf"></i> Download Barcodes PDF
        </a>
        @canany(['manage-products', 'create-products'])
            <a href="{{ route('product.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Product
            </a>
        @endcanany
    </div>
</div>

@php
    $query = trim((string) request('q', ''));
    $selectedCategoryId = (int) request('category_id', 0);
@endphp

<div class="directory-reporting" style="margin-bottom: 16px;">
    <div class="directory-reporting__filter-bar">
        <div class="directory-reporting__filter-head">
            <h3 class="directory-reporting__filter-title">Filter Records</h3>
            @if ($query !== '' || $selectedCategoryId > 0)
                <a href="{{ url()->current() }}" class="btn btn-light btn-sm">Clear Filters</a>
            @endif
        </div>

        <form method="GET" class="listing-filter-form">
            <div class="listing-filter-form__fields listing-filter-form__fields--product">
                <div class="outlet-form-group listing-filter-form__field listing-filter-form__field--search">
                    <label for="q_filter">Search</label>
                    <input id="q_filter" type="text" name="q" class="outlet-input" value="{{ $query }}" placeholder="Search by product name, code, amount...">
                </div>

                <div class="outlet-form-group listing-filter-form__field">
                    <label for="category_filter">Category</label>
                    <select id="category_filter" name="category_id" class="outlet-input">
                        <option value="">All Categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($selectedCategoryId === (int) $category->id)>
                                {{ $category->name }}
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
        <div class="table-title">Products</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Barcode</th>
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
                        <td>
                            @if ($product->barcode_svg !== '')
                                <div class="barcode-preview">{!! $product->barcode_svg !!}</div>
                            @else
                                <span class="barcode-inline">{{ $product->barcode ?: $product->code ?: '-' }}</span>
                            @endif
                        </td>
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

    .listing-filter-form__fields--product {
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

    .barcode-inline {
        font-family: monospace;
        font-size: 0.92rem;
        letter-spacing: 0.06em;
    }

    .barcode-preview {
        display: inline-flex;
        align-items: center;
        max-width: 180px;
        padding: 4px 6px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
    }

    .barcode-preview svg {
        display: block;
        width: 100%;
        height: auto;
    }

    @media (max-width: 768px) {
        .listing-filter-form__fields,
        .listing-filter-form__fields--product {
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
