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
                class="outlet-input"
                value="{{ old('name', isset($product) ? $product->name : '') }}"
                placeholder="Premium Cotton Shirt"
                required
            >
        </div>

        <div class="outlet-form-group">
            <label for="code">Code</label>
            <input
                id="code"
                name="code"
                type="text"
                class="outlet-input"
                value="{{ old('code', isset($product) ? $product->code : '') }}"
                placeholder="PRD-1001"
                required
            >
        </div>

        <div class="outlet-form-group">
            <label for="product_category_id">Category</label>
            <select id="product_category_id" name="product_category_id" class="outlet-input" required>
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
        </div>

        <div class="outlet-form-group">
            <label for="amount">Amount</label>
            <input
                id="amount"
                name="amount"
                type="number"
                min="0"
                step="0.01"
                class="outlet-input"
                value="{{ old('amount', isset($product) ? $product->amount : '0.00') }}"
                placeholder="0.00"
                required
            >
        </div>

    </div>

    <div class="outlet-form-actions">
        <a href="{{ route('product.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</div>
