<div class="table-card outlet-form-card product-form-card">
    <div class="table-header">
        <div class="table-title">{{ $title }}</div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please fix the following errors:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="outlet-form-grid">
        <div class="outlet-form-group">
            <label for="name">Product Name</label>
            <input
                id="name"
                name="name"
                type="text"
                class="outlet-input @error('name') is-invalid @enderror"
                value="{{ old('name', isset($product) ? $product->name : '') }}"
                placeholder="Premium Cotton Shirt"
                required
            >
            @error('name')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="outlet-form-group">
            <label for="code">Code</label>
            <input
                id="code"
                name="code"
                type="text"
                class="outlet-input @error('code') is-invalid @enderror"
                value="{{ old('code', isset($product) ? $product->code : '') }}"
                placeholder="PRD-1001"
                required
            >
            @error('code')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="outlet-form-group">
            <label for="product_category_id">Category</label>
            <select id="product_category_id" name="product_category_id" class="outlet-input @error('product_category_id') is-invalid @enderror" required>
                <option value="">Select Category</option>
                @foreach ($categories as $category)
                    <option
                        value="{{ $category->id }}"
                        data-category-slug="{{ $category->slug }}"
                        @selected((string) old('product_category_id', isset($product) ? $product->product_category_id : '') === (string) $category->id)
                    >
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            @error('product_category_id')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="outlet-form-group">
            <label for="amount">Selling Price</label>
            <input
                id="amount"
                name="amount"
                type="number"
                min="0"
                step="0.01"
                class="outlet-input @error('amount') is-invalid @enderror"
                value="{{ old('amount', isset($product) ? $product->amount : '0.00') }}"
                placeholder="0.00"
                required
            >
            @error('amount')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

    </div>
</div>

@if (!isset($product))
    <div class="table-card outlet-form-card product-opening-stock">
        <div class="table-header">
            <div class="table-title">Opening Inventory</div>
        </div>

        <div class="outlet-form-grid">
            <div class="outlet-form-group">
                <label for="inventory_location_id">Location</label>
                <select id="inventory_location_id" name="inventory_location_id" class="outlet-input @error('inventory_location_id') is-invalid @enderror">
                    <option value="">Select Location</option>
                    @foreach (($locations ?? collect()) as $location)
                        <option value="{{ $location->id }}" @selected((string) old('inventory_location_id') === (string) $location->id)>
                            {{ $location->name }} ({{ $location->type }})
                        </option>
                    @endforeach
                </select>
                @error('inventory_location_id')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="outlet-form-group">
                <label for="opening_quantity">Opening Quantity</label>
                <input
                    id="opening_quantity"
                    name="opening_quantity"
                    type="number"
                    min="0.01"
                    step="0.01"
                    class="outlet-input @error('opening_quantity') is-invalid @enderror"
                    value="{{ old('opening_quantity') }}"
                    placeholder="0.00"
                >
                @error('opening_quantity')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="outlet-form-group">
                <label for="opening_unit_cost">Actual Price</label>
                <input
                    id="opening_unit_cost"
                    name="opening_unit_cost"
                    type="number"
                    min="0"
                    step="0.01"
                    class="outlet-input @error('opening_unit_cost') is-invalid @enderror"
                    value="{{ old('opening_unit_cost', old('amount', '0.00')) }}"
                    placeholder="0.00"
                >
                @error('opening_unit_cost')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="outlet-form-group outlet-form-group-full">
                <label for="opening_notes">Inventory Note</label>
                <textarea
                    id="opening_notes"
                    name="opening_notes"
                    class="outlet-input @error('opening_notes') is-invalid @enderror"
                    rows="2"
                    placeholder="Optional note for opening stock transaction"
                >{{ old('opening_notes', 'Opening inventory during product creation') }}</textarea>
                @error('opening_notes')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
@endif

<div class="table-card outlet-form-card product-form-actions-card">
    <div class="outlet-form-actions">
        <a href="{{ route('product.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</div>

@once
    <style>
        .form-error {
            margin-top: 6px;
            color: #b91c1c;
            font-size: 0.875rem;
        }

        .outlet-input.is-invalid {
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
        }

        .product-opening-stock {
            margin-top: 16px;
        }

        .product-form-actions-card {
            margin-top: 16px;
        }
    </style>
@endonce
