@extends('layouts.pos')

@section('title', $editingOrder ? 'Edit Order' : 'Create Order')

@section('content')
@php
// Build lightweight payloads for JS
$productPayload = $products->map(function ($product) use ($productAvailableQty) {
    $isFabric = (string) ($product->category?->slug ?? '') === 'fabrics';

    return [
        'id' => $product->id,
        'name' => $product->name,
        'code' => $product->code,
        'category' => $product->category?->slug, // expect 'fabrics' for fabric
        'unitLabel' => $isFabric ? 'm' : 'pcs',
        'availableQty' => array_key_exists($product->id, $productAvailableQty)
            ? (float) $productAvailableQty[$product->id]
            : 0.0,
        'defaultPrice' => (float) ($product->amount ?? 0),
        'variants' => [],
    ];
})->values();

$garmentPayload = $garmentTypes->map(function ($garmentType) {
    return [
        'id' => $garmentType->id,
        'title' => $garmentType->title,
        'designNotes' => array_values((array) ($garmentType->design_note ?? [])),
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
        'customer_type' => $customer->customer_type,
    ];
})->values();
@endphp

<form id="orderForm" action="{{ $editingOrder ? route('order.update', $editingOrder) : route('order.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($editingOrder)
        @method('PUT')
    @endif

    {{-- Hidden container where bill items are converted to items[i][...] --}}
    <div id="itemsHiddenInputs"></div>
    <input type="hidden" id="print_bill" name="print_bill" value="{{ old('print_bill', '0') }}">
    <input type="hidden" id="ordered_at" name="ordered_at" value="{{ old('ordered_at', $editingOrder?->ordered_at?->format('Y-m-d H:i:s') ?: now()->format('Y-m-d H:i:s')) }}">
    <input type="hidden" id="delivery_due_at" name="delivery_due_at" value="{{ old('delivery_due_at', $editingOrder?->delivery_due_at?->format('Y-m-d H:i:s')) }}">
    <input type="hidden" id="status" name="status" value="{{ old('status', $editingOrder?->status ?: \App\Models\Order::STATUS_CONFIRMED) }}">
    <input type="hidden" id="notes" name="notes" value="{{ old('notes', $editingOrder?->notes) }}">
    <input type="hidden" id="payment_status" name="payment_status" value="{{ old('payment_status', $editingOrder?->payment_status ?: \App\Models\Order::PAYMENT_STATUS_UNPAID) }}">
    <input type="hidden" id="payment_method" name="payment_method" value="{{ old('payment_method', $editingOrder?->payment_method) }}">
    <input type="hidden" id="advance_payment_amount" name="advance_payment_amount" value="{{ old('advance_payment_amount', (string) ($editingOrder?->advance_payment_amount ?? '0')) }}">
    <input type="hidden" id="discount_amount" name="discount_amount" value="{{ old('discount_amount', (string) ($editingOrder?->discount_amount ?? '0')) }}">

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

        <section class="demo-section">
            <div class="demo-section-header">
                <a href="{{ route('order.index') }}" class="tp-back-link">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
            </div>

            <h2><i class="fas fa-desktop"></i> {{ $editingOrder ? 'Update Order' : 'Enhanced Live Billing Interface' }}</h2>

            <div class="demo-layout">
            {{-- LEFT: Order entry --}}
            <div class="order-entry">
                <h3><i class="fas fa-edit"></i> Order Entry</h3>

                <div class="customer-info">
                    <div class="form-group">
                        <label>Customer Mobile Number *</label>
                        <div class="customer-check-row">
                            <input id="customerPhone" type="text" class="tp-input" placeholder="Customer mobile number">
                            <button type="button" id="checkCustomerBtn" class="btn-secondary">Check</button>
                        </div>
                        <input
                            id="customer_id"
                            name="customer_id"
                            type="hidden"
                            value="{{ old('customer_id', $editingOrder?->customer_id ?? $selectedCustomerId ?? '') }}">
                    </div>
                    <div id="customerCheckMessage" class="customer-check-message" style="display:none;"></div>
                    <div id="customerDetails" class="customer-details" style="display:none;">
                        <div class="form-group">
                            <label for="customerName">Customer Name *</label>
                            <input id="customerName" type="text" class="tp-input" placeholder="Enter full name">
                        </div>
                        <div class="form-group">
                            <label for="customerType">Customer Type *</label>
                            <select id="customerType" class="tp-input">
                                <option value="retail">Retail Customer</option>
                                <option value="wholesale">Wholesale Customer</option>
                                <option value="custom">Custom Customer</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Product Category *</label>
                    <select id="productCategory" class="tp-input">
                        <option value="fabric">Fabric</option>
                        <option value="readymade">Ready-Made</option>
                        <option value="accessories">Accessories</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>

                <div class="form-group" id="sizeSelector" style="display:none;">
                    <label>Select Size *</label>
                    <div class="tp-size-radio-group">
                        <label><input type="radio" name="productSize" value="S"> S</label>
                        <label><input type="radio" name="productSize" value="M" checked> M</label>
                        <label><input type="radio" name="productSize" value="L"> L</label>
                        <label><input type="radio" name="productSize" value="XL"> XL</label>
                        <label><input type="radio" name="productSize" value="XXL"> XXL</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Select Product *</label>
                    <select id="productSelect" class="tp-input">
                        <option value="">-- Select or scan barcode --</option>
                    </select>
                </div>

                <select id="variantSelect" class="tp-input" disabled hidden>
                    <option value="">No Variant</option>
                </select>

                <div class="form-group" id="singleQuantityGroup">
                    <label>Quantity</label>
                    <input id="quantity" type="number" min="1" step="1" class="tp-input" value="1">
                    <small class="tp-hint" id="qtyUnitHint">-</small>
                </div>

                <div class="form-group" id="customQuantityContainer" style="display:none;"></div>

                <div class="form-group">
                    <button type="button" id="addToBill" class="tp-btn">
                        <i class="fas fa-plus-circle"></i> Add to Bill
                    </button>
                </div>
            </div>

            {{-- RIGHT: Live bill --}}
            <div class="live-bill" id="billPrintArea">
                <div class="bill-header">
                    <div class="bill-title" id="billType">Estimated Bill</div>
                    <div class="toggle-switch">
                        <span>VAT (13%)</span>
                        <label class="switch">
                            <input type="checkbox" id="vatToggle">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div class="customer-display">
                    <div id="billCustomer">-</div>
                </div>

                <div class="bill-items" id="billItems"></div>

                <div class="tailoring-breakdown" id="tailoringBreakdown" style="display:none;"></div>

                <div class="discount-section">
                    <h4><i class="fas fa-tag"></i> Discount</h4>
                    <div class="discount-row">
                        <select id="discountType" class="tp-input">
                            <option value="none">No Discount</option>
                            <option value="flat">Flat Amount</option>
                            <option value="percent">Percentage (%)</option>
                        </select>
                        <input type="number" id="discountValue" class="tp-input" placeholder="Value" disabled>
                        <button type="button" id="applyDiscount" class="btn-secondary">Apply</button>
                    </div>
                    <div id="discountDisplay" class="discount-display"></div>
                </div>

                <div class="bill-summary">
                    <div class="summary-row">
                        <span>Tailoring Charges:</span>
                        <span id="tailoringTotal">0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="subtotal">0.00</span>
                    </div>
                    <!-- <div class="summary-row">
                        <span>Subtotal (Fabric & Ready-made):</span>
                        <span id="subtotalFabric">0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Subtotal (Custom Products):</span>
                        <span id="subtotalCustom">0.00</span>
                    </div> -->
                    <div class="summary-row">
                        <span>Discount:</span>
                        <span id="discountTotal">0.00</span>
                    </div>
                    <div class="summary-row" id="vatRow" style="display:none;">
                        <span>VAT (13%):</span>
                        <span id="vatAmount">0.00</span>
                    </div>
                    <div class="summary-row total-row">
                        <span>Grand Total:</span>
                        <span id="grandTotal">0.00</span>
                    </div>
                </div>

                <div class="bill-actions">
                    <button type="button" id="printBill" class="btn-success">
                        <i class="fas fa-print"></i> Print Bill
                    </button>
                    <button type="submit" id="saveOrder" class="btn-secondary">
                        <i class="fas fa-file-export"></i> {{ $editingOrder ? 'Update Order' : 'Save Order' }}
                    </button>
                    <button type="button" id="clearBill" class="btn-danger">
                        <i class="fas fa-trash-alt"></i> Clear Bill
                    </button>
                </div>
            </div>
            </div>
        </section>
    </div>

    {{-- Edit Quantity Modal --}}
    <div id="editQtyModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1300; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:var(--radius); padding:24px; min-width:280px; max-width:360px; box-shadow:var(--shadow);">
            <h4 style="margin:0 0 14px; padding-bottom:8px; border-bottom:2px solid #eee; color:var(--primary);">Update Quantity</h4>
            <div class="form-group">
                <label id="editQtyLabel" style="display:block; margin-bottom:5px; font-weight:600; color:var(--secondary);">Quantity</label>
                <input id="editQtyInput" type="number" min="0.01" step="any" class="tp-input">
            </div>
            <div style="display:flex; gap:10px; margin-top:16px; justify-content:flex-end;">
                <button type="button" id="cancelEditQty" class="btn-secondary">Cancel</button>
                <button type="button" id="confirmEditQty" class="tp-btn">Update</button>
            </div>
        </div>
    </div>

    {{-- Measurement Modal --}}
    <div class="tp-modal-overlay" id="measurementModal" style="display:none;">
        <div class="tp-modal">
            <div class="tp-modal-header">
                <h3><i class="fas fa-ruler-combined"></i> Measurements & Tailoring (<span id="modalProductCounter">1/1</span>)</h3>
                <button type="button" class="tp-modal-close" id="closeModal">&times;</button>
            </div>

            <div class="tp-modal-body">
                <div class="tp-modal-info">
                    <div class="tp-modal-product-row">
                        <div>
                            <strong>Product:</strong>
                            <span id="modalProductName">-</span>
                        </div>
                    </div>
                    <div id="modalProductSelectWrap" class="tp-form-group">
                        <label for="modalProductSelect">Change Product</label>
                        <select id="modalProductSelect" class="tp-input">
                            <option value="">Select Product</option>
                        </select>
                    </div>
                    <div><strong>Fabric Price:</strong> <span id="modalProductPrice">NPR 0.00 per meter</span></div>
                    <div><strong>Fabric Quantity (meters):</strong> <span id="modalProductQuantity">0.00</span></div>
                    <div class="tp-form-group">
                        <label>Fabric Source</label>
                        <div style="display:flex; gap:16px; flex-wrap:wrap;">
                            <label style="display:flex; gap:8px; align-items:center;">
                                <input type="radio" name="customFabricSource" id="customFabricOwn" value="own" checked>
                                Customer Fabric
                            </label>
                            <label style="display:flex; gap:8px; align-items:center;">
                                <input type="radio" name="customFabricSource" id="customFabricStock" value="stock">
                                Stock Fabric (from current outlet)
                            </label>
                        </div>
                    </div>
                    <div id="customOwnFabricFields" class="tp-form-group">
                        <label for="customOwnFabricQty">Fabric Quantity *</label>
                        <input id="customOwnFabricQty" type="number" min="0.01" step="0.01" class="tp-input" value="1.00">
                    </div>
                    <div id="customStockFabricFields" style="display:none;" class="tp-form-group">
                        <label for="customStockFabricQty">Stock Fabric Quantity *</label>
                        <input id="customStockFabricQty" type="number" min="0.01" step="0.01" class="tp-input" value="1.00">
                        <small id="customStockFabricHint" class="tp-hint"></small>
                    </div>
                </div>

                <div class="tp-form-group" style="margin-top: 14px;">
                    <label>Garment Type *</label>
                    <div id="garmentTypeCheckboxes" class="tp-garment-check-group"></div>
                    <small class="tp-hint">Select one or more garments for the current fabric.</small>
                </div>

                <div class="tp-modal-section">
                    <h4>Measurement Details</h4>
                    <div id="measurementFields" class="tp-garment-sections"></div>
                    <small class="tp-hint">Each selected garment keeps its own quantity, measurements, tailoring package, optional design notes, and optional design samples.</small>
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
:root{--primary:#1a365d;--secondary:#2d4a7c;--accent:#c9a96e;--light:#f8f9fa;--dark:#343a40;--success:#28a745;--danger:#dc3545;--radius:8px;--shadow:0 4px 12px rgba(0,0,0,.1);}
.tp-container{max-width:1400px;margin:0 auto;padding:20px;}
.tp-alert{margin-top:16px;padding:12px 14px;border-radius:var(--radius);}
.tp-alert-danger{background:#ffe8ea;color:#7a0b18;border:1px solid #ffccd1;}
.tp-alert-success{background:#e7f7ed;color:#0d5a2b;border:1px solid #bfe8cf;}
.demo-section{background:#fff;padding:25px;border-radius:var(--radius);box-shadow:var(--shadow);}
.demo-section-header{display:flex;justify-content:flex-start;margin-bottom:18px;}
.tp-back-link{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border:1px solid #d7e0ec;border-radius:999px;background:#f7fafd;color:var(--primary);font-weight:700;text-decoration:none;transition:background-color .2s ease,border-color .2s ease,color .2s ease;}
.tp-back-link:hover{background:#eef3f9;border-color:#c2d0e1;color:var(--secondary);}
.demo-layout{display:grid;grid-template-columns:1fr 1fr;gap:30px;margin-top:20px;}
@media(max-width:1024px){.demo-layout{grid-template-columns:1fr;}}
.order-entry{padding:20px;background:#f8f9fa;border-radius:var(--radius);border:1px solid #ddd;}
.live-bill{padding:24px;background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);border-radius:18px;border:1px solid #d9e3f0;box-shadow:0 18px 40px rgba(26,54,93,.08);}
h2,h3,h4{color:var(--primary);margin-bottom:15px;padding-bottom:8px;border-bottom:2px solid #eee;}
h2 i,h3 i,h4 i{margin-right:10px;color:var(--accent);}
.form-group{margin-bottom:15px;}
label{display:block;margin-bottom:5px;font-weight:600;color:var(--secondary);}
.tp-input{width:100%;padding:10px 15px;border:1px solid #ccc;border-radius:var(--radius);font-size:1rem;}
.demo-section .select2-container--default .select2-selection--single,
.tp-modal .select2-container--default .select2-selection--single{
    min-height:auto;
    height:43px;
    border:1px solid #c9d5e6;
    border-radius:var(--radius);
    background:#f7fafd;
    padding:0 40px 0 0;
    font-size:1rem;
    font-family:inherit;
    box-shadow:none;
    display:block;
}
.demo-section .select2-container--default .select2-selection--single .select2-selection__rendered,
.tp-modal .select2-container--default .select2-selection--single .select2-selection__rendered{
    color:inherit;
    line-height:43px;
    padding-left:14px;
    padding-right:30px;
    margin-left:0;
}
.demo-section .select2-container--default .select2-selection--single .select2-selection__placeholder,
.tp-modal .select2-container--default .select2-selection--single .select2-selection__placeholder{
    color:#6a7785;
}
.demo-section .select2-container--default .select2-selection--single .select2-selection__arrow,
.tp-modal .select2-container--default .select2-selection--single .select2-selection__arrow{
    height:100%;
    right:10px;
}
.demo-section .select2-container--default .select2-selection--single .select2-selection__clear,
.tp-modal .select2-container--default .select2-selection--single .select2-selection__clear{
    position:absolute;
    right:32px;
    top:50%;
    transform:translateY(-50%);
    color:#6c757d;
    font-size:18px;
    line-height:1;
    margin-right:0;
    padding:0;
    float:none;
}
.demo-section .select2-container--default .select2-selection--single .select2-selection__clear:hover,
.tp-modal .select2-container--default .select2-selection--single .select2-selection__clear:hover{
    color:#dc3545;
}
.demo-section .select2-container--default.select2-container--focus .select2-selection--single,
.demo-section .select2-container--default.select2-container--open .select2-selection--single,
.tp-modal .select2-container--default.select2-container--focus .select2-selection--single,
.tp-modal .select2-container--default.select2-container--open .select2-selection--single{
    border-color:var(--accent);
    background:#fff;
    box-shadow:0 0 0 3px rgba(201,169,110,.16);
}
.demo-section .select2-container--default .select2-selection--multiple{
    min-height:43px;
    border:1px solid #c9d5e6;
    border-radius:var(--radius);
    background:#f7fafd;
    padding:4px 10px;
}
.demo-section .select2-container--default .select2-selection--multiple .select2-selection__choice{
    background:var(--primary);
    border:none;
    color:#fff;
    border-radius:999px;
    padding:4px 10px 4px 12px;
    margin-top:4px;
    display:inline-flex;
    align-items:center;
    gap:8px;
    flex-direction:row-reverse;
    font-weight:600;
}
.demo-section .select2-container--default .select2-selection--multiple .select2-selection__choice__remove{
    position:static;
    color:#fff;
    margin:0;
    margin-left:2px;
    width:18px;
    height:18px;
    border-radius:999px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    background:rgba(255,255,255,.18);
    transition:background-color .2s ease,color .2s ease;
    flex-shrink:0;
}
.demo-section .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover{
    background:#fff;
    color:var(--primary);
}
.demo-section .select2-container--default .select2-selection--multiple .select2-selection__choice__display{
    padding-left:0;
    padding-right:0;
}
.demo-section .select2-container--default.select2-container--focus .select2-selection--multiple,
.demo-section .select2-container--default.select2-container--open .select2-selection--multiple{
    border-color:var(--accent);
    background:#fff;
    box-shadow:0 0 0 3px rgba(201,169,110,.16);
}
.demo-section .select2-dropdown{
    border:1px solid #d7e0ec;
    border-radius:var(--radius);
    box-shadow:0 12px 24px rgba(26,54,93,.12);
    background:#fff;
}
.demo-section .select2-search--dropdown .select2-search__field{
    border:1px solid #c9d5e6;
    border-radius:var(--radius);
    font-family:inherit;
    color:var(--primary);
    background:#f7fafd;
}
.demo-section .select2-search--dropdown .select2-search__field:focus{
    outline:none;
    border-color:var(--accent);
    box-shadow:0 0 0 3px rgba(201,169,110,.14);
}
.demo-section .select2-results__option{
    color:var(--primary);
    padding:10px 14px;
}
.demo-section .select2-container--default .select2-results__option--selected{
    background:#eef3f9;
    color:var(--primary);
}
.demo-section .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable{
    background:var(--primary);
    color:#fff;
}
.tp-hint{color:#6a7785;font-size:12px;}
.instructions{background:#f0f7ff;padding:15px;border-radius:var(--radius);margin-top:15px;border-left:4px solid #17a2b8;font-size:.9rem;}
.customer-info{background:#f8f9fa;padding:15px;border-radius:var(--radius);margin-bottom:20px;}
.customer-check-row{display:flex;gap:10px;}
.customer-check-row .tp-input{flex:1;}
.customer-check-message{font-size:.9rem;margin-top:10px;padding:10px;border-radius:var(--radius);border-left:4px solid var(--accent);}
.customer-details{display:none;background:#f0f7ff;padding:15px;border-radius:var(--radius);margin-top:15px;border-left:4px solid var(--accent);}
.tp-size-radio-group{display:flex;gap:12px;flex-wrap:wrap;margin-top:8px;}
.tp-size-radio-group label{display:flex;align-items:center;gap:6px;font-weight:500;background:#e9eef5;padding:8px 14px;border-radius:999px;cursor:pointer;color:var(--primary);}
.tp-size-radio-group input[type="radio"]{width:auto;margin:0;}
button,.tp-btn,.btn-secondary,.btn-success,.btn-danger{color:#fff;border:none;padding:12px 20px;border-radius:var(--radius);cursor:pointer;font-weight:600;transition:background-color .3s;}
button,.tp-btn{background:var(--primary);}
button:hover,.tp-btn:hover{background:var(--secondary);}
.btn-secondary{background:#6c757d;}
.btn-secondary:hover{background:#5a6268;}
.btn-success{background:var(--success);}
.btn-success:hover{background:#218838;}
.btn-danger{background:var(--danger);}
.btn-danger:hover{background:#c82333;}
.tp-btn-secondary{background:#6c757d;}
.tp-btn-secondary:hover{background:#5a6268;}
.tp-btn-success{background:var(--success);}
.tp-btn-success:hover{background:#218838;}
.tp-btn-danger{background:var(--danger);}
.tp-btn-danger:hover{background:#c82333;}
.bill-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid #d9e3f0;}
.bill-title{font-size:1.5rem;font-weight:700;color:var(--primary);}
.toggle-switch{display:flex;align-items:center;gap:10px;}
.switch{position:relative;display:inline-block;width:60px;height:30px;}
.switch input{opacity:0;width:0;height:0;}
.slider{position:absolute;cursor:pointer;inset:0;background:#ccc;transition:.4s;border-radius:34px;}
.slider:before{position:absolute;content:"";height:22px;width:22px;left:4px;bottom:4px;background:#fff;transition:.4s;border-radius:50%;}
.switch input:checked+.slider{background:var(--success);}
.switch input:checked+.slider:before{transform:translateX(30px);}
.customer-display{background:linear-gradient(135deg,#f4f8fd 0%,#eef4fb 100%);padding:16px 18px;border-radius:14px;margin-bottom:20px;border:1px solid #dbe5f1;}
.bill-items{margin-bottom:20px;max-height:360px;overflow-y:auto;display:grid;gap:12px;padding-right:4px;}
.bill-item{padding:16px;background:#fff;border:1px solid #e1eaf4;border-radius:16px;display:grid;gap:10px;box-shadow:0 8px 18px rgba(22,44,72,.05);}
.item-details{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0;gap:16px;}
.item-main{flex:1;min-width:0;}
.item-price{flex-shrink:0;min-width:132px;text-align:right;padding:10px 12px;border-radius:12px;background:#f5f8fc;color:var(--primary);}
.item-sub{font-size:.9rem;color:#5f7083;margin-top:6px;line-height:1.5;}
.item-sub-amount{font-weight:600;color:#334155;}
.stitching-detail{background:linear-gradient(135deg,#fbf4ff 0%,#f6ecff 100%);padding:12px 14px;border-radius:12px;font-size:.9rem;border:1px solid #e6d8f5;border-left:4px solid #7b1fa2;width:100%;box-sizing:border-box;display:flex;justify-content:space-between;align-items:flex-start;gap:12px;grid-column:1 / -1;}
.stitching-detail > div:first-child{flex:1;min-width:0;}
.stitching-detail > div:last-child{flex-shrink:0;white-space:nowrap;}
.product-actions{display:flex;gap:10px;margin-top:10px;}
.product-actions button{padding:6px 12px;font-size:.8rem;border-radius:999px;}
.product-category{display:inline-block!important;padding:4px 10px;border-radius:20px;font-size:.8rem;font-weight:600;margin-left:10px;}
.fabric-cat{background:#e3f2fd;color:#1565c0;}
.ready-made-cat{background:#e8f5e9;color:#2e7d32;}
.custom-cat{background:#f3e5f5;color:#7b1fa2;}
.tailoring-breakdown{background:linear-gradient(135deg,#fbf4ff 0%,#f5ebff 100%);padding:14px 16px;border-radius:14px;margin-bottom:16px;border:1px solid #eadcf6;}
.tailoring-breakdown h5{margin:0 0 8px;color:#7b1fa2;}
.tailoring-item{display:flex;justify-content:space-between;padding:4px 0;}
.tailoring-item-amount{font-weight:600;color:#334155;}
.tailoring-total{font-weight:700;border-top:1px solid #ddd;margin-top:8px;padding-top:8px;}
.discount-section{background:#f7fafc;padding:16px;border-radius:14px;margin-top:15px;border:1px solid #dbe5f1;}
.discount-row{display:flex;gap:10px;margin-top:10px;}
.discount-row .tp-input{flex:1;}
.discount-display{margin-top:10px;font-weight:700;color:var(--success);}
.bill-summary{border-top:1px solid #dbe5f1;padding-top:16px;margin-top:18px;display:grid;gap:4px;}
.summary-row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;color:#34506b;}
.summary-row span:last-child{font-weight:700;color:#17324d;}
.total-row{font-weight:700;font-size:1.15rem;color:var(--primary);border-top:1px solid #dbe5f1;margin-top:8px;padding-top:14px;}
.total-row span:last-child{font-size:1.4rem;color:#0f2942;}
.bill-actions{margin-top:22px;display:flex;gap:12px;}
.bill-actions>*{flex:1;}
.tp-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1200;display:flex;align-items:stretch;justify-content:center;padding:0 20px;}
.tp-modal{width:100%;max-width:1400px;height:100vh;background:#fff;border-radius:0;box-shadow:none;overflow:hidden;display:flex;flex-direction:column;}
.tp-modal-header{background:var(--primary);color:#fff;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;}
.tp-modal-header h3{margin:0;color:#fff;border:0;padding:0;}
.tp-modal-close{background:transparent;border:0;color:#fff;font-size:24px;cursor:pointer;padding:0;width:30px;height:30px;display:flex;align-items:center;justify-content:center;}
.tp-modal-body{padding:16px;flex:1 1 auto;overflow:auto;}
.tp-modal-info{background:#f8f9fa;border:1px solid #eef1f5;border-radius:10px;padding:10px;display:grid;gap:6px;}
.tp-modal-product-row{display:flex;align-items:center;gap:12px;}
.tp-modal-section{margin-top:14px;border:1px solid #eef1f5;border-radius:10px;padding:12px;}
.tp-modal-section h4{margin:0 0 10px;color:var(--primary);}
.tp-measurement-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;}
.tp-garment-sections{display:grid;gap:14px;}
.tp-garment-check-group{display:flex;gap:12px;flex-wrap:wrap;margin-top:10px;}
.tp-garment-check-item{display:flex;align-items:center;gap:8px;padding:9px 14px;border-radius:999px;background:#eef3f9;border:1px solid #d7e0ec;color:var(--primary);font-weight:600;cursor:pointer;}
.tp-garment-check-item input[type="checkbox"]{width:auto;margin:0;}
.tp-garment-section{border:1px solid #e2d7ef;border-radius:12px;padding:14px;background:#fff;}
.tp-garment-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px;}
.tp-garment-title{font-weight:700;color:#7b1fa2;}
.tp-garment-summary{font-size:12px;color:#6a7785;}
.tp-product-quantity-list{display:grid;gap:10px;padding:12px;border:1px solid #d7e0ec;border-radius:10px;background:#fff;}
.tp-product-quantity-item{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;border:1px solid #eef1f5;border-radius:10px;}
.tp-product-quantity-item strong{color:var(--primary);}
.tp-product-quantity-input{display:flex;align-items:center;gap:8px;}
.tp-product-quantity-input input{width:110px;}
.tp-stitching{background:#f9f0ff;border-color:#eadcf6;}
.tp-tailoring-options{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;}
.tp-tailoring-option{background:#fff;border:1px solid #d1c4e9;border-radius:10px;padding:12px;cursor:pointer;transition:.2s;}
.tp-tailoring-option:hover{border-color:#7b1fa2;background:#f3e5f5;}
.tp-tailoring-option.selected{border-color:#7b1fa2;background:#e1bee7;box-shadow:0 0 0 2px rgba(123,31,162,.18);}
.tp-tailoring-option h5{margin:0 0 4px;color:#7b1fa2;}
.tp-selected-package{margin-top:10px;background:#e1bee7;border-radius:10px;padding:10px;}
.tp-design-note-panel{margin-top:12px;padding:14px 18px 18px;border-radius:12px;background:#eef4fa;border:1px solid #dbe6f2;}
.tp-design-note-heading{margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid #d7b5eb;color:#a100c4;font-size:1.1rem;font-weight:700;display:flex;align-items:center;gap:8px;}
.tp-design-note-list{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px 22px;}
.tp-design-note-item{display:flex;align-items:center;gap:8px;color:#334a92;font-size:1rem;font-weight:500;}
.tp-design-note-item input{margin:0;}
.tp-design-upload-panel{margin-top:18px;padding:16px 18px;border:1px dashed #e2bd71;border-radius:12px;background:#fffdfa;}
.tp-design-upload-heading{margin:0 0 12px;color:#3e4a92;font-size:1.1rem;font-weight:700;display:flex;align-items:center;justify-content:center;gap:8px;text-align:center;}
.tp-design-upload-input{padding:12px 14px;background:#fff;border:1px solid #cfd9e5;border-radius:10px;}
.tp-existing-image-note{margin-top:8px;color:#5d6b7a;font-size:.9rem;text-align:center;}
@media(max-width:900px){.tp-design-note-list{grid-template-columns:repeat(2,minmax(0,1fr));}}
@media(max-width:560px){.tp-design-note-list{grid-template-columns:1fr;}}
.tp-modal-footer{padding:14px 16px;background:#f8f9fa;border-top:1px solid #eef1f5;display:flex;justify-content:flex-end;gap:10px;flex-shrink:0;}
@media print{body *{visibility:hidden !important;}#billPrintArea,#billPrintArea *{visibility:visible !important;}#billPrintArea{position:absolute !important;top:0;left:0;width:100% !important;max-width:100% !important;margin:0 !important;padding:0 !important;border:0 !important;box-shadow:none !important;background:#fff !important;}#billPrintArea .bill-actions,#billPrintArea .toggle-switch,#billPrintArea .discount-section button,#billPrintArea #clearBill,#billPrintArea #printBill{display:none !important;}}
</style>
@endsection

@section('page-specific-script')
@php
    $resolvedInitialOrderState = $initialOrderState ?? [
        'items' => [],
        'discount' => [
            'type' => 'none',
            'value' => 0,
        ],
        'vatEnabled' => false,
    ];
@endphp
<script>
(function() {
    const products = @json($productPayload);
    const garmentTypes = @json($garmentPayload);
    const customerMeasurements = @json($customerMeasurementPayload);
    const customerDirectory = @json($customerLookupPayload);
    const initialOrderState = @json($resolvedInitialOrderState);
    const initialVatEnabled = Boolean(initialOrderState?.vatEnabled);
    const resolveCustomerUrl = @json(route('order.customer.resolve'));
    const csrfToken = @json(csrf_token());

    const productMap = new Map(products.map((product) => [String(product.id), product]));
    const garmentMap = new Map(garmentTypes.map((garment) => [String(garment.id), garment]));

    const categorySelect = document.getElementById('productCategory');
    const productSelect = document.getElementById('productSelect');
    const qtyInput = document.getElementById('quantity');
    const unitHint = document.getElementById('qtyUnitHint');
    const addBtn = document.getElementById('addToBill');
    const singleQuantityGroup = document.getElementById('singleQuantityGroup');
    const customQuantityContainer = document.getElementById('customQuantityContainer');
    const sizeSelector = document.getElementById('sizeSelector');
    const sizeRadios = Array.from(document.querySelectorAll('input[name="productSize"]'));

    const billItemsEl = document.getElementById('billItems');
    const tailoringBreakdownEl = document.getElementById('tailoringBreakdown');

    const customerSelect = document.getElementById('customer_id');
    const billCustomerEl = document.getElementById('billCustomer');
    const customerPhoneInput = document.getElementById('customerPhone');
    const checkCustomerBtn = document.getElementById('checkCustomerBtn');
    const customerCheckMessage = document.getElementById('customerCheckMessage');
    const customerDetails = document.getElementById('customerDetails');
    const customerNameInput = document.getElementById('customerName');
    const customerTypeInput = document.getElementById('customerType');

    const discountTypeEl = document.getElementById('discountType');
    const discountValueEl = document.getElementById('discountValue');
    const applyDiscountBtn = document.getElementById('applyDiscount');
    const discountDisplayEl = document.getElementById('discountDisplay');

    const vatToggle = document.getElementById('vatToggle');
    const billTypeEl = document.getElementById('billType');
    const vatRow = document.getElementById('vatRow');
    const vatAmountEl = document.getElementById('vatAmount');

    const subtotalEl = document.getElementById('subtotal');
    const subtotalFabricEl = document.getElementById('subtotalFabric');
    const subtotalCustomEl = document.getElementById('subtotalCustom');
    const tailoringTotalEl = document.getElementById('tailoringTotal');
    const discountTotalEl = document.getElementById('discountTotal');
    const grandTotalEl = document.getElementById('grandTotal');
    const discountAmountInputEl = document.getElementById('discount_amount');

    const clearBillBtn = document.getElementById('clearBill');
    const printBillBtn = document.getElementById('printBill');
    const saveOrderBtn = document.getElementById('saveOrder');
    const printBillInput = document.getElementById('print_bill');
    const orderForm = document.getElementById('orderForm');
    const hiddenInputsHost = document.getElementById('itemsHiddenInputs');

    const modal = document.getElementById('measurementModal');
    const closeModalBtn = document.getElementById('closeModal');
    const cancelMeasurementBtn = document.getElementById('cancelMeasurement');
    const saveMeasurementBtn = document.getElementById('saveMeasurement');
    const modalProductName = document.getElementById('modalProductName');
    const changeModalProductBtn = document.getElementById('changeModalProductBtn');
    const modalProductSelectWrap = document.getElementById('modalProductSelectWrap');
    const modalProductSelect = document.getElementById('modalProductSelect');
    const modalProductPrice = document.getElementById('modalProductPrice');
    const modalProductQuantity = document.getElementById('modalProductQuantity');
    const modalProductCounter = document.getElementById('modalProductCounter');
    const garmentTypeCheckboxes = document.getElementById('garmentTypeCheckboxes');
    const measurementFieldsEl = document.getElementById('measurementFields');
    const customFabricOwnRadio = document.getElementById('customFabricOwn');
    const customFabricStockRadio = document.getElementById('customFabricStock');
    const customOwnFabricFields = document.getElementById('customOwnFabricFields');
    const customOwnFabricQty = document.getElementById('customOwnFabricQty');
    const customStockFabricFields = document.getElementById('customStockFabricFields');
    const customStockFabricQty = document.getElementById('customStockFabricQty');
    const customStockFabricHint = document.getElementById('customStockFabricHint');
    const editQtyModal = document.getElementById('editQtyModal');
    const editQtyInput = document.getElementById('editQtyInput');
    const editQtyLabel = document.getElementById('editQtyLabel');

    let billItems = Array.isArray(initialOrderState?.items) ? initialOrderState.items : [];
    let discount = initialOrderState?.discount || { type: 'none', value: 0 };
    let vatEnabled = Boolean(initialVatEnabled);
    let pendingCustomQueue = [];
    let pendingCustomIndex = 0;
    let editingCustomIndex = -1;
    let selectedCustomProductIds = [];
    let customerSubmitInProgress = false;

    const customersByPhone = new Map();
    const customersById = new Map();

    function money(value) {
        return Number(value || 0).toFixed(2);
    }

    function normalizeUnitLabel(unitLabel) {
        const normalizedUnit = String(unitLabel || '').trim().toLowerCase();
        if (!normalizedUnit) return 'meter';
        if (normalizedUnit === 'm') return 'meter';
        if (normalizedUnit === 'pcs') return 'pieces';
        if (normalizedUnit === 'pc') return 'piece';
        return normalizedUnit;
    }

    function formatFabricPrice(value, unitLabel = 'meter') {
        return `NPR ${money(value)} per ${normalizeUnitLabel(unitLabel)}`;
    }

    function buildModalProductSelect() {
        if (!modalProductSelect) return;

        modalProductSelect.innerHTML = '<option value="">Select Product</option>';
        filterProductsByCategory('custom').forEach((product) => {
            const option = document.createElement('option');
            option.value = String(product.id);
            option.textContent = `${product.name} (${product.code}) | Available: ${money(product.availableQty)} ${product.unitLabel || ''}`.trim();
            modalProductSelect.appendChild(option);
        });

        setupOrderSelect2(modalProductSelect, 'Change custom fabric');
    }

    function applyPendingProductChange(productId) {
        const pending = getCurrentPendingCustom();
        const selectedProduct = productMap.get(String(productId || ''));

        if (!pending || !selectedProduct) {
            return;
        }

        pending.productId = selectedProduct.id;
        pending.name = `${selectedProduct.name} (${selectedProduct.code})`;
        pending.unitLabel = selectedProduct.unitLabel || '';
        pending.unitPrice = resolveDefaultPrice(selectedProduct.id);

        modalProductName.textContent = pending.name;
        modalProductPrice.textContent = formatFabricPrice(pending.unitPrice, pending.unitLabel || 'meter');
        updateCustomFabricSourceUI();
    }

    function normalizePhone(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function updatePaymentSummary(discountAmount) {
        const safeDiscount = Math.max(Number(discountAmount || 0), 0);
        if (discountAmountInputEl) {
            discountAmountInputEl.value = money(safeDiscount);
        }
    }

    function setupOrderSelect2(selectEl, placeholder, extraOptions = {}) {
        if (!selectEl || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
            return;
        }

        const $select = window.jQuery(selectEl);
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.off('.orderSelect2');
            $select.select2('destroy');
        }

        const hasEmptyOption = Array.from(selectEl.options || []).some((option) => option.value === '');
        $select.select2({
            width: '100%',
            placeholder,
            allowClear: hasEmptyOption && !selectEl.multiple,
            closeOnSelect: !selectEl.multiple,
            dropdownParent: window.jQuery(selectEl.closest('.tp-modal') || document.body),
            ...extraOptions,
        });
    }

    function bindSelect2Change(selectEl, handler) {
        if (!selectEl || typeof handler !== 'function') {
            return;
        }

        selectEl.addEventListener('change', handler);
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            window.jQuery(selectEl).on('change.orderSelect2 select2:select.orderSelect2 select2:unselect.orderSelect2 select2:clear.orderSelect2', handler);
        }
    }

    function bindProductSelectionSync() {
        productSelect.removeEventListener('change', syncCustomProductQuantities);
        productSelect.addEventListener('change', syncCustomProductQuantities);

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            window.jQuery(productSelect)
                .off('.customQtySync')
                .on('change.customQtySync select2:select.customQtySync select2:unselect.customQtySync select2:clear.customQtySync', () => {
                    window.requestAnimationFrame(() => {
                        syncCustomProductQuantities();
                        updateProductSelectionUI();
                    });
                });
        }
    }

    function getSelectedProductIds() {
        if (productSelect.multiple) {
            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                return (window.jQuery(productSelect).val() || []).map((value) => String(value || '')).filter(Boolean);
            }

            return Array.from(productSelect.selectedOptions || []).map((option) => String(option.value || '')).filter(Boolean);
        }

        return productSelect.value ? [String(productSelect.value)] : [];
    }

    function getCurrentPendingCustom() {
        return pendingCustomQueue[pendingCustomIndex] || null;
    }

    function selectedCustomFabricSource() {
        return customFabricStockRadio?.checked ? 'stock' : 'own';
    }

    function selectedReadymadeSize() {
        return sizeRadios.find((radio) => radio.checked)?.value || 'M';
    }

    function upsertCustomerIndex(customer) {
        const normalized = normalizePhone(customer?.phone || '');
        const payload = {
            id: Number(customer?.id || 0),
            name: String(customer?.name || ''),
            phone: String(customer?.phone || ''),
            customerType: String(customer?.customer_type || ''),
        };

        if (payload.id > 0) {
            customersById.set(String(payload.id), payload);
        }

        if (normalized) {
            customersByPhone.set(normalized, payload);
        }
    }

    function formatCustomerTypeLabel(customerType) {
        if (customerType === 'retail') return 'Retail Customer';
        if (customerType === 'wholesale') return 'Wholesale Customer';
        if (customerType === 'custom') return 'Custom Customer';
        return 'Customer';
    }

    function selectCustomer(customer) {
        customerSelect.value = String(customer.id);
        customerPhoneInput.value = customer.phone || customerPhoneInput.value;
        customerNameInput.value = customer.name || '';
        customerTypeInput.value = customer.customerType || customer.customer_type || 'retail';
        hideCustomerDetails();
        setCustomerCheckMessage('');
        updateCustomerDisplay();
    }

    function showCustomerDetails() {
        customerDetails.style.display = 'block';
    }

    function hideCustomerDetails() {
        customerDetails.style.display = 'none';
    }

    function setCustomerCheckMessage(message, type = 'info') {
        if (!customerCheckMessage) {
            return;
        }

        customerCheckMessage.textContent = message || '';
        customerCheckMessage.style.display = message ? 'block' : 'none';
        customerCheckMessage.style.borderLeftColor = type === 'success' ? 'var(--success)' : 'var(--accent)';
        customerCheckMessage.style.background = type === 'success' ? '#edf9f0' : '#f0f7ff';
    }

    async function resolveOrCreateCustomer(options = {}) {
        const rawPhone = String(customerPhoneInput.value || '').trim();
        const normalizedPhone = normalizePhone(rawPhone);
        const shouldCreate = Boolean(options.createIfMissing);
        const payload = {
            phone: rawPhone,
            name: (customerNameInput.value || '').trim(),
            customer_type: customerTypeInput.value || 'retail',
        };

        if (payload.phone.length < 7) {
            throw new Error('Enter a valid phone number first.');
        }

        const response = await fetch(resolveCustomerUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(shouldCreate ? payload : { phone: rawPhone }),
        });

        const data = await response.json();
        if (!response.ok) {
            throw new Error(data?.message || 'Unable to resolve customer.');
        }

        if ((data?.status === 'existing' || data?.status === 'created') && data?.customer?.id) {
            upsertCustomerIndex(data.customer);
            selectCustomer(data.customer);
            return data.customer;
        }

        if (data?.status === 'missing') {
            customerSelect.value = '';
            showCustomerDetails();
            setCustomerCheckMessage('');
            updateCustomerDisplay();
            return null;
        }

        throw new Error('Customer response was invalid.');
    }

    function updateCustomerDisplay() {
        const customer = customersById.get(String(customerSelect.value || ''));
        if (!customer) {
            const phone = normalizePhone(customerPhoneInput.value);
            if (!phone) {
                billCustomerEl.textContent = '-';
                return;
            }

            const customerName = (customerNameInput.value || '').trim() || 'New Customer';
            billCustomerEl.innerHTML = `
            <div><strong>Customer:</strong> ${customerName} (${phone})</div>
            <div><strong>Type:</strong> ${formatCustomerTypeLabel(customerTypeInput.value || 'retail')}</div>
        `;
            return;
        }

        const customerName = customer.phone ? `${customer.name} (${customer.phone})` : customer.name;
        billCustomerEl.innerHTML = `
            <div><strong>Customer:</strong> ${customerName}</div>
            <div><strong>Type:</strong> ${formatCustomerTypeLabel(customer.customerType)}</div>
        `;
    }

    function getCategoryLabel(category) {
        if (category === 'fabric') return ['Fabric', 'product-category fabric-cat'];
        if (category === 'readymade') return ['Ready-Made', 'product-category ready-made-cat'];
        if (category === 'accessories') return ['Accessories', 'product-category ready-made-cat'];
        return ['Custom', 'product-category custom-cat'];
    }

    function getAvailableQtyForProduct(product) {
        return Number(product?.availableQty || 0);
    }

    function filterProductsByCategory(category) {
        const matchesCategory = (product) => {
            if (category === 'fabric') return product.category === 'fabrics';
            if (category === 'readymade') return product.category === 'ready-made';
            if (category === 'accessories') return product.category === 'accessories';
            return product.category === 'fabrics';
        };

        return products.filter((product) => matchesCategory(product) && getAvailableQtyForProduct(product) > 0);
    }

    function resolveDefaultPrice(productId) {
        const product = productMap.get(String(productId));
        if (!product) return 0;
        return product.defaultPrice != null ? Number(product.defaultPrice) : 0;
    }

    function rebuildProductSelect(category, preserveSelection = true) {
        const isCustom = category === 'custom';
        const list = filterProductsByCategory(category);
        const selectedValues = preserveSelection
            ? (isCustom ? getSelectedProductIds() : (productSelect.value ? [String(productSelect.value)] : []))
            : [];

        productSelect.innerHTML = '';
        productSelect.removeAttribute('size');
        if (!isCustom) {
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = '-- Select Product --';
            productSelect.appendChild(placeholder);
        }

        list.forEach((product) => {
            const option = document.createElement('option');
            option.value = String(product.id);
            option.textContent = `${product.name} (${product.code}) | Available: ${money(product.availableQty)} ${product.unitLabel || ''}`.trim();
            if (selectedValues.includes(String(product.id))) {
                option.selected = true;
            }
            productSelect.appendChild(option);
        });

        productSelect.multiple = isCustom;
        singleQuantityGroup.style.display = isCustom ? 'none' : '';
        customQuantityContainer.style.display = 'none';
        qtyInput.step = isCustom ? '0.01' : '1';
        sizeSelector.style.display = category === 'readymade' ? '' : 'none';
        if (!isCustom) {
            selectedCustomProductIds = [];
        }

        setupOrderSelect2(
            productSelect,
            isCustom ? 'Search and select custom fabrics' : 'Select or scan barcode',
            isCustom
                ? {
                    closeOnSelect: false,
                    allowClear: true,
                }
                : {}
        );
        bindProductSelectionSync();
        if (!selectedValues.length) {
            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                window.jQuery(productSelect).val(isCustom ? [] : '').trigger('change.select2');
            } else {
                productSelect.value = '';
            }
        }
        if (!isCustom) {
            productSelect.value = '';
            customQuantityContainer.innerHTML = '';
        }
        updateProductSelectionUI();
    }

    function renderCustomQuantityInputs() {
        const selectedIds = selectedCustomProductIds.slice();
        if (!selectedIds.length) {
            customQuantityContainer.innerHTML = '';
            customQuantityContainer.style.display = 'none';
            return;
        }

        const existingValues = new Map(
            Array.from(customQuantityContainer.querySelectorAll('input[data-product-id]')).map((input) => [
                String(input.dataset.productId || ''),
                Number(input.value || 1),
            ])
        );

        let html = '<label>Custom Fabric Quantities</label><div class="tp-product-quantity-list">';
        selectedIds.forEach((productId) => {
            const product = productMap.get(String(productId));
            if (!product) return;
            const value = existingValues.get(String(productId)) || 1;
            html += `
                <div class="tp-product-quantity-item">
                    <div>
                        <strong>${product.name}</strong>
                        <div class="tp-garment-summary">${product.code} | Available ${money(product.availableQty)} ${product.unitLabel || ''}</div>
                    </div>
                    <div class="tp-product-quantity-input">
                        <input type="number" class="tp-input" data-product-id="${productId}" min="0.01" step="0.01" value="${money(value)}">
                        <span>${product.unitLabel || 'qty'}</span>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        customQuantityContainer.innerHTML = html;
        customQuantityContainer.style.display = '';
    }

    function updateProductSelectionUI() {
        if (categorySelect.value === 'custom') {
            const selectedIds = selectedCustomProductIds.slice();
            unitHint.textContent = selectedIds.length
                ? `${selectedIds.length} custom fabric${selectedIds.length > 1 ? 's' : ''} selected.`
                : 'Select one or more custom fabrics.';
            return;
        }

        const product = productMap.get(String(productSelect.value || ''));
        unitHint.textContent = product
            ? `Available: ${money(product.availableQty)} ${product.unitLabel || ''}`.trim()
            : '-';
    }

    function resetOrderEntryFields() {
        categorySelect.value = 'fabric';
        selectedCustomProductIds = [];
        qtyInput.value = '1';
        sizeRadios.forEach((radio) => {
            radio.checked = false;
        });
        rebuildProductSelect(categorySelect.value, false);
        unitHint.textContent = '-';
    }

    function syncCustomProductQuantities() {
        if (categorySelect.value !== 'custom') {
            return;
        }

        selectedCustomProductIds = getSelectedProductIds();
        const selectedIds = selectedCustomProductIds.slice();
        unitHint.textContent = selectedIds.length
            ? `${selectedIds.length} custom fabric${selectedIds.length > 1 ? 's' : ''} selected.`
            : 'Select one or more custom fabrics.';
    }

    function getSelectedGarmentIds() {
        return Array.from(garmentTypeCheckboxes.querySelectorAll('input[type="checkbox"]:checked'))
            .map((input) => String(input.value || ''))
            .filter(Boolean);
    }

    function fillGarmentTypeOptions() {
        garmentTypeCheckboxes.innerHTML = '';
        garmentTypes.forEach((garment) => {
            const label = document.createElement('label');
            label.className = 'tp-garment-check-item';
            label.innerHTML = `
                <input type="checkbox" value="${garment.id}">
                <span>${garment.title}</span>
            `;
            garmentTypeCheckboxes.appendChild(label);
        });
    }

    function getCustomerMeasurementForGarment(customerId, garmentTypeId) {
        const customerRows = customerMeasurements[String(customerId)] || {};
        return customerRows[String(garmentTypeId)] || [];
    }

    function collectGarmentDraftsFromDom() {
        const drafts = new Map();
        Array.from(measurementFieldsEl.querySelectorAll('.tp-garment-section')).forEach((section) => {
            const garmentTypeId = String(section.dataset.garmentTypeId || '');
            if (!garmentTypeId) return;

            const measurements = Array.from(section.querySelectorAll('input[data-measure-type]')).map((input) => ({
                type: String(input.dataset.measureType || ''),
                measurement: String(input.value || ''),
                unit: String(input.dataset.measureUnit || ''),
            }));

            const tailoringSelect = section.querySelector('select[data-tailoring-for]');
            drafts.set(garmentTypeId, {
                quantity: Number(section.querySelector('input[data-garment-qty]')?.value || 1),
                measurements,
                designNotes: Array.from(section.querySelectorAll('input[data-design-note]:checked')).map((input) => String(input.value || '')),
                designFiles: Array.from(section.querySelector('input[data-design-images]')?.files || []),
                existingDesignImages: (() => {
                    try {
                        return JSON.parse(section.dataset.existingDesignImages || '[]');
                    } catch (error) {
                        return [];
                    }
                })(),
                tailoringPackageId: Number(tailoringSelect?.value || 0) || null,
                tailoringPackage: tailoringSelect?.selectedOptions?.[0]?.dataset.packageName || '',
            });
        });

        return drafts;
    }

    function renderGarmentSections() {
        const selectedGarmentIds = getSelectedGarmentIds();
        const existingDrafts = collectGarmentDraftsFromDom();
        const modalState = modal.dataset.itemState ? JSON.parse(modal.dataset.itemState) : {};

        if (!selectedGarmentIds.length) {
            measurementFieldsEl.innerHTML = '<div class="tp-hint">Select at least one garment type.</div>';
            return;
        }

        const customerId = customerSelect.value || '';
        measurementFieldsEl.innerHTML = '';

        selectedGarmentIds.forEach((garmentTypeId) => {
            const garment = garmentMap.get(String(garmentTypeId));
            if (!garment) return;

            const existingGarment = (modalState.garments || []).find((entry) => String(entry.garmentTypeId) === String(garmentTypeId));
            const draft = existingDrafts.get(String(garmentTypeId));
            const savedMeasurements = getCustomerMeasurementForGarment(customerId, garmentTypeId);
            const baseMeasurements = garment.measurements || [];
            const defaultPackageId = garment.tailoringPackages?.[0]?.id || '';
            const selectedPackageId = draft?.tailoringPackageId || existingGarment?.tailoring?.packageId || defaultPackageId;
            const selectedDesignNotes = draft?.designNotes
                || existingGarment?.designNotes
                || existingGarment?.designNote
                || [];
            const existingDesignImages = draft?.existingDesignImages || existingGarment?.existingDesignImages || [];
            const draftDesignFiles = draft?.designFiles || existingGarment?.designFiles || [];

            const section = document.createElement('div');
            section.className = 'tp-garment-section';
            section.dataset.garmentTypeId = String(garmentTypeId);
            section.dataset.existingDesignImages = JSON.stringify(existingDesignImages);
            section._designFiles = draftDesignFiles;

            let measurementHtml = '<div class="tp-measurement-grid">';
            baseMeasurements.forEach((measurement) => {
                const existingMeasurement = draft?.measurements?.find((row) => row.type === measurement.title)
                    || existingGarment?.measurements?.find((row) => row.type === measurement.title)
                    || savedMeasurements.find((row) => row.type === measurement.title);
                measurementHtml += `
                    <div class="tp-form-group">
                        <label>${measurement.title}${measurement.unit ? ` (${measurement.unit})` : ''}</label>
                        <input
                            type="text"
                            class="tp-input"
                            data-measure-type="${measurement.title}"
                            data-measure-unit="${measurement.unit || ''}"
                            value="${String(existingMeasurement?.measurement || '')}">
                    </div>
                `;
            });
            measurementHtml += '</div>';

            let designNoteHtml = '<div class="tp-design-note-panel"><div class="tp-design-note-heading"><i class="fas fa-pencil-alt"></i> Design notes (optional)</div>';
            if ((garment.designNotes || []).length) {
                designNoteHtml += '<div class="tp-design-note-list">';
                (garment.designNotes || []).forEach((note, noteIndex) => {
                    const checkboxId = `garment-${garmentTypeId}-design-note-${noteIndex}`;
                    designNoteHtml += `
                        <label for="${checkboxId}" class="tp-design-note-item">
                            <input
                                id="${checkboxId}"
                                type="checkbox"
                                value="${String(note)}"
                                data-design-note="1"
                                ${selectedDesignNotes.includes(String(note)) ? 'checked' : ''}>
                            <span>${String(note)}</span>
                        </label>
                    `;
                });
                designNoteHtml += '</div>';
            } else {
                designNoteHtml += '<div class="tp-hint">No preset design notes for this garment type.</div>';
            }
            designNoteHtml += '</div>';

            const existingImageCount = Array.isArray(existingDesignImages) ? existingDesignImages.length : 0;
            const selectedFileCount = Array.isArray(draftDesignFiles) ? draftDesignFiles.length : 0;
            const designImageHtml = `
                <div class="tp-design-upload-panel">
                    <div class="tp-design-upload-heading"><i class="fas fa-cloud-upload-alt"></i> Upload design sample (optional)</div>
                    <input type="file" class="tp-input tp-design-upload-input" data-design-images accept="image/*" multiple>
                    ${existingImageCount ? `<div class="tp-existing-image-note">${existingImageCount} existing design sample${existingImageCount > 1 ? 's' : ''} will be kept.</div>` : ''}
                    ${selectedFileCount ? `<div class="tp-existing-image-note">${selectedFileCount} newly selected file${selectedFileCount > 1 ? 's are' : ' is'} queued.</div>` : ''}
                </div>
            `;

            let tailoringOptions = '';
            (garment.tailoringPackages || []).forEach((pkg) => {
                tailoringOptions += `
                    <option
                        value="${pkg.id}"
                        data-package-name="${pkg.name}"
                        data-package-amount="${pkg.amount}"
                        ${String(selectedPackageId) === String(pkg.id) ? 'selected' : ''}>
                        ${pkg.name} - NPR ${money(pkg.amount)}
                    </option>
                `;
            });

            section.innerHTML = `
                <div class="tp-garment-head">
                    <div>
                        <div class="tp-garment-title">${garment.title}</div>
                        <div class="tp-garment-summary">Measurements and tailoring for this garment.</div>
                    </div>
                    <div class="tp-form-group" style="margin:0;min-width:140px;">
                        <label>Garment Qty</label>
                        <input type="number" class="tp-input" data-garment-qty min="1" step="1" value="${Number(draft?.quantity || existingGarment?.quantity || 1)}">
                    </div>
                </div>
                ${measurementHtml}
                <div class="tp-form-group" style="margin-top:12px;">
                    <label>Tailoring Package</label>
                    <select class="tp-input" data-tailoring-for="${garmentTypeId}">
                        ${tailoringOptions}
                    </select>
                </div>
                ${designNoteHtml}
                ${designImageHtml}
            `;

            measurementFieldsEl.appendChild(section);
        });
    }

    function updateCustomFabricSourceUI() {
        const pending = getCurrentPendingCustom();
        const source = selectedCustomFabricSource();
        customOwnFabricFields.style.display = source === 'own' ? '' : 'none';
        customStockFabricFields.style.display = source === 'stock' ? '' : 'none';

        if (!pending) {
            customStockFabricHint.textContent = '';
            return;
        }

        const stockUnitPrice = Number(pending.unitPrice || resolveDefaultPrice(pending.productId) || 0);
        const unitLabel = pending.unitLabel || 'meter';

        if (source === 'stock') {
            modalProductPrice.textContent = formatFabricPrice(stockUnitPrice, unitLabel);
            modalProductQuantity.textContent = money(Number(customStockFabricQty.value || 0));
            const product = productMap.get(String(pending.productId || ''));
            customStockFabricHint.textContent = product
                ? `Available: ${money(product.availableQty)} ${product.unitLabel || ''}`.trim()
                : 'Selected stock fabric is unavailable.';
        } else {
            modalProductPrice.textContent = formatFabricPrice(stockUnitPrice, unitLabel);
            modalProductQuantity.textContent = money(Number(customOwnFabricQty?.value || 0));
            customStockFabricHint.textContent = '';
        }
    }

    function openModal() {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function resetCustomModalState() {
        modal.dataset.itemState = JSON.stringify({ garments: [] });
        customFabricOwnRadio.checked = true;
        customFabricStockRadio.checked = false;
        if (customOwnFabricQty) customOwnFabricQty.value = '1.00';
        customStockFabricQty.value = '1.00';
        Array.from(garmentTypeCheckboxes.querySelectorAll('input[type="checkbox"]')).forEach((input) => {
            input.checked = false;
        });
        measurementFieldsEl.innerHTML = '<div class="tp-hint">Select at least one garment type.</div>';
    }

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        pendingCustomQueue = [];
        pendingCustomIndex = 0;
        editingCustomIndex = -1;
        saveMeasurementBtn.textContent = 'Add Custom Item';
        resetCustomModalState();
    }

    function loadPendingCustomIntoModal(existingItem = null) {
        const pending = getCurrentPendingCustom();
        if (!pending) {
            closeModal();
            renderBill();
            productSelect.value = '';
            qtyInput.value = '1';
            rebuildProductSelect(categorySelect.value);
            return;
        }

        modalProductName.textContent = pending.name;
        if (changeModalProductBtn && pending.productId) {
            changeModalProductBtn.style.display = 'inline-flex';
        } else if (changeModalProductBtn) {
            changeModalProductBtn.style.display = 'none';
        }
        if (modalProductSelect) {
            buildModalProductSelect();
            modalProductSelect.value = String(pending.productId || '');
            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                window.jQuery(modalProductSelect).val(String(pending.productId || '')).trigger('change.select2');
            }
        }
        modalProductPrice.textContent = formatFabricPrice(pending.unitPrice, pending.unitLabel || 'meter');
        modalProductQuantity.textContent = money(pending.qty);
        if (customOwnFabricQty) {
            customOwnFabricQty.value = money(pending.qty);
        }
        if (modalProductCounter) {
            modalProductCounter.textContent = `${pendingCustomIndex + 1}/${pendingCustomQueue.length}`;
        }
        const itemState = {
            garments: existingItem?.garments || [],
        };
        modal.dataset.itemState = JSON.stringify(itemState);

        const selectedIds = itemState.garments.length
            ? itemState.garments.map((garment) => String(garment.garmentTypeId))
            : (garmentTypes[0] ? [String(garmentTypes[0].id)] : []);
        Array.from(garmentTypeCheckboxes.querySelectorAll('input[type="checkbox"]')).forEach((input) => {
            input.checked = selectedIds.includes(String(input.value || ''));
        });

        const fabricSource = String(existingItem?.fabricSource || 'own');
        customFabricOwnRadio.checked = fabricSource !== 'stock';
        customFabricStockRadio.checked = fabricSource === 'stock';
        customStockFabricQty.value = money(existingItem?.fabricQuantity || pending.qty || 1);
        saveMeasurementBtn.textContent = editingCustomIndex > -1
            ? 'Update Custom Item'
            : (pendingCustomIndex + 1 < pendingCustomQueue.length
                ? `Save & Continue (${pendingCustomIndex + 1}/${pendingCustomQueue.length})`
                : `Save Custom Item (${pendingCustomIndex + 1}/${pendingCustomQueue.length})`);

        renderGarmentSections();
        updateCustomFabricSourceUI();
        openModal();
    }

    function openCustomMeasurementModal(queue, existingItem = null, itemIndex = -1) {
        pendingCustomQueue = Array.isArray(queue) ? queue : [queue];
        pendingCustomIndex = 0;
        editingCustomIndex = itemIndex;
        resetCustomModalState();
        loadPendingCustomIntoModal(existingItem);
    }

    function getTailoringTotalForItem(item) {
        return (item.garments || []).reduce((sum, garment) => {
            return sum + (Number(garment.quantity || 0) * Number(garment.tailoring?.amount || 0));
        }, 0);
    }

    function renderBill() {
        billItemsEl.innerHTML = '';

        let subtotalFabric = 0;
        let subtotalCustom = 0;
        let totalTailoring = 0;
        const tailoringLines = [];

        billItems.forEach((item) => {
            const lineTotal = Number(item.qty || 0) * Number(item.unitPrice || 0);
            if (item.category === 'fabric' || item.category === 'readymade' || item.category === 'accessories') {
                subtotalFabric += lineTotal;
            }
            if (item.category === 'custom') {
                subtotalCustom += lineTotal;
                (item.garments || []).forEach((garment) => {
                    const garmentTailoring = Number(garment.quantity || 0) * Number(garment.tailoring?.amount || 0);
                    totalTailoring += garmentTailoring;
                    tailoringLines.push({
                        label: `${item.name} - ${garment.title} - ${garment.tailoring?.package || 'Tailoring'} x ${money(garment.quantity)}`,
                        amount: garmentTailoring,
                    });
                });
            }

            const [label, chipClass] = getCategoryLabel(item.category);
            const row = document.createElement('div');
            row.className = 'bill-item';

            let customSummary = '';
            if (item.category === 'custom' && item.garments?.length) {
                customSummary = item.garments.map((garment) => {
                    return `
                        <div class="stitching-detail">
                            <div>
                                <strong>${garment.title}</strong> x ${money(garment.quantity)}
                                <div class="item-sub item-sub-amount">${garment.tailoring?.package || 'Tailoring'} - NPR ${money(garment.tailoring?.amount || 0)} each</div>
                            </div>
                            <div><strong>NPR ${money(Number(garment.quantity || 0) * Number(garment.tailoring?.amount || 0))}</strong></div>
                        </div>
                    `;
                }).join('');
            }

            const sizeSummary = item.category === 'readymade' && item.size
                ? `<div class="item-sub"><i class="fas fa-ruler"></i> Size: ${item.size}</div>`
                : '';

            row.innerHTML = `
                <div class="item-details">
                    <div class="item-main">
                        <div style="position:relative;">
                            ${item.name}
                            <span class="${chipClass}">${label}</span>
                            <div class="item-sub">${money(item.qty)} ${item.unitLabel || ''} × NPR ${money(item.unitPrice)}</div>
                            ${sizeSummary}
                        </div>
                        <div class="product-actions">
                            <button type="button" class="btn-secondary" data-action="edit" data-id="${item.id}">Edit</button>
                            <button type="button" class="btn-danger" data-action="remove" data-id="${item.id}">Remove</button>
                        </div>
                    </div>
                    <div class="item-price"><strong>NPR ${money(lineTotal)}</strong></div>
                </div>
                ${customSummary}
            `;
            billItemsEl.appendChild(row);
        });

        if (!tailoringLines.length) {
            tailoringBreakdownEl.style.display = 'none';
            tailoringBreakdownEl.innerHTML = '';
        } else {
            tailoringBreakdownEl.style.display = '';
            let html = '<h5><i class="fas fa-cut"></i> Tailoring Charges Breakdown</h5>';
            tailoringLines.forEach((line) => {
                html += `<div class="tailoring-item"><span>${line.label}</span><span class="tailoring-item-amount">NPR ${money(line.amount)}</span></div>`;
            });
            html += `<div class="tailoring-item tailoring-total"><span>Total Tailoring Charges</span><span class="tailoring-item-amount">NPR ${money(totalTailoring)}</span></div>`;
            tailoringBreakdownEl.innerHTML = html;
        }

        let discountAmount = 0;
        if (discount.type === 'flat') discountAmount = Math.min(Number(discount.value || 0), subtotalFabric + subtotalCustom);
        if (discount.type === 'percent') discountAmount = (subtotalFabric + subtotalCustom) * (Number(discount.value || 0) / 100);

        const taxableSubtotal = Math.max((subtotalFabric + subtotalCustom) - discountAmount, 0);
        const taxableTotal = taxableSubtotal + totalTailoring;
        const vatAmount = vatEnabled ? taxableTotal * 0.13 : 0;
        const grandTotal = taxableTotal + vatAmount;
        const subtotal = subtotalFabric + subtotalCustom + totalTailoring;

        subtotalEl.textContent = `NPR ${money(subtotal)}`;
        // subtotalFabricEl.textContent = `NPR ${money(subtotalFabric)}`;
        // subtotalCustomEl.textContent = `NPR ${money(subtotalCustom)}`;
        tailoringTotalEl.textContent = `NPR ${money(totalTailoring)}`;
        discountTotalEl.textContent = `NPR ${money(discountAmount)}`;
        vatAmountEl.textContent = `NPR ${money(vatAmount)}`;
        grandTotalEl.textContent = `NPR ${money(grandTotal)}`;
        vatRow.style.display = vatEnabled ? '' : 'none';

        updatePaymentSummary(discountAmount);
        buildHiddenInputs();
    }

    function makeHidden(name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value ?? '';
        return input;
    }

    function appendFileInput(name, files) {
        if (!files?.length) return;
        const input = document.createElement('input');
        input.type = 'file';
        input.name = name;
        input.multiple = true;
        input.style.display = 'none';

        try {
            const transfer = new DataTransfer();
            files.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
            hiddenInputsHost.appendChild(input);
        } catch (error) {}
    }

    function buildHiddenInputs() {
        hiddenInputsHost.innerHTML = '';
        hiddenInputsHost.appendChild(makeHidden('vat_enabled', vatEnabled ? '1' : '0'));

        billItems.forEach((item, idx) => {
            hiddenInputsHost.appendChild(makeHidden(`items[${idx}][item_category]`, item.category));
            hiddenInputsHost.appendChild(makeHidden(`items[${idx}][quantity]`, String(item.qty || 0)));
            hiddenInputsHost.appendChild(makeHidden(`items[${idx}][unit_price]`, String(item.unitPrice || 0)));

            if (item.category !== 'custom') {
                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][product_id]`, String(item.productId || '')));
                if (item.category === 'readymade' && item.size) {
                    hiddenInputsHost.appendChild(makeHidden(`items[${idx}][size]`, String(item.size)));
                }
                return;
            }

            hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][fabric_source]`, String(item.fabricSource || 'own')));
            hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][fabric_product_id]`, String(item.productId || '')));
            hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][fabric_quantity]`, String(item.fabricQuantity || item.qty || 0)));

            (item.garments || []).forEach((garment, garmentIndex) => {
                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][garments][${garmentIndex}][garment_type_id]`, String(garment.garmentTypeId || '')));
                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][garments][${garmentIndex}][garment_title]`, String(garment.title || '')));
                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][garments][${garmentIndex}][quantity]`, String(garment.quantity || 1)));
                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][garments][${garmentIndex}][tailoring_package_id]`, String(garment.tailoring?.packageId || '')));
                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][garments][${garmentIndex}][tailoring_package]`, String(garment.tailoring?.package || '')));
                hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][garments][${garmentIndex}][tailoring_amount]`, String(garment.tailoring?.amount || 0)));
                (garment.designNotes || []).forEach((note, noteIndex) => {
                    hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][garments][${garmentIndex}][design_note][${noteIndex}]`, String(note || '')));
                });
                (garment.existingDesignImages || []).forEach((path, pathIndex) => {
                    hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][garments][${garmentIndex}][existing_design_images][${pathIndex}]`, String(path || '')));
                });

                (garment.measurements || []).forEach((measurement, measurementIndex) => {
                    hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][garments][${garmentIndex}][measurements][${measurementIndex}][type]`, String(measurement.type || '')));
                    hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][garments][${garmentIndex}][measurements][${measurementIndex}][measurement]`, String(measurement.measurement || '')));
                    hiddenInputsHost.appendChild(makeHidden(`items[${idx}][custom][garments][${garmentIndex}][measurements][${measurementIndex}][unit]`, String(measurement.unit || '')));
                });

                appendFileInput(`items[${idx}][custom][garments][${garmentIndex}][design_images][]`, garment.designFiles || []);
            });
        });
    }

    function validateHasItems() {
        if (billItems.length > 0) return true;
        alert('Add at least one item to the bill before saving or printing.');
        return false;
    }

    let editingQtyItemIndex = -1;

    function openEditQtyModal(index) {
        const item = billItems[index];
        if (!item) return;
        editingQtyItemIndex = index;
        editQtyLabel.textContent = `Quantity for ${item.name}`;
        editQtyInput.step = (item.category === 'fabric' || item.category === 'custom') ? '0.01' : '1';
        editQtyInput.value = String(item.fabricQuantity || item.qty);
        editQtyModal.style.display = 'flex';
    }

    function buildCustomEditQueueItem(item) {
        const product = productMap.get(String(item?.productId || ''));
        const qty = Number(item?.fabricQuantity || item?.qty || 1);

        return {
            productId: item?.productId || '',
            qty,
            unitPrice: Number(item?.baseUnitPrice ?? item?.unitPrice ?? resolveDefaultPrice(item?.productId)),
            name: item?.name || (product ? `${product.name} (${product.code})` : 'Custom Product'),
            unitLabel: item?.unitLabel || product?.unitLabel || '',
        };
    }

    document.getElementById('cancelEditQty')?.addEventListener('click', () => {
        editQtyModal.style.display = 'none';
        editingQtyItemIndex = -1;
    });

    document.getElementById('confirmEditQty')?.addEventListener('click', () => {
        const qty = Number(editQtyInput.value || 0);
        if (qty <= 0) {
            alert('Quantity must be greater than zero.');
            return;
        }
        if (editingQtyItemIndex > -1 && billItems[editingQtyItemIndex]) {
            const target = billItems[editingQtyItemIndex];
            target.qty = qty;
            if (target.fabricQuantity !== undefined) {
                target.fabricQuantity = qty;
            }
            editQtyModal.style.display = 'none';
            editingQtyItemIndex = -1;
            renderBill();
        }
    });

    function updateCustomQueueFromSelection() {
        const selectedIds = selectedCustomProductIds.slice();
        const sharedQty = 1;
        return selectedIds.map((productId) => {
            const product = productMap.get(String(productId));
            return {
                productId,
                qty: sharedQty,
                unitPrice: resolveDefaultPrice(productId),
                name: product ? `${product.name} (${product.code})` : 'Custom Product',
                unitLabel: product?.unitLabel || '',
            };
        });
    }

    billItemsEl.addEventListener('click', (event) => {
        const btn = event.target.closest('button[data-action]');
        if (!btn) return;

        const id = String(btn.dataset.id || '');
        const index = billItems.findIndex((item) => String(item.id) === id);
        if (index === -1) return;

        if (btn.dataset.action === 'remove') {
            billItems.splice(index, 1);
            renderBill();
            return;
        }

        const item = billItems[index];
        if (item?.category === 'custom') {
            openCustomMeasurementModal([buildCustomEditQueueItem(item)], item, index);
            return;
        }

        openEditQtyModal(index);
    });

    addBtn.addEventListener('click', () => {
        const category = categorySelect.value;
        const normalizedPhone = normalizePhone(customerPhoneInput.value);
        if (!customerSelect.value && normalizedPhone.length < 7) {
            alert('Check/select customer first.');
            customerPhoneInput.focus();
            return;
        }

        if (category === 'custom') {
            const queue = updateCustomQueueFromSelection().filter((item) => item.productId);
            if (!queue.length) {
                alert('Select at least one custom fabric.');
                return;
            }

            openCustomMeasurementModal(queue);
            return;
        }

        const productId = productSelect.value;
        const qty = Number(qtyInput.value || 0);
        if (!productId) {
            alert('Select a product.');
            return;
        }
        if (qty <= 0) {
            alert('Quantity must be greater than zero.');
            return;
        }

        const product = productMap.get(String(productId));
        if (product && qty > Number(product.availableQty || 0)) {
            alert(`Only ${money(product.availableQty)} ${product.unitLabel || ''} is available in current outlet.`);
            return;
        }

        if (category === 'readymade' && !selectedReadymadeSize()) {
            alert('Select a size for ready-made product.');
            return;
        }

        billItems.push({
            id: `${Date.now()}-${Math.random()}`,
            category,
            productId,
            name: product ? `${product.name} (${product.code})` : 'Product',
            unitLabel: product?.unitLabel || '',
            qty,
            unitPrice: resolveDefaultPrice(productId),
            size: category === 'readymade' ? selectedReadymadeSize() : null,
        });
        resetOrderEntryFields();
        renderBill();
    });

    garmentTypeCheckboxes.addEventListener('change', (event) => {
        if (event.target instanceof HTMLInputElement && event.target.type === 'checkbox') {
            renderGarmentSections();
        }
    });
    customFabricOwnRadio?.addEventListener('change', updateCustomFabricSourceUI);
    customFabricStockRadio?.addEventListener('change', updateCustomFabricSourceUI);
    customOwnFabricQty?.addEventListener('input', updateCustomFabricSourceUI);
    customStockFabricQty?.addEventListener('input', updateCustomFabricSourceUI);

    saveMeasurementBtn.addEventListener('click', () => {
        const pending = getCurrentPendingCustom();
        if (!pending) return;

        if (modalProductSelect?.value && String(modalProductSelect.value) !== String(pending.productId || '')) {
            applyPendingProductChange(modalProductSelect.value);
        }

        const selectedGarmentIds = getSelectedGarmentIds();
        if (!selectedGarmentIds.length) {
            alert('Select at least one garment type.');
            return;
        }

        const fabricSource = selectedCustomFabricSource();
        const fabricQuantity = fabricSource === 'stock'
            ? Number(customStockFabricQty.value || 0)
            : Number(customOwnFabricQty?.value || 0);
        if (fabricQuantity <= 0) {
            alert('Fabric quantity must be greater than zero.');
            return;
        }

        const product = productMap.get(String(pending.productId || ''));
        if (fabricSource === 'stock' && fabricQuantity > Number(product?.availableQty || 0)) {
            alert(`Only ${money(product?.availableQty || 0)} ${product?.unitLabel || ''} is available in stock.`);
            return;
        }

        const garments = [];
        let hasInvalidMeasurements = false;
        Array.from(measurementFieldsEl.querySelectorAll('.tp-garment-section')).forEach((section) => {
            const garmentTypeId = String(section.dataset.garmentTypeId || '');
            const garmentType = garmentMap.get(garmentTypeId);
            const garmentQuantity = Number(section.querySelector('input[data-garment-qty]')?.value || 0);
            const tailoringSelect = section.querySelector('select[data-tailoring-for]');
            const tailoringOption = tailoringSelect?.selectedOptions?.[0] || null;
            const measurements = Array.from(section.querySelectorAll('input[data-measure-type]')).map((input) => ({
                type: String(input.dataset.measureType || ''),
                measurement: String(input.value || '').trim(),
                unit: String(input.dataset.measureUnit || ''),
            }));

            if (garmentQuantity < 1) {
                hasInvalidMeasurements = true;
                return;
            }

            if (!tailoringOption || !tailoringOption.value) {
                hasInvalidMeasurements = true;
                return;
            }

            if (measurements.some((row) => !row.measurement)) {
                hasInvalidMeasurements = true;
                return;
            }

            garments.push({
                garmentTypeId,
                title: garmentType?.title || tailoringOption.dataset.packageName || 'Garment',
                quantity: garmentQuantity,
                measurements,
                designNotes: Array.from(section.querySelectorAll('input[data-design-note]:checked')).map((input) => String(input.value || '')),
                designFiles: (() => {
                    const selectedFiles = Array.from(section.querySelector('input[data-design-images]')?.files || []);
                    return selectedFiles.length ? selectedFiles : (section._designFiles || []);
                })(),
                existingDesignImages: (() => {
                    try {
                        return JSON.parse(section.dataset.existingDesignImages || '[]');
                    } catch (error) {
                        return [];
                    }
                })(),
                tailoring: {
                    packageId: Number(tailoringOption.value || 0),
                    package: tailoringOption.dataset.packageName || 'Tailoring',
                    amount: Number(tailoringOption.dataset.packageAmount || 0),
                },
            });
        });

        if (hasInvalidMeasurements || !garments.length) {
            alert('Complete garment quantities, tailoring package, and all measurements.');
            return;
        }

        const stockUnitPrice = Number(pending.unitPrice || resolveDefaultPrice(pending.productId) || 0);
        const payload = {
            id: editingCustomIndex > -1 ? billItems[editingCustomIndex]?.id : `${Date.now()}-${Math.random()}`,
            category: 'custom',
            productId: pending.productId,
            name: pending.name,
            unitLabel: pending.unitLabel,
            qty: fabricQuantity,
            unitPrice: fabricSource === 'stock' ? stockUnitPrice : 0,
            baseUnitPrice: stockUnitPrice,
            fabricSource,
            fabricQuantity,
            garments,
        };

        if (editingCustomIndex > -1 && billItems[editingCustomIndex]) {
            billItems[editingCustomIndex] = payload;
            closeModal();
            renderBill();
            return;
        }

        billItems.push(payload);
        pendingCustomIndex += 1;
        if (pendingCustomIndex >= pendingCustomQueue.length) {
            closeModal();
            resetOrderEntryFields();
            renderBill();
            return;
        }

        resetCustomModalState();
        loadPendingCustomIntoModal();
    });

    closeModalBtn.addEventListener('click', closeModal);
    cancelMeasurementBtn.addEventListener('click', closeModal);
    changeModalProductBtn?.addEventListener('click', () => {
        if (!modalProductSelectWrap || !modalProductSelect) {
            return;
        }

        if (!shouldShow) {
            return;
        }

        window.requestAnimationFrame(() => {
            if (window.jQuery && window.jQuery.fn && window.jQuery(modalProductSelect).hasClass('select2-hidden-accessible')) {
                window.jQuery(modalProductSelect).select2('open');
                return;
            }

            modalProductSelect.focus();
        });
    });
    bindSelect2Change(modalProductSelect, () => {
        applyPendingProductChange(modalProductSelect?.value || '');
    });

    discountTypeEl.addEventListener('change', () => {
        discountValueEl.disabled = discountTypeEl.value === 'none';
        if (discountTypeEl.value === 'none') discountValueEl.value = '';
    });

    applyDiscountBtn.addEventListener('click', () => {
        const type = discountTypeEl.value;
        const value = Number(discountValueEl.value || 0);
        discount = { type, value: type === 'none' ? 0 : value };
        if (type === 'none') discountDisplayEl.textContent = '';
        const subtotal = billItems.reduce((sum, item) => sum + (Number(item.qty || 0) * Number(item.unitPrice || 0)), 0);
        const maxDiscount = subtotal;
        
        if (type === 'flat') {
            if (value > 0 && value <= maxDiscount) {
            discountDisplayEl.textContent = `Flat discount: NPR ${money(value)}`;
            } else if (value > maxDiscount) {
            discountDisplayEl.textContent = `⚠️ Discount is not applicable (Max: NPR ${money(maxDiscount)})`;
            } else {
            discountDisplayEl.textContent = '';
            }
        }
        if (type === 'percent') {
            const discountAmount = subtotal * (value / 100);
            if (value > 0 && value <= 100 && discountAmount > 0) {
            discountDisplayEl.textContent = `Percent discount: ${money(value)}%`;
            } else if (value > 100) {
            discountDisplayEl.textContent = `⚠️ Discount is not applicable`;
            } else if (value > 0 && discountAmount <= 0) {
            discountDisplayEl.textContent = `⚠️ Discount is not applicable`;
            } else {
            discountDisplayEl.textContent = '';
            }
        }
        renderBill();
    });

    vatToggle.addEventListener('change', () => {
        vatEnabled = vatToggle.checked;
        billTypeEl.textContent = vatEnabled ? 'VAT Bill' : 'Estimated Bill';
        renderBill();
    });

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
        if (printBillInput) printBillInput.value = '1';
        orderForm?.requestSubmit();
    });

    saveOrderBtn?.addEventListener('click', () => {
        if (printBillInput) printBillInput.value = '0';
    });

    orderForm?.addEventListener('submit', async (event) => {
        if (customerSubmitInProgress) {
            return;
        }

        if (!validateHasItems()) {
            event.preventDefault();
            return;
        }

        event.preventDefault();

        try {
            const customer = await resolveOrCreateCustomer({ createIfMissing: true });
            if (!customer?.id) {
                customerNameInput.focus();
                return;
            }
        } catch (error) {
            alert(error.message || 'Unable to attach customer.');
            return;
        }

        customerSubmitInProgress = true;
        HTMLFormElement.prototype.submit.call(orderForm);
    });

    async function handleCustomerLookup() {
        const normalizedPhone = normalizePhone(customerPhoneInput.value);
        if (normalizedPhone.length < 7) {
            alert('Enter a valid phone number before checking.');
            customerPhoneInput.focus();
            return;
        }

        checkCustomerBtn.disabled = true;
        try {
            const customer = await resolveOrCreateCustomer({ createIfMissing: false });
            if (!customer) {
                customerNameInput.focus();
            }
        } catch (error) {
            alert(error.message || 'Unable to check customer.');
        } finally {
            checkCustomerBtn.disabled = false;
        }
    }

    checkCustomerBtn.addEventListener('click', handleCustomerLookup);
    customerPhoneInput.addEventListener('input', () => {
        customerSelect.value = '';
        setCustomerCheckMessage('');
        hideCustomerDetails();
        updateCustomerDisplay();
    });
    customerPhoneInput.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        handleCustomerLookup();
    });

    bindSelect2Change(categorySelect, () => rebuildProductSelect(categorySelect.value));
    bindSelect2Change(productSelect, () => {
        if (categorySelect.value !== 'custom') {
            updateProductSelectionUI();
        }
    });

    setupOrderSelect2(productSelect, 'Select or scan barcode');
    fillGarmentTypeOptions();
    customerDirectory.forEach(upsertCustomerIndex);
    if (customerSelect.value) {
        const customer = customersById.get(String(customerSelect.value));
        if (customer?.phone) {
            customerPhoneInput.value = customer.phone;
        }
        if (customer?.name) {
            customerNameInput.value = customer.name;
        }
        if (customer?.customerType) {
            customerTypeInput.value = customer.customerType;
        }
    }
    customerNameInput?.addEventListener('input', updateCustomerDisplay);
    customerTypeInput?.addEventListener('change', updateCustomerDisplay);

    vatToggle.checked = vatEnabled;
    billTypeEl.textContent = vatEnabled ? 'VAT Bill' : 'Estimated Bill';
    discountTypeEl.value = discount.type || 'none';
    discountValueEl.value = discount.type === 'none' ? '' : Number(discount.value || 0);
    discountValueEl.disabled = discount.type === 'none';
    if (discount.type === 'flat') discountDisplayEl.textContent = `Flat discount: NPR ${money(discount.value)}`;
    if (discount.type === 'percent') discountDisplayEl.textContent = `Percent discount: ${money(discount.value)}%`;
    updateCustomerDisplay();
    rebuildProductSelect(categorySelect.value);
    renderBill();
})();
</script>
@endsection
