<div class="table-card outlet-form-card">
    <div class="table-header">
        <div class="table-title">{{ $title }}</div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

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
            <label for="vendor_id">Vendor</label>
            <select id="vendor_id" name="vendor_id" class="outlet-input" required>
                <option value="">Select Vendor</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected((string) old('vendor_id', $purchase->vendor_id) === (string) $vendor->id)>
                        {{ $vendor->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="outlet-form-group">
            <label for="product_id">Raw Material</label>
            <select id="product_id" name="product_id" class="outlet-input" required>
                <option value="">Select Raw Material</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" data-code="{{ $product->code }}" @selected((string) old('product_id', $purchase->product_id) === (string) $product->id)>
                        {{ $product->name }} ({{ $product->code }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="outlet-form-group">
            <label for="quantity">Quantity</label>
            <input
                id="quantity"
                name="quantity"
                type="number"
                min="1"
                class="outlet-input"
                value="{{ old('quantity', $purchase->quantity) }}"
                required
            >
        </div>

        <div class="outlet-form-group">
            <label for="purchased_at">Purchase Date</label>
            <input
                id="purchased_at"
                name="purchased_at"
                type="date"
                class="outlet-input"
                value="{{ old('purchased_at', optional($purchase->purchased_at)->toDateString()) }}"
                required
            >
        </div>

        <div class="outlet-form-group">
            <label for="unit_price">Unit Price</label>
            <input
                id="unit_price"
                name="unit_price"
                type="number"
                min="0"
                step="0.01"
                class="outlet-input"
                value="{{ old('unit_price', $purchase->unit_price) }}"
                required
            >
        </div>

        <div class="outlet-form-group">
            <label for="inventory_location_id">Inventory Location</label>
            <select id="inventory_location_id" name="inventory_location_id" class="outlet-input" required>
                <option value="">Select Location</option>
                @foreach ($inventoryLocations as $location)
                    <option
                        value="{{ $location->id }}"
                        @selected((string) old('inventory_location_id', $purchase->inventory_location_id) === (string) $location->id)
                    >
                        {{ $location->name }} ({{ ucwords(str_replace('_', ' ', $location->type)) }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="outlet-form-group">
            <label>Total Amount</label>
            <input
                type="text"
                id="purchase-total-amount"
                class="outlet-input"
                value="{{ number_format((float) $purchase->total_amount, 2) }}"
                readonly
            >
        </div>

        <div class="outlet-form-group outlet-form-group-full">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="outlet-input" rows="3">{{ old('notes', $purchase->notes) }}</textarea>
        </div>
    </div>

    <div class="outlet-form-actions">
        <a href="{{ route('rawMaterialPurchase.index') }}" class="btn btn-secondary">Back</a>
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</div>
