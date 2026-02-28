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
            <label>Vendor</label>
            <input type="text" class="outlet-input" value="{{ $purchase->vendor?->name }}" readonly>
        </div>

        <div class="outlet-form-group">
            <label>Raw Material</label>
            <input type="text" class="outlet-input" value="{{ $purchase->product?->name }} ({{ $purchase->product?->code }})" readonly>
        </div>

        <div class="outlet-form-group">
            <label>Quantity</label>
            <input
                type="text"
                class="outlet-input"
                value="{{ $purchase->quantity }} {{ $purchase->unit?->symbol ?: $purchase->unit?->name }}"
                readonly
            >
        </div>

        <div class="outlet-form-group">
            <label>Inventory Status</label>
            <input
                type="text"
                class="outlet-input"
                value="{{ $purchase->inventory_updated_at ? 'Updated on ' . $purchase->inventory_updated_at->format('M d, Y h:i A') : 'Not Updated' }}"
                readonly
            >
        </div>

        <div class="outlet-form-group">
            <label for="vendor_bill_number">Bill Number</label>
            <input
                id="vendor_bill_number"
                name="vendor_bill_number"
                type="text"
                class="outlet-input"
                value="{{ old('vendor_bill_number', $purchase->vendor_bill_number) }}"
                placeholder="BILL-1001"
            >
        </div>

        <div class="outlet-form-group">
            <label for="vendor_bill_amount">Bill Amount</label>
            <input
                id="vendor_bill_amount"
                name="vendor_bill_amount"
                type="number"
                min="0"
                step="0.01"
                class="outlet-input"
                value="{{ old('vendor_bill_amount', $purchase->vendor_bill_amount ?: $purchase->total_amount) }}"
            >
        </div>

        <div class="outlet-form-group outlet-form-group-full">
            <label for="bill_file">Upload Bill</label>
            <input
                id="bill_file"
                name="bill_file"
                type="file"
                class="outlet-input"
                accept=".pdf,.jpg,.jpeg,.png"
            >
            @if ($purchase->bill_file_path)
                <small>
                    Current bill:
                    <a href="{{ asset('storage/' . $purchase->bill_file_path) }}" target="_blank" rel="noopener">View File</a>
                </small>
            @endif
        </div>

        <div class="outlet-form-group outlet-form-group-full">
            <label class="product-active-toggle">
                <input
                    type="checkbox"
                    name="update_inventory"
                    value="1"
                    @checked(old('update_inventory'))
                    @disabled($purchase->inventory_updated_at !== null)
                >
                Update inventory with this purchase quantity
            </label>
            @if ($purchase->inventory_updated_at !== null)
                <small>Inventory is already updated for this purchase.</small>
            @endif
        </div>

        <div class="outlet-form-group">
            <label for="inventory_location_id">Warehouse Location</label>
            <select
                id="inventory_location_id"
                name="inventory_location_id"
                class="outlet-input"
                @disabled($purchase->inventory_updated_at !== null)
            >
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
            <small>Required only when updating inventory. Only warehouse locations are allowed.</small>
        </div>

        <div class="outlet-form-group">
            <label for="inventory_base_price">Inventory Base Price</label>
            <input
                id="inventory_base_price"
                name="inventory_base_price"
                type="number"
                min="0"
                step="0.01"
                class="outlet-input"
                value="{{ old('inventory_base_price', $purchase->unit_price) }}"
                @disabled($purchase->inventory_updated_at !== null)
            >
            <small>Required when updating inventory.</small>
        </div>

        <div class="outlet-form-group">
            <label for="inventory_special_price">Inventory Special Price</label>
            <input
                id="inventory_special_price"
                name="inventory_special_price"
                type="number"
                min="0"
                step="0.01"
                class="outlet-input"
                value="{{ old('inventory_special_price') }}"
                @disabled($purchase->inventory_updated_at !== null)
            >
            <small>Optional. Must be less than or equal to base price.</small>
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
