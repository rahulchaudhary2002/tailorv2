@extends('layouts.app')

@section('title', 'Create Order')

@section('content')
@php
// Build lightweight payloads for JS
$productPayload = $products->map(function ($product) use ($productDefaultPrices, $variantDefaultPrices, $productAvailableQty, $variantAvailableQty) {
    return [
        'id' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'category' => $product->category?->slug, // expect 'fabrics' for fabric
        'unitLabel' => $product->unit?->symbol ?: ($product->unit?->name ?: ''),
        'availableQty' => array_key_exists($product->id, $productAvailableQty)
            ? (float) $productAvailableQty[$product->id]
            : 0.0,
        'defaultPrice' => array_key_exists($product->id, $productDefaultPrices)
            ? (float) $productDefaultPrices[$product->id]
            : null,
        'variants' => $product->variants->map(function ($variant) use ($variantDefaultPrices, $variantAvailableQty) {
            $parts = array_filter([$variant->sku, $variant->size, $variant->color, $variant->material]);
            return [
                'id' => $variant->id,
                'label' => implode(' | ', $parts),
                'availableQty' => array_key_exists($variant->id, $variantAvailableQty)
                    ? (float) $variantAvailableQty[$variant->id]
                    : 0.0,
                'defaultPrice' => array_key_exists($variant->id, $variantDefaultPrices)
                    ? (float) $variantDefaultPrices[$variant->id]
                    : null,
            ];
        })->values(),
    ];
})->values();

$garmentPayload = $garmentTypes->map(function ($garmentType) {
    return [
        'id' => $garmentType->id,
        'title' => $garmentType->title,
        'amount' => (float) $garmentType->amount,
        'tailoringPackages' => $garmentType->tailoringPackages->map(function ($package) {
            return [
                'id' => $package->id,
                'name' => $package->name,
                'amount' => (float) $package->amount,
                'description' => $package->description,
                'order' => (int) $package->order,
            ];
        })->values(),
        'measurements' => $garmentType->measurements->map(function ($m) {
            return [
                'title' => $m->title,
                'unit' => $m->unit?->symbol ?: ($m->unit?->name ?: ''),
            ];
        })->values(),
    ];
})->values();

$customerMeasurementPayload = $customers->mapWithKeys(function ($customer) {
    $byGarment = $customer->customerGarmentTypes
        ->mapWithKeys(function ($cgt) {
            return [
                (string) $cgt->garment_type_id => $cgt->measurements->map(function ($m) {
                    return [
                        'type' => $m->type,
                        'measurement' => $m->measurement,
                        'unit' => $m->unit,
                    ];
                })->values(),
            ];
        });

    return [(string) $customer->id => $byGarment];
});

$customerLookupPayload = $customers->map(function ($customer) {
    return [
        'id' => $customer->id,
        'name' => $customer->name,
        'phone' => $customer->phone,
    ];
})->values();
@endphp

<form id="orderForm" action="{{ route('order.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- Hidden container where bill items are converted to items[i][...] --}}
    <div id="itemsHiddenInputs"></div>
    <input type="hidden" id="print_bill" name="print_bill" value="{{ old('print_bill', '0') }}">

    <div class="tp-container">
        @if (session('error'))
            <div class="tp-alert tp-alert-danger">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="tp-alert tp-alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="tp-alert tp-alert-danger">
                <strong>Please fix the following errors:</strong>
                <ul style="margin-top: 8px; padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="tp-grid">
            {{-- LEFT: Order entry --}}
            <div class="tp-card">
                <h2 class="tp-h2"><i class="fas fa-edit"></i> Order Entry</h2>

                <div class="tp-form-grid">
                    <div class="tp-form-group">
                        <label>Customer *</label>
                        <div class="tp-customer-check-row">
                            <input id="customerPhone" type="text" class="tp-input" placeholder="Customer mobile number">
                            <button type="button" id="checkCustomerBtn" class="tp-btn tp-btn-secondary">Check</button>
                        </div>
                        <div id="customerCheckMessage" class="tp-customer-check-message" style="display:none;"></div>
                        <input
                            id="customer_id"
                            name="customer_id"
                            type="hidden"
                            value="{{ old('customer_id', $selectedCustomerId ?? '') }}">
                    </div>

                    <div class="tp-form-group">
                        <label>Order Date *</label>
                        <input
                            id="ordered_at"
                            name="ordered_at"
                            type="datetime-local"
                            class="tp-input"
                            value="{{ old('ordered_at', now()->format('Y-m-d\TH:i')) }}"
                            required>
                    </div>

                    <div class="tp-form-group">
                        <label>Delivery Date *</label>
                        <input
                            id="delivery_due_at"
                            name="delivery_due_at"
                            type="datetime-local"
                            class="tp-input"
                            value="{{ old('delivery_due_at', now()->addDays(7)->format('Y-m-d\TH:i')) }}"
                            required>
                    </div>

                    <div class="tp-form-group">
                        <label>Status *</label>
                        <select id="status" name="status" class="tp-input" required>
                            @foreach (\App\Models\Order::creatableStatuses() as $status)
                                <option value="{{ $status }}" @selected(old('status', \App\Models\Order::STATUS_CONFIRMED)===$status)>
                                    {{ \App\Models\Order::statusLabels()[$status] ?? ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="tp-form-group">
                        <label>Worker</label>
                        <select id="worker_id" name="worker_id" class="tp-input">
                            <option value="">Unassigned</option>
                            @foreach (($workers ?? collect()) as $worker)
                                <option value="{{ $worker->id }}" @selected((string) old('worker_id')===(string) $worker->id)>
                                    {{ $worker->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="tp-form-group">
                        <label>Worker Deadline</label>
                        <input id="worker_deadline_at" name="worker_deadline_at" type="datetime-local" class="tp-input" value="{{ old('worker_deadline_at') }}">
                    </div>

                    <div class="tp-form-group tp-form-group-full">
                        <label>Notes</label>
                        <textarea id="notes" name="notes" class="tp-input" rows="3" placeholder="Optional order notes">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <hr class="tp-hr">

                <div class="tp-form-grid">
                    <div class="tp-form-group">
                        <label>Product Category *</label>
                        <select id="productCategory" class="tp-input">
                            <option value="fabric">Fabric</option>
                            <option value="readymade">Ready-Made</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>

                    <div class="tp-form-group">
                        <label>Select Product *</label>
                        <select id="productSelect" class="tp-input">
                            <option value="">-- Select Product --</option>
                        </select>
                    </div>

                    <div class="tp-form-group">
                        <label>Variant</label>
                        <select id="variantSelect" class="tp-input" disabled>
                            <option value="">No Variant</option>
                        </select>
                    </div>

                    <div class="tp-form-group">
                        <label>Quantity</label>
                        <input id="quantity" type="number" min="0.01" step="0.01" class="tp-input" value="1.00">
                        <small class="tp-hint" id="qtyUnitHint">-</small>
                    </div>

                    <div class="tp-form-group">
                        <label>Unit Price</label>
                        <input id="unitPrice" type="number" min="0" step="0.01" class="tp-input" placeholder="Auto from default price">
                        <small class="tp-hint">For custom: this is fabric/product price only. Tailoring is separate.</small>
                    </div>

                    <div class="tp-form-group tp-form-group-full">
                        <button type="button" id="addToBill" class="tp-btn">
                            <i class="fas fa-plus-circle"></i> Add to Bill
                        </button>
                    </div>
                </div>
            </div>

            {{-- RIGHT: Live bill --}}
            <div class="tp-card" id="billPrintArea">
                <div class="tp-bill-header">
                    <div class="tp-bill-title" id="billType">Estimated Bill</div>
                    <div class="tp-toggle">
                        <span>VAT (13%)</span>
                        <label class="tp-switch">
                            <input type="checkbox" id="vatToggle">
                            <span class="tp-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="tp-bill-customer">
                    <div><strong>Customer:</strong> <span id="billCustomer">-</span></div>
                </div>

                <div class="tp-bill-items" id="billItems"></div>

                <div class="tp-tailoring-breakdown" id="tailoringBreakdown" style="display:none;"></div>

                <div class="tp-discount">
                    <h3 class="tp-h3"><i class="fas fa-tag"></i> Discount</h3>
                    <div class="tp-discount-row">
                        <select id="discountType" class="tp-input">
                            <option value="none">No Discount</option>
                            <option value="flat">Flat Amount</option>
                            <option value="percent">Percentage (%)</option>
                        </select>
                        <input type="number" id="discountValue" class="tp-input" placeholder="Value" disabled>
                        <button type="button" id="applyDiscount" class="tp-btn tp-btn-secondary">Apply</button>
                    </div>
                    <div id="discountDisplay" class="tp-discount-display"></div>
                </div>

                <div class="tp-summary">
                    <div class="tp-row">
                        <span>Subtotal (Fabric/Ready-made):</span>
                        <span id="subtotalFabric">0.00</span>
                    </div>
                    <div class="tp-row">
                        <span>Subtotal (Custom products):</span>
                        <span id="subtotalCustom">0.00</span>
                    </div>
                    <div class="tp-row">
                        <span>Tailoring charges:</span>
                        <span id="tailoringTotal">0.00</span>
                    </div>
                    <div class="tp-row">
                        <span>Discount:</span>
                        <span id="discountTotal">0.00</span>
                    </div>
                    <div class="tp-row" id="vatRow" style="display:none;">
                        <span>VAT (13%):</span>
                        <span id="vatAmount">0.00</span>
                    </div>
                    <div class="tp-row tp-total">
                        <span>Grand Total:</span>
                        <span id="grandTotal">0.00</span>
                    </div>
                </div>

                <div class="tp-payment">
                    <h3 class="tp-h3"><i class="fas fa-wallet"></i> Payment Details</h3>
                    <div class="tp-form-grid">
                        <div class="tp-form-group">
                            <label for="payment_status">Payment Status *</label>
                            <select id="payment_status" name="payment_status" class="tp-input" required>
                                @foreach (\App\Models\Order::availablePaymentStatuses() as $paymentStatus)
                                    <option value="{{ $paymentStatus }}" @selected(old('payment_status', \App\Models\Order::PAYMENT_STATUS_UNPAID) === $paymentStatus)>
                                        {{ ucfirst($paymentStatus) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="tp-form-group">
                            <label for="payment_method">Payment Method</label>
                            <input
                                id="payment_method"
                                name="payment_method"
                                type="text"
                                class="tp-input"
                                value="{{ old('payment_method') }}"
                                placeholder="Cash, Card, Bank Transfer...">
                        </div>
                        <div class="tp-form-group">
                            <label for="advance_payment_amount">Advance Payment</label>
                            <input
                                id="advance_payment_amount"
                                name="advance_payment_amount"
                                type="number"
                                min="0"
                                step="0.01"
                                class="tp-input"
                                value="{{ old('advance_payment_amount', '0') }}">
                        </div>
                    </div>
                    <input
                        id="discount_amount"
                        name="discount_amount"
                        type="hidden"
                        value="{{ old('discount_amount', '0') }}">

                    <div class="tp-summary" style="margin-top:10px;">
                        <div class="tp-row">
                            <span>Payable Amount:</span>
                            <span id="payableAmount">NPR 0.00</span>
                        </div>
                        <div class="tp-row">
                            <span>Due Amount:</span>
                            <span id="dueAmount">NPR 0.00</span>
                        </div>
                    </div>
                </div>

                <div class="tp-actions">
                    <button type="button" id="printBill" class="tp-btn tp-btn-secondary">
                        <i class="fas fa-print"></i> Print Bill
                    </button>
                    <button type="submit" id="saveOrder" class="tp-btn tp-btn-success">
                        <i class="fas fa-save"></i> Save Order
                    </button>
                    <button type="button" id="clearBill" class="tp-btn tp-btn-danger">
                        <i class="fas fa-trash-alt"></i> Clear
                    </button>
                </div>
            </div>
        </section>
    </div>

    {{-- Create Customer Modal --}}
    <div class="tp-modal-overlay" id="createCustomerModal" style="display:none;">
        <div class="tp-modal" style="max-width:640px;">
            <div class="tp-modal-header">
                <h3><i class="fas fa-user-plus"></i> Create Customer</h3>
                <button type="button" class="tp-modal-close" id="closeCustomerModal">&times;</button>
            </div>
            <div class="tp-modal-body">
                <div class="tp-form-grid">
                    <div class="tp-form-group">
                        <label for="newCustomerPhone">Phone *</label>
                        <input id="newCustomerPhone" type="text" class="tp-input" readonly>
                    </div>
                    <div class="tp-form-group">
                        <label for="newCustomerName">Customer Name *</label>
                        <input id="newCustomerName" type="text" class="tp-input" placeholder="Full name">
                    </div>
                    <div class="tp-form-group">
                        <label for="newCustomerEmail">Email *</label>
                        <input id="newCustomerEmail" type="email" class="tp-input" placeholder="name@example.com">
                    </div>
                    <div class="tp-form-group">
                        <label for="newCustomerType">Customer Type *</label>
                        <select id="newCustomerType" class="tp-input">
                            <option value="retail">Retail</option>
                            <option value="wholesale">Wholesale</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    <div class="tp-form-group tp-form-group-full">
                        <label for="newCustomerAddress">Address *</label>
                        <input id="newCustomerAddress" type="text" class="tp-input" placeholder="Address">
                    </div>
                </div>
            </div>
            <div class="tp-modal-footer">
                <button type="button" class="tp-btn tp-btn-secondary" id="cancelCustomerModal">Cancel</button>
                <button type="button" id="createCustomerBtn" class="tp-btn tp-btn-success">Create Customer</button>
            </div>
        </div>
    </div>

    {{-- Measurement Modal --}}
    <div class="tp-modal-overlay" id="measurementModal" style="display:none;">
        <div class="tp-modal">
            <div class="tp-modal-header">
                <h3><i class="fas fa-ruler-combined"></i> Measurements & Tailoring</h3>
                <button type="button" class="tp-modal-close" id="closeModal">&times;</button>
            </div>

            <div class="tp-modal-body">
                <div class="tp-modal-info">
                    <div><strong>Product:</strong> <span id="modalProductName">-</span></div>
                    <div><strong>Unit Price:</strong> <span id="modalProductPrice">0.00</span></div>
                    <div><strong>Qty:</strong> <span id="modalProductQuantity">0.00</span></div>
                </div>

                <div class="tp-form-group" style="margin-top: 14px;">
                    <label>Garment Type *</label>
                    <select id="garmentType" class="tp-input">
                        <option value="">Select Garment Type</option>
                    </select>
                </div>

                <div class="tp-modal-section">
                    <h4><i class="fas fa-tshirt"></i> Fabric Source</h4>
                    <div class="tp-form-group">
                        <label style="display:flex; gap:8px; align-items:center;">
                            <input type="radio" name="customFabricSource" id="customFabricOwn" value="own" checked>
                            Customer Fabric
                        </label>
                        <label style="display:flex; gap:8px; align-items:center; margin-top:6px;">
                            <input type="radio" name="customFabricSource" id="customFabricStock" value="stock">
                            Stock Fabric (from current outlet)
                        </label>
                    </div>
                    <div id="customStockFabricFields" style="display:none;">
                        <div class="tp-form-group">
                            <label for="customStockFabricQty">Stock Fabric Quantity *</label>
                            <input id="customStockFabricQty" type="number" min="0.01" step="0.01" class="tp-input" value="1.00">
                            <small id="customStockFabricHint" class="tp-hint"></small>
                        </div>
                    </div>
                </div>

                <div class="tp-modal-section">
                    <h4>Measurement Details</h4>
                    <div id="measurementFields" class="tp-measurement-grid"></div>
                    <small class="tp-hint">Fields come from your GarmentType measurements.</small>
                </div>

                <div class="tp-modal-section tp-stitching">
                    <h4><i class="fas fa-cut"></i> Select Tailoring Package</h4>
                    <div class="tp-tailoring-options" id="tailoringOptions"></div>

                    <div id="selectedTailoringPackage" class="tp-selected-package" style="display:none;">
                        <strong>Selected:</strong> <span id="selectedPackageName">-</span>
                        — NPR <span id="selectedPackagePrice">0.00</span>
                    </div>

                    <div class="tp-form-group" style="margin-top: 12px;">
                        <label>Design Note</label>
                        <textarea id="customDesignNote" class="tp-input" rows="3" placeholder="Design details..."></textarea>
                    </div>

                    <div class="tp-form-group">
                        <label>Design Images</label>
                        <input id="customDesignImages" type="file" class="tp-input" accept="image/*" multiple>
                        <small class="tp-hint">Images will be submitted with the form.</small>
                    </div>
                </div>
            </div>

            <div class="tp-modal-footer">
                <button type="button" class="tp-btn tp-btn-secondary" id="cancelMeasurement">Cancel</button>
                <button type="button" class="tp-btn tp-btn-success" id="saveMeasurement">
                    Add Custom Item
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@section('page-specific-style')
<style>
/* (Your CSS unchanged – pasted exactly as you sent) */
:root{--primary:#1a365d;--secondary:#2d4a7c;--accent:#c9a96e;--light:#f8f9fa;--dark:#343a40;--success:#28a745;--danger:#dc3545;--muted:#6c757d;--radius:10px;--shadow:0 4px 12px rgba(0,0,0,.10);}
.tp-container{max-width:1400px;margin:0 auto;padding:18px;}
.tp-alert{margin-top:16px;padding:12px 14px;border-radius:10px;}
.tp-alert-danger{background:#ffe8ea;color:#7a0b18;border:1px solid #ffccd1;}
.tp-alert-success{background:#e7f7ed;color:#0d5a2b;border:1px solid #bfe8cf;}
.tp-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
@media(max-width:1024px){.tp-grid{grid-template-columns:1fr;}}
.tp-card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);padding:16px;}
.tp-h2{margin:0 0 14px;color:var(--primary);border-bottom:2px solid #eee;padding-bottom:8px;}
.tp-h2 i{color:var(--accent);margin-right:8px;}
.tp-h3{margin:0 0 10px;color:var(--primary);}
.tp-h3 i{color:var(--accent);margin-right:8px;}
.tp-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;}
.tp-form-group{display:flex;flex-direction:column;gap:6px;}
.tp-form-group-full{grid-column:1/-1;}
.tp-input{width:100%;padding:10px 12px;border:1px solid #cfd6df;border-radius:10px;font-size:14px;}
.tp-customer-check-row{display:flex;gap:8px;align-items:center;}
.tp-customer-check-row .tp-input{flex:1;}
.tp-customer-check-message{font-size:12px;padding:6px 8px;border-radius:8px;background:#f3f6fb;color:#415265;border:1px solid #dbe4ef;}
.tp-hint{color:#6a7785;font-size:12px;}
.tp-btn{background:var(--primary);color:#fff;border:0;border-radius:10px;padding:10px 12px;font-weight:600;cursor:pointer;transition:.2s;}
.tp-btn:hover{background:var(--secondary);}
.tp-btn-secondary{background:var(--muted);}
.tp-btn-secondary:hover{background:#59626b;}
.tp-btn-success{background:var(--success);}
.tp-btn-success:hover{background:#218838;}
.tp-btn-danger{background:var(--danger);}
.tp-btn-danger:hover{background:#c82333;}
.tp-hr{border:0;border-top:1px solid #eef1f5;margin:16px 0;}
.tp-bill-header{display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid var(--accent);padding-bottom:10px;margin-bottom:12px;}
.tp-bill-title{font-size:18px;font-weight:800;color:var(--primary);}
.tp-toggle{display:flex;gap:10px;align-items:center;font-size:13px;}
.tp-switch{position:relative;display:inline-block;width:54px;height:28px;}
.tp-switch input{opacity:0;width:0;height:0;}
.tp-slider{position:absolute;cursor:pointer;inset:0;background:#ccc;border-radius:30px;transition:.2s;}
.tp-slider:before{content:"";position:absolute;height:20px;width:20px;left:4px;bottom:4px;background:#fff;border-radius:50%;transition:.2s;}
.tp-switch input:checked+.tp-slider{background:var(--success);}
.tp-switch input:checked+.tp-slider:before{transform:translateX(26px);}
.tp-bill-customer{color:#2f3b47;font-size:14px;margin-bottom:10px;}
.tp-bill-items{max-height:330px;overflow:auto;border:1px solid #eef1f5;border-radius:10px;padding:10px;background:#fbfcfe;}
.tp-item{padding:10px 0;border-bottom:1px dashed #e7ecf2;}
.tp-item:last-child{border-bottom:0;}
.tp-item-top{display:flex;justify-content:space-between;gap:10px;}
.tp-chip{display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:700;margin-left:8px;}
.tp-chip-fabric{background:#e3f2fd;color:#1565c0;}
.tp-chip-ready{background:#e8f5e9;color:#2e7d32;}
.tp-chip-custom{background:#f3e5f5;color:#7b1fa2;}
.tp-item-sub{margin-top:4px;color:#6a7682;font-size:13px;}
.tp-stitch-line{margin-top:6px;background:#f9f0ff;border-left:3px solid #7b1fa2;padding:8px 10px;border-radius:8px;font-size:13px;}
.tp-item-actions{margin-top:6px;display:flex;gap:8px;flex-wrap:wrap;}
.tp-item-actions button{padding:4px 10px;font-size:12px;border-radius:8px;}
.tp-summary{border-top:2px solid var(--primary);padding-top:12px;margin-top:12px;}
.tp-row{display:flex;justify-content:space-between;padding:6px 0;color:#2f3b47;}
.tp-total{font-weight:900;font-size:16px;color:var(--primary);border-top:1px solid #e7ecf2;margin-top:6px;padding-top:10px;}
.tp-discount,.tp-payment{margin-top:14px;background:#f8f9fa;border:1px solid #eef1f5;border-radius:10px;padding:12px;}
.tp-discount-row{display:flex;gap:10px;align-items:center;}
.tp-discount-row .tp-input{flex:1;}
.tp-discount-display{margin-top:8px;color:var(--success);font-weight:700;}
.tp-actions{margin-top:14px;display:flex;gap:10px;}
.tp-actions>*{flex:1;}
.tp-tailoring-breakdown{margin-top:10px;background:#f9f0ff;border:1px solid #eadcf6;border-left:4px solid #7b1fa2;border-radius:10px;padding:10px;}
.tp-tailoring-breakdown h5{margin:0 0 8px;color:#7b1fa2;}
.tp-tailoring-row{display:flex;justify-content:space-between;padding:4px 0;font-size:13px;color:#2f3b47;}
.tp-tailoring-total{font-weight:900;border-top:1px solid #eadcf6;margin-top:6px;padding-top:8px;}
.tp-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1200;display:flex;align-items:center;justify-content:center;padding:20px;}
.tp-modal{width:100%;max-width:860px;background:#fff;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.30);overflow:hidden;}
.tp-modal-header{background:var(--primary);color:#fff;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;}
.tp-modal-header h3{margin:0;color:#fff;}
.tp-modal-close{background:transparent;border:0;color:#fff;font-size:24px;cursor:pointer;}
.tp-modal-body{padding:16px;max-height:70vh;overflow:auto;}
.tp-modal-info{background:#f8f9fa;border:1px solid #eef1f5;border-radius:10px;padding:10px;display:grid;gap:6px;}
.tp-modal-section{margin-top:14px;border:1px solid #eef1f5;border-radius:10px;padding:12px;}
.tp-modal-section h4{margin:0 0 10px;color:var(--primary);}
.tp-measurement-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;}
.tp-stitching{background:#f9f0ff;border-color:#eadcf6;}
.tp-tailoring-options{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;}
.tp-tailoring-option{background:#fff;border:1px solid #d1c4e9;border-radius:10px;padding:12px;cursor:pointer;transition:.2s;}
.tp-tailoring-option:hover{border-color:#7b1fa2;background:#f3e5f5;}
.tp-tailoring-option.selected{border-color:#7b1fa2;background:#e1bee7;box-shadow:0 0 0 2px rgba(123,31,162,.18);}
.tp-tailoring-option h5{margin:0 0 4px;color:#7b1fa2;}
.tp-selected-package{margin-top:10px;background:#e1bee7;border-radius:10px;padding:10px;}
.tp-modal-footer{padding:14px 16px;background:#f8f9fa;border-top:1px solid #eef1f5;display:flex;justify-content:flex-end;gap:10px;}
@media print{body *{visibility:hidden !important;}#billPrintArea,#billPrintArea *{visibility:visible !important;}#billPrintArea{position:absolute !important;top:0;left:0;width:100% !important;max-width:100% !important;margin:0 !important;padding:0 !important;border:0 !important;box-shadow:none !important;background:#fff !important;}#billPrintArea .tp-actions,#billPrintArea .tp-toggle,#billPrintArea .tp-discount button,#billPrintArea #clearBill,#billPrintArea #printBill{display:none !important;}}
</style>
@endsection

@section('page-specific-script')
<script>
(function() {
    const products = @json($productPayload);
    const garmentTypes = @json($garmentPayload);
    const customerMeasurements = @json($customerMeasurementPayload);
    const customerDirectory = @json($customerLookupPayload);
    const initialVatEnabled = @json(old('vat_enabled', '0') === '1');
    const resolveCustomerUrl = @json(route('order.customer.resolve'));
    const csrfToken = @json(csrf_token());

    const productMap = new Map(products.map(p => [String(p.id), p]));
    const garmentMap = new Map(garmentTypes.map(g => [String(g.id), g]));

    const categorySelect = document.getElementById('productCategory');
    const productSelect = document.getElementById('productSelect');
    const variantSelect = document.getElementById('variantSelect');
    const qtyInput = document.getElementById('quantity');
    const unitPriceInput = document.getElementById('unitPrice');
    const unitHint = document.getElementById('qtyUnitHint');
    const addBtn = document.getElementById('addToBill');

    const billItemsEl = document.getElementById('billItems');
    const tailoringBreakdownEl = document.getElementById('tailoringBreakdown');

    const customerSelect = document.getElementById('customer_id');
    const billCustomerEl = document.getElementById('billCustomer');
    const customerPhoneInput = document.getElementById('customerPhone');
    const checkCustomerBtn = document.getElementById('checkCustomerBtn');
    const customerCheckMessage = document.getElementById('customerCheckMessage');
    const createCustomerModal = document.getElementById('createCustomerModal');
    const closeCustomerModalBtn = document.getElementById('closeCustomerModal');
    const cancelCustomerModalBtn = document.getElementById('cancelCustomerModal');
    const newCustomerPhone = document.getElementById('newCustomerPhone');
    const newCustomerName = document.getElementById('newCustomerName');
    const newCustomerEmail = document.getElementById('newCustomerEmail');
    const newCustomerType = document.getElementById('newCustomerType');
    const newCustomerAddress = document.getElementById('newCustomerAddress');
    const createCustomerBtn = document.getElementById('createCustomerBtn');

    const discountTypeEl = document.getElementById('discountType');
    const discountValueEl = document.getElementById('discountValue');
    const applyDiscountBtn = document.getElementById('applyDiscount');
    const discountDisplayEl = document.getElementById('discountDisplay');

    const vatToggle = document.getElementById('vatToggle');
    const billTypeEl = document.getElementById('billType');
    const vatRow = document.getElementById('vatRow');
    const vatAmountEl = document.getElementById('vatAmount');

    const subtotalFabricEl = document.getElementById('subtotalFabric');
    const subtotalCustomEl = document.getElementById('subtotalCustom');
    const tailoringTotalEl = document.getElementById('tailoringTotal');
    const discountTotalEl = document.getElementById('discountTotal');
    const grandTotalEl = document.getElementById('grandTotal');
    const advancePaymentEl = document.getElementById('advance_payment_amount');
    const discountAmountInputEl = document.getElementById('discount_amount');
    const payableAmountEl = document.getElementById('payableAmount');
    const dueAmountEl = document.getElementById('dueAmount');

    const clearBillBtn = document.getElementById('clearBill');
    const printBillBtn = document.getElementById('printBill');
    const saveOrderBtn = document.getElementById('saveOrder');
    const printBillInput = document.getElementById('print_bill');
    const orderForm = document.getElementById('orderForm');

    // Modal
    const modal = document.getElementById('measurementModal');
    const closeModalBtn = document.getElementById('closeModal');
    const cancelMeasurementBtn = document.getElementById('cancelMeasurement');
    const saveMeasurementBtn = document.getElementById('saveMeasurement');
    const modalProductName = document.getElementById('modalProductName');
    const modalProductPrice = document.getElementById('modalProductPrice');
    const modalProductQuantity = document.getElementById('modalProductQuantity');

    const garmentTypeSelect = document.getElementById('garmentType');
    const measurementFieldsEl = document.getElementById('measurementFields');
    const customFabricOwnRadio = document.getElementById('customFabricOwn');
    const customFabricStockRadio = document.getElementById('customFabricStock');
    const customStockFabricFields = document.getElementById('customStockFabricFields');
    const customStockFabricQty = document.getElementById('customStockFabricQty');
    const customStockFabricHint = document.getElementById('customStockFabricHint');

    const tailoringOptionsEl = document.getElementById('tailoringOptions');
    const selectedTailoringBox = document.getElementById('selectedTailoringPackage');
    const selectedPackageNameEl = document.getElementById('selectedPackageName');
    const selectedPackagePriceEl = document.getElementById('selectedPackagePrice');

    const customDesignNote = document.getElementById('customDesignNote');
    const customDesignImages = document.getElementById('customDesignImages');

    const hiddenInputsHost = document.getElementById('itemsHiddenInputs');

    // Bill State
    let billItems = [];
    let discount = { type: 'none', value: 0 };
    let vatEnabled = Boolean(initialVatEnabled);
    let latestGrandTotal = 0;

    // For modal add/edit
    let pendingCustom = null; // {productId, variantId, qty, unitPrice, name, unitLabel, variantLabel}
    let editingCustomIndex = -1;

    const customersByPhone = new Map();
    const customersById = new Map();

    function money(n) { return Number(n || 0).toFixed(2); }
    function normalizePhone(value) { return String(value || '').replace(/\D+/g, ''); }

    function setCustomerCheckMessage(message, tone = 'info') {
        customerCheckMessage.textContent = message;
        customerCheckMessage.style.display = message ? 'block' : 'none';
        if (tone === 'error') {
            customerCheckMessage.style.background = '#ffe8ea';
            customerCheckMessage.style.color = '#7a0b18';
            customerCheckMessage.style.borderColor = '#ffccd1';
            return;
        }
        if (tone === 'success') {
            customerCheckMessage.style.background = '#e7f7ed';
            customerCheckMessage.style.color = '#0d5a2b';
            customerCheckMessage.style.borderColor = '#bfe8cf';
            return;
        }
        customerCheckMessage.style.background = '#f3f6fb';
        customerCheckMessage.style.color = '#415265';
        customerCheckMessage.style.borderColor = '#dbe4ef';
    }

    function updatePaymentSummary(discountAmount, grandTotal) {
        latestGrandTotal = Number(grandTotal || 0);
        const safeDiscount = Math.max(Number(discountAmount || 0), 0);
        const advanceAmount = Math.max(Number(advancePaymentEl?.value || 0), 0);
        const dueAmount = Math.max(latestGrandTotal - advanceAmount, 0);

        if (discountAmountInputEl) discountAmountInputEl.value = money(safeDiscount);
        if (payableAmountEl) payableAmountEl.textContent = `NPR ${money(latestGrandTotal)}`;
        if (dueAmountEl) dueAmountEl.textContent = `NPR ${money(dueAmount)}`;
    }

    function upsertCustomerIndex(customer) {
        const customerId = String(customer?.id || '');
        if (customerId) {
            customersById.set(customerId, { id: Number(customer.id), name: String(customer.name || ''), phone: String(customer.phone || '') });
        }
        const normalized = normalizePhone(customer?.phone || '');
        if (!normalized) return;
        customersByPhone.set(normalized, { id: Number(customer.id), name: String(customer.name || ''), phone: String(customer.phone || '') });
    }

    function selectCustomer(customer) {
        customerSelect.value = String(customer.id);
        customerPhoneInput.value = customer.phone || customerPhoneInput.value;
        updateCustomerDisplay();
        closeCustomerCreateModal();
    }

    function openCustomerCreateModal(phone) {
        newCustomerPhone.value = phone || '';
        newCustomerName.value = '';
        newCustomerEmail.value = '';
        newCustomerAddress.value = '';
        newCustomerType.value = 'retail';
        createCustomerModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeCustomerCreateModal() {
        createCustomerModal.style.display = 'none';
        document.body.style.overflow = '';
    }

    function getCategoryLabel(cat) {
        if (cat === 'fabric') return ['Fabric', 'tp-chip tp-chip-fabric'];
        if (cat === 'readymade') return ['Ready-Made', 'tp-chip tp-chip-ready'];
        return ['Custom', 'tp-chip tp-chip-custom'];
    }

    function getAvailableQtyForProduct(product) {
        if (!product) return 0;
        const hasVariants = Boolean((product.variants || []).length);
        if (!hasVariants) return Number(product.availableQty || 0);
        return (product.variants || []).reduce((total, variant) => total + Number(variant.availableQty || 0), 0);
    }

    function filterProductsByCategory(cat) {
        const matchesCategory = (product) => {
            if (cat === 'fabric') return product.category === 'fabrics';
            if (cat === 'readymade') return product.category !== 'fabrics';
            return product.category === 'fabrics';
        };
        const inStock = (product) => getAvailableQtyForProduct(product) > 0;
        return products.filter(p => matchesCategory(p) && inStock(p));
    }

    function resolveDefaultPrice(productId, variantId) {
        const p = productMap.get(String(productId));
        if (!p) return 0;
        if (variantId) {
            const v = (p.variants || []).find(x => String(x.id) === String(variantId));
            if (v && v.defaultPrice != null) return Number(v.defaultPrice);
        }
        if (p.defaultPrice != null) return Number(p.defaultPrice);
        return 0;
    }

    function updateProductOptions() {
        const cat = categorySelect.value;
        const list = filterProductsByCategory(cat);

        productSelect.innerHTML = `<option value="">-- Select Product --</option>`;
        if (!list.length) {
            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = '-- No in-stock products in current outlet --';
            productSelect.appendChild(emptyOption);
        }
        list.forEach(p => {
            const opt = document.createElement('option');
            opt.value = String(p.id);
            const available = money(getAvailableQtyForProduct(p));
            opt.textContent = `${p.name} (${p.sku}) | Available: ${available}${p.unitLabel ? ' ' + p.unitLabel : ''}`;
            productSelect.appendChild(opt);
        });

        variantSelect.innerHTML = `<option value="">No Variant</option>`;
        variantSelect.disabled = true;

        unitHint.textContent = '-';
        unitPriceInput.value = '';
    }

    function updateVariantOptions() {
        const pid = productSelect.value;
        const p = productMap.get(String(pid));
        variantSelect.innerHTML = '';

        if (!p || !(p.variants || []).length) {
            variantSelect.innerHTML = `<option value="">No Variant</option>`;
            variantSelect.disabled = true;
            unitHint.textContent = p ? `Available: ${money(Number(p.availableQty || 0))}${p.unitLabel ? ' ' + p.unitLabel : ''}` : '-';
            unitPriceInput.value = money(resolveDefaultPrice(pid, ''));
            return;
        }

        const inStockVariants = (p.variants || []).filter(v => Number(v.availableQty || 0) > 0);
        if (!inStockVariants.length) {
            variantSelect.innerHTML = `<option value="">Out of Stock</option>`;
            variantSelect.disabled = true;
            unitHint.textContent = 'All variants are out of stock in current outlet';
            unitPriceInput.value = '';
            return;
        }

        variantSelect.disabled = false;
        inStockVariants.forEach(v => {
            const opt = document.createElement('option');
            opt.value = String(v.id);
            opt.textContent = `${v.label} | Available: ${money(Number(v.availableQty || 0))}${p.unitLabel ? ' ' + p.unitLabel : ''}`;
            variantSelect.appendChild(opt);
        });

        unitHint.textContent = p.unitLabel || '-';
        unitPriceInput.value = money(resolveDefaultPrice(pid, variantSelect.value || ''));
    }

    function updatePriceFromSelection() {
        unitPriceInput.value = money(resolveDefaultPrice(productSelect.value, variantSelect.value));
    }

    function selectedCustomFabricSource() {
        return customFabricStockRadio?.checked ? 'stock' : 'own';
    }

    // ✅ UPDATED: also update modal qty display when switching source
    function updateCustomFabricSourceUI() {
        const source = selectedCustomFabricSource();
        customStockFabricFields.style.display = source === 'stock' ? '' : 'none';

        if (pendingCustom) {
            if (source === 'stock') {
                modalProductQuantity.textContent = money(Number(customStockFabricQty?.value || 0));
            } else {
                modalProductQuantity.textContent = money(pendingCustom.qty);
            }
        }

        if (source !== 'stock' || !pendingCustom) {
            customStockFabricHint.textContent = '';
            return;
        }

        const product = productMap.get(String(pendingCustom.productId || ''));
        if (!product) {
            customStockFabricHint.textContent = 'Selected stock fabric is unavailable.';
            return;
        }

        let availableQty = Number(product.availableQty || 0);
        if ((product.variants || []).length) {
            const selectedVariant = (product.variants || []).find(v => String(v.id) === String(pendingCustom.variantId || ''));
            availableQty = Number(selectedVariant?.availableQty || 0);
        }

        customStockFabricHint.textContent = `Available: ${money(availableQty)}${product.unitLabel ? ' ' + product.unitLabel : ''}`;
    }

    // ✅ UPDATED: on stock qty input, update modal qty and (optionally) refresh tailoring cards display (tailoring is per pcs, so no change needed)
    customStockFabricQty?.addEventListener('input', () => {
        if (selectedCustomFabricSource() === 'stock' && pendingCustom) {
            modalProductQuantity.textContent = money(Number(customStockFabricQty.value || 0));
        }
        updateCustomFabricSourceUI();
    });

    function openModal() {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        pendingCustom = null;
        editingCustomIndex = -1;
        saveMeasurementBtn.textContent = 'Add Custom Item';
    }

    function fillGarmentTypeOptions() {
        garmentTypeSelect.innerHTML = `<option value="">Select Garment Type</option>`;
        garmentTypes.forEach(g => {
            const opt = document.createElement('option');
            opt.value = String(g.id);
            opt.textContent = g.title;
            garmentTypeSelect.appendChild(opt);
        });
    }

    function getCustomerMeasurementForGarment(customerId, garmentTypeId) {
        const customerRows = customerMeasurements[String(customerId)] || {};
        return customerRows[String(garmentTypeId)] || [];
    }

    function renderMeasurementFields(garmentTypeId) {
        measurementFieldsEl.innerHTML = '';
        const g = garmentMap.get(String(garmentTypeId));
        if (!g) return;

        const customerId = customerSelect.value || '';
        const saved = getCustomerMeasurementForGarment(customerId, garmentTypeId);
        const base = (saved && saved.length)
            ? saved.map(x => ({ title: x.type || '', value: x.measurement || '', unit: x.unit || '' }))
            : (g.measurements || []).map(x => ({ title: x.title || '', value: '', unit: x.unit || '' }));

        base.forEach((m) => {
            const wrap = document.createElement('div');
            wrap.className = 'tp-form-group';
            const label = document.createElement('label');
            label.textContent = `${m.title} ${m.unit ? '('+m.unit+')' : ''}`;
            const input = document.createElement('input');
            input.className = 'tp-input';
            input.type = 'text';
            input.value = m.value || '';
            input.dataset.measureType = m.title || '';
            input.dataset.measureUnit = m.unit || '';
            wrap.appendChild(label);
            wrap.appendChild(input);
            measurementFieldsEl.appendChild(wrap);
        });
    }

    // Tailoring packages from garment configured packages; amount is per pcs × (pendingCustom.qty)
    function buildTailoringPackages(garmentTypeId) {
        const g = garmentMap.get(String(garmentTypeId));
        const pcsQty = Number(pendingCustom?.qty || 1); // ✅ tailoring is based on pcs count, NOT stock meter
        const configuredPackages = (g?.tailoringPackages || []).filter(pkg => Number(pkg.amount || 0) >= 0);

        return configuredPackages.map((pkg) => {
            const perPiece = Number(pkg.amount || 0);
            return {
                id: pkg.id || null,
                name: pkg.name || 'Tailoring Package',
                price: Math.max(perPiece * pcsQty, 0),
                desc: `${pkg.description || 'Configured package'} (${money(perPiece)} per pcs × ${pcsQty})`,
            };
        });
    }

    function renderTailoringOptions(garmentTypeId) {
        tailoringOptionsEl.innerHTML = '';
        selectedTailoringBox.style.display = 'none';

        if (!garmentTypeId) {
            tailoringOptionsEl.innerHTML = `<div class="tp-hint">Select a garment type to view tailoring packages.</div>`;
            return;
        }

        const pkgs = buildTailoringPackages(garmentTypeId);
        if (!pkgs.length) {
            tailoringOptionsEl.innerHTML = `<div class="tp-hint">No tailoring package configured for this garment type.</div>`;
            return;
        }

        pkgs.forEach((pkg, idx) => {
            const card = document.createElement('div');
            card.className = 'tp-tailoring-option';
            card.dataset.price = String(pkg.price);
            card.dataset.name = pkg.name;
            card.dataset.packageId = String(pkg.id || '');

            card.innerHTML = `
                <h5>${pkg.name}</h5>
                <div style="font-size:12px;color:#6a7682;margin:6px 0;">${pkg.desc}</div>
                <div style="font-weight:900;color:var(--primary);">NPR ${money(pkg.price)}</div>
            `;

            if (idx === 0) {
                card.classList.add('selected');
                selectedTailoringBox.style.display = '';
                selectedPackageNameEl.textContent = pkg.name;
                selectedPackagePriceEl.textContent = money(pkg.price);
            }

            tailoringOptionsEl.appendChild(card);
        });
    }

    function applyExistingMeasurements(measurements) {
        const byType = new Map((measurements || []).map(m => [String(m.type || ''), String(m.measurement || '')]));
        Array.from(measurementFieldsEl.querySelectorAll('input.tp-input')).forEach((input) => {
            const key = String(input.dataset.measureType || '');
            if (byType.has(key)) input.value = byType.get(key) || '';
        });
    }

    function applyExistingTailoring(tailoring) {
        if (!tailoring) return;
        const cards = Array.from(tailoringOptionsEl.querySelectorAll('.tp-tailoring-option'));
        if (!cards.length) return;

        let target = null;
        if (tailoring.packageId) target = cards.find(card => String(card.dataset.packageId || '') === String(tailoring.packageId));
        if (!target && tailoring.package) target = cards.find(card => String(card.dataset.name || '') === String(tailoring.package));
        if (!target) return;

        cards.forEach(card => card.classList.remove('selected'));
        target.classList.add('selected');
        selectedTailoringBox.style.display = '';
        selectedPackageNameEl.textContent = target.dataset.name || '-';
        selectedPackagePriceEl.textContent = money(target.dataset.price || 0);
    }

    function openCustomMeasurementModal(customPayload, existingItem = null, itemIndex = -1) {
        pendingCustom = customPayload;
        editingCustomIndex = itemIndex;

        modalProductName.textContent = pendingCustom.name;
        modalProductPrice.textContent = money(pendingCustom.unitPrice);
        // ✅ default show pcs qty; if stock selected later, updateCustomFabricSourceUI will change display
        modalProductQuantity.textContent = money(pendingCustom.qty);

        fillGarmentTypeOptions();

        const garmentTypeId = existingItem?.garmentTypeId ? String(existingItem.garmentTypeId) : '';
        garmentTypeSelect.value = garmentTypeId;
        renderMeasurementFields(garmentTypeId);
        renderTailoringOptions(garmentTypeId);
        applyExistingMeasurements(existingItem?.measurements || []);
        applyExistingTailoring(existingItem?.tailoring || null);

        customDesignNote.value = String(existingItem?.designNote || '');
        const fabricSource = String(existingItem?.fabricSource || 'own');
        customFabricOwnRadio.checked = fabricSource !== 'stock';
        customFabricStockRadio.checked = fabricSource === 'stock';
        customStockFabricQty.value = money(existingItem?.fabricQuantity || 1);
        updateCustomFabricSourceUI();

        try { customDesignImages.value = ''; } catch (e) {}

        saveMeasurementBtn.textContent = editingCustomIndex > -1 ? 'Update Custom Item' : 'Add Custom Item';
        openModal();
    }

    function getSelectedTailoring() {
        const selected = tailoringOptionsEl.querySelector('.tp-tailoring-option.selected');
        if (!selected) return null;
        return {
            packageId: selected.dataset.packageId ? Number(selected.dataset.packageId) : null,
            name: selected.dataset.name,
            price: Number(selected.dataset.price || 0),
        };
    }

    tailoringOptionsEl.addEventListener('click', (e) => {
        const card = e.target.closest('.tp-tailoring-option');
        if (!card) return;
        tailoringOptionsEl.querySelectorAll('.tp-tailoring-option').forEach(x => x.classList.remove('selected'));
        card.classList.add('selected');

        selectedTailoringBox.style.display = '';
        selectedPackageNameEl.textContent = card.dataset.name || '-';
        selectedPackagePriceEl.textContent = money(card.dataset.price || 0);
    });

    // Bill rendering + totals
    function renderBill() {
        billItemsEl.innerHTML = '';

        let subtotalFabric = 0;
        let subtotalCustom = 0;
        let totalTailoring = 0;
        const tailoringLines = [];

        billItems.forEach((item) => {
            const lineTotal = Number(item.unitPrice || 0) * Number(item.qty || 0);

            if (item.category === 'fabric' || item.category === 'readymade') subtotalFabric += lineTotal;
            if (item.category === 'custom') {
                subtotalCustom += lineTotal;
                if (item.tailoring && item.tailoring.amount) {
                    totalTailoring += Number(item.tailoring.amount || 0);
                    tailoringLines.push({ label: `${item.name} - ${item.tailoring.package}`, amount: Number(item.tailoring.amount || 0) });
                }
            }

            const [label, chipClass] = getCategoryLabel(item.category);
            const row = document.createElement('div');
            row.className = 'tp-item';

            const variantText = item.variantLabel ? ` | ${item.variantLabel}` : '';
            const unitLabel = item.unitLabel ? ` ${item.unitLabel}` : '';

            let measurementInfo = '';
            if (item.measurements && item.measurements.length) {
                measurementInfo = `<div class="tp-item-sub"><i class="fas fa-ruler"></i> ${item.garmentTitle || 'Garment'} | ${item.measurements.slice(0,3).map(m => `${m.type}: ${m.measurement}${m.unit ? ' '+m.unit : ''}`).join(', ')}${item.measurements.length>3 ? ' ...' : ''}</div>`;
            }

            row.innerHTML = `
                <div class="tp-item-top">
                    <div>
                        <div>
                            ${item.name}
                            <span class="${chipClass}">${label}</span>
                            <div class="tp-item-sub">${money(item.qty)}${unitLabel} × NPR ${money(item.unitPrice)}${variantText}</div>
                            ${measurementInfo}
                            ${item.tailoring ? `<div class="tp-stitch-line"><i class="fas fa-cut"></i> <strong>Tailoring:</strong> ${item.tailoring.package} - NPR ${money(item.tailoring.amount)}</div>` : ''}
                        </div>
                        <div class="tp-item-actions">
                            <button type="button" class="tp-btn tp-btn-secondary" data-action="edit" data-id="${item.id}">Edit</button>
                            <button type="button" class="tp-btn tp-btn-danger" data-action="remove" data-id="${item.id}">Remove</button>
                        </div>
                    </div>
                    <div><strong>NPR ${money(lineTotal)}</strong></div>
                </div>
            `;
            billItemsEl.appendChild(row);
        });

        // Tailoring breakdown
        if (!tailoringLines.length) {
            tailoringBreakdownEl.style.display = 'none';
            tailoringBreakdownEl.innerHTML = '';
        } else {
            tailoringBreakdownEl.style.display = '';
            let html = `<h5><i class="fas fa-cut"></i> Tailoring Charges Breakdown</h5>`;
            tailoringLines.forEach(l => {
                html += `<div class="tp-tailoring-row"><span>${l.label}</span><span>NPR ${money(l.amount)}</span></div>`;
            });
            html += `<div class="tp-tailoring-row tp-tailoring-total"><span>Total Tailoring Charges</span><span>NPR ${money(totalTailoring)}</span></div>`;
            tailoringBreakdownEl.innerHTML = html;
        }

        // Discount applies on (fabric + custom) in your current logic
        let discountAmount = 0;
        if (discount.type === 'flat') discountAmount = Math.min(Number(discount.value || 0), (subtotalFabric + subtotalCustom));
        if (discount.type === 'percent') discountAmount = (subtotalFabric + subtotalCustom) * (Number(discount.value || 0) / 100);

        // VAT on taxableSubtotal
        let vatAmount = 0;
        const taxableSubtotal = Math.max((subtotalFabric + subtotalCustom) - discountAmount, 0);
        if (vatEnabled) vatAmount = taxableSubtotal * 0.13;

        const grand = taxableSubtotal + vatAmount + totalTailoring;

        subtotalFabricEl.textContent = `NPR ${money(subtotalFabric)}`;
        subtotalCustomEl.textContent = `NPR ${money(subtotalCustom)}`;
        tailoringTotalEl.textContent = `NPR ${money(totalTailoring)}`;
        discountTotalEl.textContent = `NPR ${money(discountAmount)}`;
        vatAmountEl.textContent = `NPR ${money(vatAmount)}`;
        grandTotalEl.textContent = `NPR ${money(grand)}`;
        updatePaymentSummary(discountAmount, grand);

        vatRow.style.display = vatEnabled ? '' : 'none';

        buildHiddenInputs();
    }

    function buildHiddenInputs() {
        hiddenInputsHost.innerHTML = '';

        hiddenInputsHost.appendChild(makeHidden('vat_enabled', vatEnabled ? '1' : '0'));
        hiddenInputsHost.appendChild(makeHidden('discount_type', discount.type));
        hiddenInputsHost.appendChild(makeHidden('discount_value', String(discount.value || 0)));

        billItems.forEach((item, idx) => {
            hiddenInputsHost.appendChild(makeHidden(`items[${idx}][item_category]`, item.category === 'fabric' ? 'fabric' : (item.category === 'readymade' ? 'readymade' : 'custom')));

            if (item.category !== 'custom') {
                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][product_id]`, String(item.productId || '')));
                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][product_variant_id]`, String(item.variantId || '')));
                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][quantity]`, String(item.qty || '1.00')));
                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][unit_price]`, String(item.unitPrice || '0.00')));
            } else {
                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][quantity]`, String(item.qty || '1.00')));
                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][unit_price]`, String(item.unitPrice || '0.00')));

                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][garment_type_id]`, String(item.garmentTypeId || '')));
                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][garment_title]`, String(item.garmentTitle || '')));
                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][fabric_source]`, String(item.fabricSource || 'own')));

                if (String(item.fabricSource || 'own') === 'stock') {
                    hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][fabric_product_id]`, String(item.productId || '')));
                    hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][fabric_product_variant_id]`, String(item.variantId || '')));
                    hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][fabric_quantity]`, String(item.fabricQuantity || '0')));
                }

                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][tailoring_package]`, String(item.tailoring?.package || '')));
                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][tailoring_amount]`, String(item.tailoring?.amount || '0')));
                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][tailoring_package_id]`, String(item.tailoring?.packageId || '')));
                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][design_note]`, String(item.designNote || '')));

                (item.measurements || []).forEach((m, mIdx) => {
                    hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][measurements][${mIdx}][type]`, String(m.type || '')));
                    hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][measurements][${mIdx}][measurement]`, String(m.measurement || '')));
                    hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][measurements][${mIdx}][unit]`, String(m.unit || '')));
                });
            }
        });
    }

    function makeHidden(name, value) {
        const i = document.createElement('input');
        i.type = 'hidden';
        i.name = name;
        i.value = value ?? '';
        return i;
    }

    function validateRequiredVariants() {
        const invalidItem = billItems.find((item) => {
            if (item.category === 'custom') return false;
            const product = productMap.get(String(item.productId || ''));
            const hasVariants = Boolean(product && (product.variants || []).length);
            if (!hasVariants) return false;
            return !item.variantId;
        });

        if (!invalidItem) return true;
        alert(`Variant is required for "${invalidItem.name}". Please edit the item and select a variant.`);
        return false;
    }

    function validateHasItems() {
        if (billItems.length > 0) return true;
        alert('Add at least one item to the bill before saving or printing.');
        return false;
    }

    // Actions edit/remove
    billItemsEl.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;

        const action = btn.dataset.action;
        const id = String(btn.dataset.id || '');
        const index = billItems.findIndex(x => String(x.id) === id);
        if (index === -1) return;

        if (action === 'remove') {
            billItems.splice(index, 1);
            renderBill();
            return;
        }

        if (action === 'edit') {
            const item = billItems[index];
            if (item.category === 'custom') {
                const customPayload = {
                    productId: item.productId,
                    variantId: item.variantId,
                    qty: Number(item.garmentQty || item.qty || 0), // keep pcs qty if you add garmentQty later
                    unitPrice: Number(item.baseUnitPrice || item.unitPrice || 0),
                    name: item.name,
                    unitLabel: item.unitLabel || '',
                    variantLabel: item.variantLabel || '',
                };
                openCustomMeasurementModal(customPayload, item, index);
            } else {
                const newQty = prompt(`Update quantity for ${item.name}:`, String(item.qty));
                if (newQty && !isNaN(newQty) && Number(newQty) > 0) {
                    item.qty = Number(newQty);
                    renderBill();
                }
            }
        }
    });

    // Add to bill
    addBtn.addEventListener('click', () => {
        const category = categorySelect.value;
        const productId = productSelect.value;
        const variantId = variantSelect.disabled ? '' : (variantSelect.value || '');
        const qty = Number(qtyInput.value || 0); // for custom: pcs qty
        const unitPrice = Number(unitPriceInput.value || 0);

        if (!customerSelect.value) {
            alert('Check/select customer first.');
            customerPhoneInput.focus();
            return;
        }

        if (!productId) { alert('Select a product.'); return; }
        if (qty <= 0) { alert('Quantity must be > 0'); return; }
        if (unitPrice < 0) { alert('Unit price must be >= 0'); return; }

        const p = productMap.get(String(productId));
        const variantLabel = variantId ? ((p.variants || []).find(v => String(v.id) === String(variantId))?.label || '') : '';

        if (category !== 'custom' && p && (p.variants || []).length && !variantId) {
            alert('Please select a variant for this product.');
            return;
        }

        if (category !== 'custom' && p) {
            if ((p.variants || []).length) {
                const selectedVariant = (p.variants || []).find(v => String(v.id) === String(variantId));
                const availableQty = Number(selectedVariant?.availableQty || 0);
                if (qty > availableQty) {
                    alert(`Only ${money(availableQty)} ${p.unitLabel || ''} is available for selected variant in current outlet.`);
                    return;
                }
            } else {
                const availableQty = Number(p.availableQty || 0);
                if (qty > availableQty) {
                    alert(`Only ${money(availableQty)} ${p.unitLabel || ''} is available in current outlet.`);
                    return;
                }
            }
        }

        if (category !== 'custom') {
            billItems.push({
                id: Date.now(),
                category,
                productId,
                variantId,
                name: p ? `${p.name} (${p.sku})` : 'Product',
                variantLabel,
                unitLabel: p?.unitLabel || '',
                qty,
                unitPrice,
            });
            renderBill();

            productSelect.value = '';
            variantSelect.value = '';
            variantSelect.disabled = true;
            unitPriceInput.value = '';
            qtyInput.value = '1.00';
            unitHint.textContent = '-';
            return;
        }

        // Custom => open modal
        const customPayload = {
            productId,
            variantId,
            qty, // pcs qty
            unitPrice, // fabric per-unit price
            name: p ? `${p.name} (${p.sku})` : 'Custom Product',
            unitLabel: p?.unitLabel || '',
            variantLabel,
        };
        openCustomMeasurementModal(customPayload, null, -1);
    });

    garmentTypeSelect.addEventListener('change', () => {
        const gid = garmentTypeSelect.value;
        renderMeasurementFields(gid);
        renderTailoringOptions(gid);
    });

    customFabricOwnRadio?.addEventListener('change', updateCustomFabricSourceUI);
    customFabricStockRadio?.addEventListener('change', updateCustomFabricSourceUI);

    // ✅ MAIN FIX: when fabricSource=stock, use stockFabricQty for item.qty so (unitPrice * qty) becomes correct
    saveMeasurementBtn.addEventListener('click', () => {
        if (!pendingCustom) return;

        const garmentTypeId = garmentTypeSelect.value;
        if (!garmentTypeId) { alert('Select garment type.'); return; }

        const tailoring = getSelectedTailoring();
        if (!tailoring) { alert('Select tailoring package.'); return; }

        const fabricSource = selectedCustomFabricSource();

        // validate stock qty + available
        let stockFabricQtyValue = 0;
        if (fabricSource === 'stock') {
            stockFabricQtyValue = Number(customStockFabricQty?.value || 0);
            if (stockFabricQtyValue <= 0) { alert('Enter stock fabric quantity.'); return; }

            const product = productMap.get(String(pendingCustom.productId || ''));
            if (!product) { alert('Selected stock fabric product is invalid.'); return; }
            if ((product.variants || []).length && !pendingCustom.variantId) { alert('Selected stock fabric requires a variant.'); return; }

            let availableQty = Number(product.availableQty || 0);
            if ((product.variants || []).length) {
                const selectedVariant = (product.variants || []).find(v => String(v.id) === String(pendingCustom.variantId || ''));
                availableQty = Number(selectedVariant?.availableQty || 0);
            }

            if (stockFabricQtyValue > availableQty) {
                alert(`Only ${money(availableQty)} ${product.unitLabel || ''} stock fabric is available.`);
                return;
            }
        }

        // gather measurements
        const mInputs = Array.from(measurementFieldsEl.querySelectorAll('input.tp-input'));
        const measurements = mInputs.map(i => ({
            type: i.dataset.measureType || '',
            measurement: i.value || '',
            unit: i.dataset.measureUnit || '',
        })).filter(m => String(m.type).trim() !== '');

        const invalid = measurements.some(m => !String(m.measurement).trim());
        if (!measurements.length || invalid) { alert('Fill all measurement values.'); return; }

        const garmentTitle = garmentMap.get(String(garmentTypeId))?.title || 'Garment';

        // base unit price (per meter/yard/etc). If own fabric, product charge is 0.
        const baseUnitPrice = fabricSource === 'own' ? 0 : Number(pendingCustom.unitPrice || 0);

        // ✅ qty used for product price calculation:
        // - own fabric => keep qty = pcs (but unitPrice=0 so total product cost 0 anyway)
        // - stock fabric => qty = stock fabric quantity so total = (stockQty × unitPrice)
        const billQty = fabricSource === 'stock'
            ? stockFabricQtyValue
            : pendingCustom.qty;

        const customItemPayload = {
            id: editingCustomIndex > -1 ? billItems[editingCustomIndex]?.id : Date.now(),
            category: 'custom',

            productId: pendingCustom.productId,
            variantId: pendingCustom.variantId,

            name: pendingCustom.name,
            variantLabel: pendingCustom.variantLabel,
            unitLabel: pendingCustom.unitLabel,

            // ✅ this is what renderBill uses in lineTotal = qty * unitPrice
            qty: billQty,
            unitPrice: baseUnitPrice,

            // keep pcs qty for edit/reference (optional)
            garmentQty: Number(pendingCustom.qty || 1),
            baseUnitPrice: Number(pendingCustom.unitPrice || 0),

            fabricSource,
            fabricQuantity: fabricSource === 'stock' ? stockFabricQtyValue : 0,

            garmentTypeId,
            garmentTitle,
            measurements,

            tailoring: {
                packageId: tailoring.packageId,
                package: tailoring.name,
                amount: tailoring.price, // already per pcs × pendingCustom.qty
            },

            designNote: customDesignNote.value || '',
        };

        if (editingCustomIndex > -1 && billItems[editingCustomIndex]) {
            billItems[editingCustomIndex] = customItemPayload;
        } else {
            billItems.push(customItemPayload);
        }

        closeModal();
        renderBill();

        // reset entry fields
        productSelect.value = '';
        variantSelect.value = '';
        variantSelect.disabled = true;
        unitPriceInput.value = '';
        qtyInput.value = '1.00';
        unitHint.textContent = '-';
    });

    closeModalBtn.addEventListener('click', closeModal);
    cancelMeasurementBtn.addEventListener('click', closeModal);

    // Discounts
    discountTypeEl.addEventListener('change', () => {
        discountValueEl.disabled = discountTypeEl.value === 'none';
        if (discountTypeEl.value === 'none') discountValueEl.value = '';
    });

    applyDiscountBtn.addEventListener('click', () => {
        const type = discountTypeEl.value;
        const val = Number(discountValueEl.value || 0);
        discount = { type, value: (type === 'none' ? 0 : val) };

        if (type === 'none') discountDisplayEl.textContent = '';
        if (type === 'flat') discountDisplayEl.textContent = `Flat discount: NPR ${money(val)}`;
        if (type === 'percent') discountDisplayEl.textContent = `Percent discount: ${money(val)}%`;

        renderBill();
    });

    advancePaymentEl.addEventListener('input', () => {
        updatePaymentSummary(discountAmountInputEl?.value || 0, latestGrandTotal);
    });

    // VAT
    vatToggle.addEventListener('change', () => {
        vatEnabled = vatToggle.checked;
        billTypeEl.textContent = vatEnabled ? 'VAT Bill' : 'Estimated Bill';
        renderBill();
    });

    // Clear
    clearBillBtn.addEventListener('click', () => {
        if (!confirm('Clear the entire bill?')) return;
        billItems = [];
        discount = { type: 'none', value: 0 };
        discountTypeEl.value = 'none';
        discountValueEl.value = '';
        discountValueEl.disabled = true;
        discountDisplayEl.textContent = '';
        vatEnabled = false;
        vatToggle.checked = false;
        billTypeEl.textContent = 'Estimated Bill';
        renderBill();
    });

    printBillBtn.addEventListener('click', () => {
        if (!validateHasItems()) return;
        if (!validateRequiredVariants()) return;
        if (printBillInput) printBillInput.value = '1';
        orderForm?.requestSubmit();
    });

    saveOrderBtn?.addEventListener('click', () => {
        if (printBillInput) printBillInput.value = '0';
    });

    orderForm?.addEventListener('submit', (event) => {
        if (!validateHasItems()) { event.preventDefault(); return; }
        if (!validateRequiredVariants()) event.preventDefault();
    });

    // Customer display
    function updateCustomerDisplay() {
        const customer = customersById.get(String(customerSelect.value || ''));
        const text = customer ? (customer.phone ? `${customer.name} (${customer.phone})` : customer.name) : '-';
        billCustomerEl.textContent = text;
    }

    checkCustomerBtn.addEventListener('click', () => {
        const normalizedPhone = normalizePhone(customerPhoneInput.value);
        if (normalizedPhone.length < 7) {
            setCustomerCheckMessage('Enter a valid phone number before checking.', 'error');
            return;
        }

        const foundCustomer = customersByPhone.get(normalizedPhone);
        if (foundCustomer) {
            selectCustomer(foundCustomer);
            setCustomerCheckMessage(`Existing customer selected: ${foundCustomer.name}`, 'success');
            return;
        }

        customerSelect.value = '';
        updateCustomerDisplay();
        setCustomerCheckMessage('Customer not found. Create customer in popup.', 'info');
        openCustomerCreateModal(normalizedPhone);
    });

    createCustomerBtn.addEventListener('click', async () => {
        const normalizedPhone = normalizePhone(newCustomerPhone.value || customerPhoneInput.value);
        const payload = {
            phone: normalizedPhone,
            name: (newCustomerName.value || '').trim(),
            email: (newCustomerEmail.value || '').trim(),
            customer_type: newCustomerType.value || 'retail',
            address: (newCustomerAddress.value || '').trim(),
        };

        if (payload.phone.length < 7) { setCustomerCheckMessage('Enter a valid phone number before creating customer.', 'error'); return; }
        if (!payload.name || !payload.email || !payload.address) { setCustomerCheckMessage('Name, email and address are required to create customer.', 'error'); return; }

        createCustomerBtn.disabled = true;
        try {
            const response = await fetch(resolveCustomerUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(payload),
            });

            const data = await response.json();
            if (!response.ok) { setCustomerCheckMessage(data?.message || 'Unable to create customer.', 'error'); return; }

            const customer = data?.customer;
            if (!customer?.id) { setCustomerCheckMessage('Customer response was invalid. Please try again.', 'error'); return; }

            upsertCustomerIndex(customer);
            selectCustomer(customer);
            setCustomerCheckMessage(
                data.status === 'existing'
                    ? `Existing customer selected: ${customer.name}`
                    : `Customer created and selected: ${customer.name}`,
                'success'
            );
        } catch (error) {
            setCustomerCheckMessage('Unable to reach server. Please retry.', 'error');
        } finally {
            createCustomerBtn.disabled = false;
        }
    });

    closeCustomerModalBtn.addEventListener('click', closeCustomerCreateModal);
    cancelCustomerModalBtn.addEventListener('click', closeCustomerCreateModal);
    createCustomerModal.addEventListener('click', (event) => {
        if (event.target === createCustomerModal) closeCustomerCreateModal();
    });

    // Product selection listeners
    categorySelect.addEventListener('change', updateProductOptions);
    productSelect.addEventListener('change', updateVariantOptions);
    variantSelect.addEventListener('change', updatePriceFromSelection);

    // Init
    customerDirectory.forEach(upsertCustomerIndex);
    if (customerSelect.value) {
        const customer = customersById.get(String(customerSelect.value));
        if (customer?.phone) customerPhoneInput.value = customer.phone;
    }
    vatToggle.checked = vatEnabled;
    billTypeEl.textContent = vatEnabled ? 'VAT Bill' : 'Estimated Bill';
    updateCustomerDisplay();
    updateProductOptions();
    renderBill();
})();
</script>
@endsection