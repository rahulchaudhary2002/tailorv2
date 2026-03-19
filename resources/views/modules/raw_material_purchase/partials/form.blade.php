<div class="table-card outlet-form-card">
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

    @php
        $selectedVendorId = $selectedVendorId ?? 0;
        $selectedPurchaseDate = $selectedPurchaseDate ?? now()->toDateString();
        $notesValue = $notesValue ?? '';
    @endphp

    <div class="outlet-form-grid">
        <div class="outlet-form-group">
            <label for="vendor_id">Vendor</label>
            <select id="vendor_id" name="vendor_id" class="outlet-input" required>
                <option value="">Select Vendor</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected((string) old('vendor_id', $selectedVendorId ?? '') === (string) $vendor->id)>
                        {{ $vendor->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="outlet-form-group">
            <label for="purchased_at">Purchase Date</label>
            <input
                id="purchased_at"
                name="purchased_at"
                type="date"
                class="outlet-input"
                value="{{ old('purchased_at', $selectedPurchaseDate) }}"
                required
            >
        </div>

        <div class="outlet-form-group outlet-form-group-full">
            <label for="notes">Notes</label>
            <textarea
                id="notes"
                name="notes"
                class="outlet-input"
                rows="3"
                placeholder="Optional supplier invoice/reference details"
            >{{ old('notes', $notesValue) }}</textarea>
        </div>
    </div>

    @php
        $allowMultipleItems = $allowMultipleItems ?? true;
        $oldItems = $oldItems ?? old('items', [
            ['product_id' => '', 'quantity' => 1, 'unit_price' => '0.00'],
        ]);
    @endphp

    <div class="table-header" style="margin-top: 1rem;">
        <div class="table-title">Purchase Items</div>
        @if ($allowMultipleItems)
            <button type="button" id="add-item-row" class="btn btn-secondary btn-sm">Add Item</button>
        @endif
    </div>

    <div class="table-container">
        <table class="table" id="purchase-items-table">
            <thead>
                <tr>
                    <th>Raw Material</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="purchase-items-body">
                @foreach ($oldItems as $index => $item)
                    <tr class="purchase-item-row">
                        <td>
                            <select name="items[{{ $index }}][product_id]" class="outlet-input item-product" required>
                                <option value="">Select Raw Material</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected((string) ($item['product_id'] ?? '') === (string) $product->id)>
                                        {{ $product->name }} ({{ $product->code }})
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input
                                name="items[{{ $index }}][quantity]"
                                type="number"
                                min="1"
                                class="outlet-input item-quantity"
                                value="{{ $item['quantity'] ?? 1 }}"
                                required
                            >
                        </td>
                        <td>
                            <input
                                name="items[{{ $index }}][unit_price]"
                                type="number"
                                min="0"
                                step="0.01"
                                class="outlet-input item-unit-price"
                                value="{{ $item['unit_price'] ?? '0.00' }}"
                                required
                            >
                        </td>
                        <td class="item-total">0.00</td>
                        <td>
                            @if ($allowMultipleItems)
                                <button type="button" class="btn btn-sm btn-secondary remove-item-row">Remove</button>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="outlet-form-grid" style="margin-top: 1rem;">
        <div class="outlet-form-group">
            <label>Grand Total</label>
            <input type="text" id="purchase-grand-total" class="outlet-input" value="0.00" readonly>
        </div>
    </div>

    @if ($allowMultipleItems)
        <template id="purchase-item-row-template">
            <tr class="purchase-item-row">
                <td>
                    <select class="outlet-input item-product" required>
                        <option value="">Select Raw Material</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->name }} ({{ $product->code }})
                            </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" min="1" class="outlet-input item-quantity" value="1" required>
                </td>
                <td>
                    <input type="number" min="0" step="0.01" class="outlet-input item-unit-price" value="0.00" required>
                </td>
                <td class="item-total">0.00</td>
                <td>
                    <button type="button" class="btn btn-sm btn-secondary remove-item-row">Remove</button>
                </td>
            </tr>
        </template>
    @endif

    <div class="outlet-form-actions">
        <a href="{{ route('rawMaterialPurchase.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</div>
