@php
    $statusLabels = \App\Models\Order::statusLabels();
    $oldItems = old('items', [
        ['item_category' => 'readymade', 'product_id' => '', 'product_variant_id' => '', 'quantity' => '1.00', 'unit_price' => '0.00'],
    ]);

    $initialStep = 1;
    if ($errors->any()) {
        $errorKeys = collect($errors->keys());
        if ($errorKeys->contains(fn($key) => str_starts_with($key, 'items'))) {
            $initialStep = 2;
        } elseif ($errorKeys->contains(fn($key) => in_array($key, ['payment_status', 'payment_method', 'advance_payment_amount', 'discount_amount'], true))) {
            $initialStep = 3;
        }
    }
@endphp

<div class="table-card outlet-form-card" id="order-form-wizard" data-initial-step="{{ $initialStep }}">
    <div class="table-header">
        <div class="table-title">{{ $title }}</div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div id="order-form-client-error" class="alert alert-danger" style="display:none;"></div>

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

    <div class="order-wizard-hero">
        <h2 class="order-wizard-title">Order Wizard</h2>
        <p class="order-wizard-subtitle">Create a perfect tailored order in 4 simple steps</p>
        <div class="order-wizard-track">
            <div class="order-wizard-line"></div>
            <div class="order-wizard-indicators">
                <div class="order-wizard-indicator" data-step-indicator="1">
                    <span class="order-wizard-badge">1</span>
                    <span class="order-wizard-label">Customer</span>
                </div>
                <div class="order-wizard-indicator" data-step-indicator="2">
                    <span class="order-wizard-badge">2</span>
                    <span class="order-wizard-label">Items</span>
                </div>
                <div class="order-wizard-indicator" data-step-indicator="3">
                    <span class="order-wizard-badge">3</span>
                    <span class="order-wizard-label">Payment</span>
                </div>
                <div class="order-wizard-indicator" data-step-indicator="4">
                    <span class="order-wizard-badge">4</span>
                    <span class="order-wizard-label">Review</span>
                </div>
            </div>
        </div>
    </div>

    <section class="order-wizard-step" data-step="1">
        <div class="outlet-form-grid">
            <div class="outlet-form-group">
                <label for="customer_id">Customer</label>
                <select id="customer_id" name="customer_id" class="outlet-input" required>
                    <option value="" disabled @selected((string) old('customer_id', $selectedCustomerId ?? '') === '')>Select Customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) old('customer_id', $selectedCustomerId ?? '') === (string) $customer->id)>
                            {{ $customer->name }}@if($customer->phone) ({{ $customer->phone }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="outlet-form-group">
                <label for="ordered_at">Order Date</label>
                <input
                    id="ordered_at"
                    name="ordered_at"
                    type="datetime-local"
                    class="outlet-input"
                    value="{{ old('ordered_at', now()->format('Y-m-d\TH:i')) }}"
                    required
                >
            </div>

            <div class="outlet-form-group">
                <label for="delivery_due_at">Delivery Date</label>
                <input
                    id="delivery_due_at"
                    name="delivery_due_at"
                    type="datetime-local"
                    class="outlet-input"
                    value="{{ old('delivery_due_at', now()->addDays(7)->format('Y-m-d\TH:i')) }}"
                    required
                >
            </div>

            <div class="outlet-form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="outlet-input" required>
                    @foreach (\App\Models\Order::creatableStatuses() as $status)
                        <option value="{{ $status }}" @selected(old('status', \App\Models\Order::STATUS_CONFIRMED) === $status)>
                            {{ $statusLabels[$status] ?? ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="outlet-form-group">
                <label for="worker_id">Worker</label>
                <select id="worker_id" name="worker_id" class="outlet-input">
                    <option value="">Unassigned</option>
                    @foreach (($workers ?? collect()) as $worker)
                        <option value="{{ $worker->id }}" @selected((string) old('worker_id') === (string) $worker->id)>
                            {{ $worker->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="outlet-form-group">
                <label for="worker_deadline_at">Worker Deadline</label>
                <input
                    id="worker_deadline_at"
                    name="worker_deadline_at"
                    type="datetime-local"
                    class="outlet-input"
                    value="{{ old('worker_deadline_at') }}"
                >
            </div>

            <div class="outlet-form-group outlet-form-group-full">
                <label for="notes">Notes</label>
                <textarea
                    id="notes"
                    name="notes"
                    class="outlet-input"
                    rows="3"
                    placeholder="Optional order notes"
                >{{ old('notes') }}</textarea>
            </div>
        </div>
    </section>

    <section class="order-wizard-step" data-step="2" style="display:none;">
        <div class="table-header" style="margin-top: 1rem;">
            <div class="table-title">Order Items</div>
            <button type="button" id="add-item-row" class="btn btn-secondary btn-sm">Add Item</button>
        </div>

        <div class="table-container">
            <table class="table" id="order-items-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Product / Garment</th>
                        <th>Variant</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="order-items-body">
                    @foreach ($oldItems as $index => $item)
                        @php
                            $category = (string) ($item['item_category'] ?? 'readymade');
                            $isCustom = $category === 'custom';
                            $customMeasurements = collect(data_get($item, 'custom.measurements', []))->values();
                        @endphp
                        <tr class="order-item-row" data-row-category="{{ $category }}">
                            <td>
                                <span class="order-item-category-label">{{ ucfirst($category) }}</span>
                                <input type="hidden" name="items[{{ $index }}][item_category]" value="{{ $category }}" class="item-category-input">
                            </td>
                            <td>
                                @if (!$isCustom)
                                    <select name="items[{{ $index }}][product_id]" class="outlet-input item-product" required>
                                        <option value="">Select Product</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" @selected((string) ($item['product_id'] ?? '') === (string) $product->id)>
                                                {{ $product->name }} ({{ $product->sku }}) - Unit: {{ $product->unit?->symbol ?: ($product->unit?->name ?: 'N/A') }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <div class="custom-item-summary item-custom-summary">
                                        {{ data_get($item, 'custom.garment_title') ?: 'Custom Garment' }}
                                        <small>
                                            Fabric: {{ ucfirst((string) data_get($item, 'custom.fabric_source', 'own')) }}
                                            @if (data_get($item, 'custom.fabric_source') === 'stock')
                                                | Qty: {{ data_get($item, 'custom.fabric_quantity', '0') }}
                                            @endif
                                        </small>
                                    </div>
                                    <input type="hidden" name="items[{{ $index }}][custom][garment_type_id]" value="{{ data_get($item, 'custom.garment_type_id', '') }}" class="item-custom-garment-type-id">
                                    <input type="hidden" name="items[{{ $index }}][custom][garment_title]" value="{{ data_get($item, 'custom.garment_title', '') }}" class="item-custom-garment-title">
                                    <input type="hidden" name="items[{{ $index }}][custom][fabric_source]" value="{{ data_get($item, 'custom.fabric_source', 'own') }}" class="item-custom-fabric-source">
                                    <input type="hidden" name="items[{{ $index }}][custom][fabric_product_id]" value="{{ data_get($item, 'custom.fabric_product_id', '') }}" class="item-custom-fabric-product-id">
                                    <input type="hidden" name="items[{{ $index }}][custom][fabric_product_variant_id]" value="{{ data_get($item, 'custom.fabric_product_variant_id', '') }}" class="item-custom-fabric-product-variant-id">
                                    <input type="hidden" name="items[{{ $index }}][custom][fabric_quantity]" value="{{ data_get($item, 'custom.fabric_quantity', '') }}" class="item-custom-fabric-quantity">
                                    <input type="hidden" name="items[{{ $index }}][custom][design_note]" value="{{ data_get($item, 'custom.design_note', '') }}" class="item-custom-design-note">
                                    <div class="item-custom-design-image-file-container"></div>
                                    <div class="item-custom-measurement-container">
                                        @foreach ($customMeasurements as $mIndex => $measurement)
                                            <input type="hidden" name="items[{{ $index }}][custom][measurements][{{ $mIndex }}][type]" value="{{ $measurement['type'] ?? '' }}" class="item-custom-measurement-type">
                                            <input type="hidden" name="items[{{ $index }}][custom][measurements][{{ $mIndex }}][measurement]" value="{{ $measurement['measurement'] ?? '' }}" class="item-custom-measurement-value">
                                            <input type="hidden" name="items[{{ $index }}][custom][measurements][{{ $mIndex }}][unit]" value="{{ $measurement['unit'] ?? '' }}" class="item-custom-measurement-unit">
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if (!$isCustom)
                                    <select
                                        name="items[{{ $index }}][product_variant_id]"
                                        class="outlet-input item-variant"
                                        data-selected="{{ $item['product_variant_id'] ?? '' }}"
                                    >
                                        <option value="">No Variant</option>
                                    </select>
                                @else
                                    <span class="item-custom-variant-placeholder">-</span>
                                @endif
                            </td>
                            <td>
                                <input
                                    name="items[{{ $index }}][quantity]"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    class="outlet-input item-quantity"
                                    value="{{ $item['quantity'] ?? '1.00' }}"
                                    required
                                >
                            </td>
                            <td class="item-quantity-unit">
                                @if ($isCustom)
                                    pcs
                                @else
                                    @php
                                        $selectedProductId = (int) ($item['product_id'] ?? 0);
                                        $selectedProduct = $products->firstWhere('id', $selectedProductId);
                                        $unitLabel = $selectedProduct?->unit?->symbol ?: ($selectedProduct?->unit?->name ?: '-');
                                    @endphp
                                    {{ $unitLabel }}
                                @endif
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
                                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                    <button type="button" class="btn btn-sm btn-danger remove-item-row">
                                        <i class="fas fa-trash-alt"></i> Remove
                                    </button>
                                    @if ($isCustom)
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-custom-row">
                                            <i class="fas fa-pen"></i> Edit
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="outlet-form-grid" style="margin-top: 1rem;">
            <div class="outlet-form-group">
                <label>Grand Total</label>
                <input type="text" id="order-grand-total" class="outlet-input" value="0.00" readonly>
            </div>
        </div>
    </section>

    <section class="order-wizard-step" data-step="3" style="display:none;">
        <div class="outlet-form-grid">
            <div class="outlet-form-group">
                <label for="payment_status">Payment Status</label>
                <select id="payment_status" name="payment_status" class="outlet-input" required>
                    @foreach (\App\Models\Order::availablePaymentStatuses() as $paymentStatus)
                        <option value="{{ $paymentStatus }}" @selected(old('payment_status', \App\Models\Order::PAYMENT_STATUS_UNPAID) === $paymentStatus)>
                            {{ ucfirst($paymentStatus) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="outlet-form-group">
                <label for="payment_method">Payment Method</label>
                <input
                    id="payment_method"
                    name="payment_method"
                    type="text"
                    class="outlet-input"
                    value="{{ old('payment_method') }}"
                    placeholder="Cash, Card, Bank Transfer, etc."
                >
            </div>

            <div class="outlet-form-group">
                <label for="advance_payment_amount">Advance Payment Amount</label>
                <input
                    id="advance_payment_amount"
                    name="advance_payment_amount"
                    type="number"
                    min="0"
                    step="0.01"
                    class="outlet-input"
                    value="{{ old('advance_payment_amount', '0.00') }}"
                >
            </div>

            <div class="outlet-form-group">
                <label for="discount_amount">Discount Amount</label>
                <input
                    id="discount_amount"
                    name="discount_amount"
                    type="number"
                    min="0"
                    step="0.01"
                    class="outlet-input"
                    value="{{ old('discount_amount', '0.00') }}"
                >
            </div>
        </div>
    </section>

    <section class="order-wizard-step" data-step="4" style="display:none;">
        <div class="review-layout">
            <div class="review-block">
                <div class="review-block-title">Order Summary</div>
                <div class="review-kpi-grid">
                    <div class="review-kpi-card">
                        <div class="review-kpi-label">Customer</div>
                        <div id="review-customer" class="review-kpi-value">-</div>
                    </div>
                    <div class="review-kpi-card">
                        <div class="review-kpi-label">Order Date</div>
                        <div id="review-order-date" class="review-kpi-value">-</div>
                    </div>
                    <div class="review-kpi-card">
                        <div class="review-kpi-label">Delivery Date</div>
                        <div id="review-delivery-date" class="review-kpi-value">-</div>
                    </div>
                    <div class="review-kpi-card">
                        <div class="review-kpi-label">Status</div>
                        <div id="review-status" class="review-kpi-value">-</div>
                    </div>
                    <div class="review-kpi-card">
                        <div class="review-kpi-label">Worker</div>
                        <div id="review-worker" class="review-kpi-value">-</div>
                    </div>
                    <div class="review-kpi-card">
                        <div class="review-kpi-label">Worker Deadline</div>
                        <div id="review-worker-deadline" class="review-kpi-value">-</div>
                    </div>
                    <div class="review-kpi-card">
                        <div class="review-kpi-label">Payment Status</div>
                        <div id="review-payment-status" class="review-kpi-value">-</div>
                    </div>
                    <div class="review-kpi-card">
                        <div class="review-kpi-label">Payment Method</div>
                        <div id="review-payment-method" class="review-kpi-value">-</div>
                    </div>
                    <div class="review-kpi-card">
                        <div class="review-kpi-label">Total Quantity</div>
                        <div id="review-total-items" class="review-kpi-value">0.00</div>
                    </div>
                    <div class="review-kpi-card">
                        <div class="review-kpi-label">Order Total</div>
                        <div id="review-order-total" class="review-kpi-value">0.00</div>
                    </div>
                    <div class="review-kpi-card">
                        <div class="review-kpi-label">Discount</div>
                        <div id="review-discount" class="review-kpi-value">0.00</div>
                    </div>
                    <div class="review-kpi-card">
                        <div class="review-kpi-label">Advance Paid</div>
                        <div id="review-advance" class="review-kpi-value">0.00</div>
                    </div>
                    <div class="review-kpi-card">
                        <div class="review-kpi-label">Remaining Due</div>
                        <div id="review-remaining" class="review-kpi-value">0.00</div>
                    </div>
                </div>
            </div>

            <div class="review-block">
                <div class="review-block-title">Items Review</div>
                <div id="review-items-list" class="review-items-list"></div>
            </div>

            <div class="review-block">
                <div class="review-block-title">Notes</div>
                <div id="review-notes" class="review-notes-text">-</div>
            </div>
        </div>
    </section>

    <template id="order-item-standard-row-template">
        <tr class="order-item-row" data-row-category="readymade">
            <td>
                <span class="order-item-category-label">Readymade</span>
                <input type="hidden" class="item-category-input" value="readymade">
            </td>
            <td>
                <select class="outlet-input item-product" required>
                    <option value="">Select Product</option>
                    @foreach ($products as $product)
                        <option
                            value="{{ $product->id }}"
                            data-category="{{ $product->category?->slug }}"
                        >
                            {{ $product->name }} ({{ $product->sku }}) - Unit: {{ $product->unit?->symbol ?: ($product->unit?->name ?: 'N/A') }}
                        </option>
                    @endforeach
                </select>
            </td>
            <td>
                <select class="outlet-input item-variant">
                    <option value="">No Variant</option>
                </select>
            </td>
            <td>
                <input type="number" min="0.01" step="0.01" class="outlet-input item-quantity" value="1.00" required>
            </td>
            <td class="item-quantity-unit">-</td>
            <td>
                <input type="number" min="0" step="0.01" class="outlet-input item-unit-price" value="0.00" required>
            </td>
            <td class="item-total">0.00</td>
            <td>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <button type="button" class="btn btn-sm btn-danger remove-item-row">
                        <i class="fas fa-trash-alt"></i> Remove
                    </button>
                </div>
            </td>
        </tr>
    </template>

    <template id="order-item-custom-row-template">
        <tr class="order-item-row" data-row-category="custom">
            <td>
                <span class="order-item-category-label">Custom</span>
                <input type="hidden" class="item-category-input" value="custom">
            </td>
            <td>
                <div class="custom-item-summary item-custom-summary">Custom Garment</div>
                <input type="hidden" class="item-custom-garment-type-id">
                <input type="hidden" class="item-custom-garment-title">
                <input type="hidden" class="item-custom-fabric-source" value="own">
                <input type="hidden" class="item-custom-fabric-product-id">
                <input type="hidden" class="item-custom-fabric-product-variant-id">
                <input type="hidden" class="item-custom-fabric-quantity">
                <input type="hidden" class="item-custom-design-note">
                <div class="item-custom-design-image-file-container"></div>
                <div class="item-custom-measurement-container"></div>
            </td>
            <td><span class="item-custom-variant-placeholder">-</span></td>
            <td>
                <input type="number" min="0.01" step="0.01" class="outlet-input item-quantity" value="1.00" required>
            </td>
            <td class="item-quantity-unit">pcs</td>
            <td>
                <input type="number" min="0" step="0.01" class="outlet-input item-unit-price" value="0.00" required>
            </td>
            <td class="item-total">0.00</td>
            <td>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <button type="button" class="btn btn-sm btn-danger remove-item-row">
                        <i class="fas fa-trash-alt"></i> Remove
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary edit-custom-row">
                        <i class="fas fa-pen"></i> Edit
                    </button>
                </div>
            </td>
        </tr>
    </template>

    <div class="outlet-form-actions">
        <a href="{{ route('order.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="button" id="order-wizard-prev" class="btn btn-secondary" style="display:none;">Previous</button>
        <button type="button" id="order-wizard-next" class="btn btn-primary">Next</button>
        <button type="submit" id="order-wizard-submit" class="btn btn-primary" style="display:none;">{{ $submitLabel }}</button>
    </div>
</div>

<div class="order-modal" id="item-category-modal" style="display:none;">
    <div class="order-modal-backdrop" data-close-modal="item-category-modal"></div>
    <div class="order-modal-dialog">
        <div class="order-modal-header">
            <h3>Select Item Category</h3>
            <button type="button" class="order-modal-close" data-close-modal="item-category-modal">&times;</button>
        </div>
        <div class="order-modal-body">
            <div class="category-option-grid">
                <button type="button" class="btn btn-secondary btn-block" data-pick-item-category="custom">Custom</button>
                <button type="button" class="btn btn-secondary btn-block" data-pick-item-category="fabric">Fabric</button>
                <button type="button" class="btn btn-secondary btn-block" data-pick-item-category="readymade">Readymade</button>
            </div>
        </div>
    </div>
</div>

<div class="order-modal" id="custom-item-modal" style="display:none;">
    <div class="order-modal-backdrop"></div>
    <div class="order-modal-dialog custom-item-modal-dialog">
        <div class="order-modal-header">
            <h3>Custom Item Wizard</h3>
            <button type="button" class="order-modal-close" id="close-custom-item-modal">&times;</button>
        </div>

        <div class="order-modal-body">
            <div id="custom-wizard-error" class="alert alert-danger" style="display:none;"></div>

            <div class="custom-modal-wizard-hero">
                <h4 class="custom-modal-wizard-title">Custom Item Wizard</h4>
                <p class="custom-modal-wizard-subtitle">Configure garment, measurements, fabric, and design</p>
                <div class="custom-modal-wizard-track">
                    <div class="custom-modal-wizard-line"></div>
                    <div class="custom-modal-wizard-indicators">
                        <div class="custom-wizard-step is-active" data-custom-step-indicator="1">
                            <span class="custom-wizard-badge">1</span>
                            <span class="custom-wizard-label">Garment</span>
                        </div>
                        <div class="custom-wizard-step" data-custom-step-indicator="2">
                            <span class="custom-wizard-badge">2</span>
                            <span class="custom-wizard-label">Fabric</span>
                        </div>
                        <div class="custom-wizard-step" data-custom-step-indicator="3">
                            <span class="custom-wizard-badge">3</span>
                            <span class="custom-wizard-label">Design</span>
                        </div>
                        <div class="custom-wizard-step" data-custom-step-indicator="4">
                            <span class="custom-wizard-badge">4</span>
                            <span class="custom-wizard-label">Review</span>
                        </div>
                    </div>
                </div>
            </div>

            <section class="custom-wizard-panel" data-custom-step="1">
                <div class="outlet-form-group">
                    <label for="custom-garment-type">Garment Type</label>
                    <select id="custom-garment-type" class="outlet-input">
                        <option value="">Select Garment Type</option>
                    </select>
                </div>
                <div class="table-container" style="margin-top:10px;">
                    <table class="table" id="custom-measurement-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Measurement</th>
                                <th>Unit</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>

            <section class="custom-wizard-panel" data-custom-step="2" style="display:none;">
                <div class="outlet-form-group">
                    <label>Fabric Source</label>
                    <div class="custom-radio-group">
                        <label><input type="radio" name="custom-fabric-source" value="own" checked> Customer Own Fabric</label>
                        <label><input type="radio" name="custom-fabric-source" value="stock"> Use Stock Fabric</label>
                    </div>
                </div>
                <div id="custom-stock-fabric-fields" style="display:none;">
                    <div class="outlet-form-group">
                        <label for="custom-stock-fabric-product">Fabric Product from Stock</label>
                        <select id="custom-stock-fabric-product" class="outlet-input">
                            <option value="">Select Fabric Product</option>
                        </select>
                    </div>
                    <div class="outlet-form-group">
                        <label for="custom-stock-fabric-variant">Fabric Variant (if available)</label>
                        <select id="custom-stock-fabric-variant" class="outlet-input">
                            <option value="">No Variant</option>
                        </select>
                    </div>
                    <div class="outlet-form-group">
                        <label for="custom-stock-fabric-qty">Fabric Quantity</label>
                        <div class="custom-fabric-qty-wrap">
                            <input id="custom-stock-fabric-qty" type="number" min="0.01" step="0.01" class="outlet-input" value="1.00">
                            <span id="custom-stock-fabric-qty-unit" class="custom-fabric-qty-unit">-</span>
                        </div>
                        <small id="custom-stock-fabric-price-hint" class="custom-fabric-price-hint"></small>
                    </div>
                </div>
            </section>

            <section class="custom-wizard-panel" data-custom-step="3" style="display:none;">
                <div class="outlet-form-group">
                    <label for="custom-design-note">Stitching Design Note</label>
                    <textarea id="custom-design-note" class="outlet-input" rows="4" placeholder="Design details, collar/cuff pattern, pockets, lining, etc."></textarea>
                </div>
                <div class="outlet-form-group">
                    <label for="custom-design-image">Design Images</label>
                    <div id="custom-design-image-holder">
                        <input id="custom-design-image" type="file" class="outlet-input" accept="image/*" multiple>
                    </div>
                </div>
            </section>

            <section class="custom-wizard-panel" data-custom-step="4" style="display:none;">
                <div class="custom-review-layout">
                    <div class="custom-review-card">
                        <div class="custom-review-label">Garment</div>
                        <div id="custom-review-garment" class="custom-review-value">-</div>
                    </div>
                    <div class="custom-review-card">
                        <div class="custom-review-label">Fabric Source</div>
                        <div id="custom-review-fabric" class="custom-review-value">-</div>
                    </div>
                    <div class="custom-review-card">
                        <div class="custom-review-label">Stitching Note</div>
                        <div id="custom-review-design-note" class="custom-review-value">-</div>
                    </div>
                    <div class="custom-review-card">
                        <div class="custom-review-label">Design Images</div>
                        <div id="custom-review-design-image" class="custom-review-value">-</div>
                    </div>
                    <div class="custom-review-card custom-review-card-full">
                        <div class="custom-review-label">Measurements</div>
                        <div id="custom-review-measurements" class="custom-review-list"></div>
                    </div>
                </div>
            </section>
        </div>

        <div class="order-modal-footer">
            <button type="button" class="btn btn-secondary" id="custom-wizard-prev" style="display:none;">Previous</button>
            <button type="button" class="btn btn-primary" id="custom-wizard-next">Next</button>
            <button type="button" class="btn btn-primary" id="custom-wizard-save" style="display:none;">Add Item</button>
        </div>
    </div>
</div>
