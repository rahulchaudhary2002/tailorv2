@extends('layouts.app')

@section('title', 'Create Order')

@section('content')
@php
// Build lightweight payloads for JS
$productPayload = $products->map(function ($product) use ($productDefaultPrices, $productAvailableQty) {
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
        'defaultPrice' => array_key_exists($product->id, $productDefaultPrices)
            ? (float) $productDefaultPrices[$product->id]
            : null,
        'variants' => [],
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
        'customer_type' => $customer->customer_type,
    ];
})->values();
@endphp

<form id="orderForm" action="{{ route('order.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- Hidden container where bill items are converted to items[i][...] --}}
    <div id="itemsHiddenInputs"></div>
    <input type="hidden" id="print_bill" name="print_bill" value="{{ old('print_bill', '0') }}">
    <input type="hidden" id="ordered_at" name="ordered_at" value="{{ old('ordered_at', now()->format('Y-m-d H:i:s')) }}">
    <input type="hidden" id="delivery_due_at" name="delivery_due_at" value="{{ old('delivery_due_at', now()->addDays(7)->format('Y-m-d H:i:s')) }}">
    <input type="hidden" id="status" name="status" value="{{ old('status', \App\Models\Order::STATUS_CONFIRMED) }}">
    <input type="hidden" id="worker_id" name="worker_id" value="{{ old('worker_id') }}">
    <input type="hidden" id="worker_deadline_at" name="worker_deadline_at" value="{{ old('worker_deadline_at') }}">
    <input type="hidden" id="notes" name="notes" value="{{ old('notes') }}">
    <input type="hidden" id="payment_status" name="payment_status" value="{{ old('payment_status', \App\Models\Order::PAYMENT_STATUS_UNPAID) }}">
    <input type="hidden" id="payment_method" name="payment_method" value="{{ old('payment_method') }}">
    <input type="hidden" id="advance_payment_amount" name="advance_payment_amount" value="{{ old('advance_payment_amount', '0') }}">
    <input type="hidden" id="discount_amount" name="discount_amount" value="{{ old('discount_amount', '0') }}">

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
            <h2><i class="fas fa-desktop"></i> Enhanced Live Billing Interface</h2>
            <div class="instructions">
                <i class="fas fa-info-circle"></i> <strong>New Features:</strong>
                1. Select "Custom" product category to trigger measurement popup.
                2. Tailoring charges appear separately in bill items (not included in product price).
            </div>

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
                            value="{{ old('customer_id', $selectedCustomerId ?? '') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label>Product Category *</label>
                    <select id="productCategory" class="tp-input">
                        <option value="fabric">Fabric</option>
                        <option value="readymade">Ready-Made</option>
                        <option value="custom">Custom</option>
                    </select>
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

                <div class="form-group">
                    <label>Quantity</label>
                    <input id="quantity" type="number" min="1" step="1" class="tp-input" value="1">
                    <small class="tp-hint" id="qtyUnitHint">-</small>
                </div>

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
                        <span>Subtotal (Fabric & Ready-made):</span>
                        <span id="subtotalFabric">0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Subtotal (Custom Products):</span>
                        <span id="subtotalCustom">0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Discount:</span>
                        <span id="discountTotal">0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Tailoring Charges:</span>
                        <span id="tailoringTotal">0.00</span>
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
                        <i class="fas fa-file-export"></i> Save Order
                    </button>
                    <button type="button" id="clearBill" class="btn-danger">
                        <i class="fas fa-trash-alt"></i> Clear Bill
                    </button>
                </div>
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
:root{--primary:#1a365d;--secondary:#2d4a7c;--accent:#c9a96e;--light:#f8f9fa;--dark:#343a40;--success:#28a745;--danger:#dc3545;--radius:8px;--shadow:0 4px 12px rgba(0,0,0,.1);}
.tp-container{max-width:1400px;margin:0 auto;padding:20px;}
.tp-alert{margin-top:16px;padding:12px 14px;border-radius:var(--radius);}
.tp-alert-danger{background:#ffe8ea;color:#7a0b18;border:1px solid #ffccd1;}
.tp-alert-success{background:#e7f7ed;color:#0d5a2b;border:1px solid #bfe8cf;}
.demo-section{background:#fff;padding:25px;border-radius:var(--radius);box-shadow:var(--shadow);}
.demo-layout{display:grid;grid-template-columns:1fr 1fr;gap:30px;margin-top:20px;}
@media(max-width:1024px){.demo-layout{grid-template-columns:1fr;}}
.order-entry{padding:20px;background:#f8f9fa;border-radius:var(--radius);border:1px solid #ddd;}
.live-bill{padding:20px;background:#fff;border-radius:var(--radius);border:1px solid #ddd;box-shadow:0 0 15px rgba(0,0,0,.05);}
h2,h3,h4{color:var(--primary);margin-bottom:15px;padding-bottom:8px;border-bottom:2px solid #eee;}
h2 i,h3 i,h4 i{margin-right:10px;color:var(--accent);}
.form-group{margin-bottom:15px;}
label{display:block;margin-bottom:5px;font-weight:600;color:var(--secondary);}
.tp-input{width:100%;padding:10px 15px;border:1px solid #ccc;border-radius:var(--radius);font-size:1rem;}
.demo-section .select2-container--default .select2-selection--single{
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
.demo-section .select2-container--default .select2-selection--single .select2-selection__rendered{
    color:inherit;
    line-height:43px;
    padding-left:14px;
    padding-right:30px;
    margin-left:0;
}
.demo-section .select2-container--default .select2-selection--single .select2-selection__placeholder{
    color:#6a7785;
}
.demo-section .select2-container--default .select2-selection--single .select2-selection__arrow{
    height:100%;
    right:10px;
}
.demo-section .select2-container--default .select2-selection--single .select2-selection__clear{
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
.demo-section .select2-container--default .select2-selection--single .select2-selection__clear:hover{
    color:#dc3545;
}
.demo-section .select2-container--default.select2-container--focus .select2-selection--single,
.demo-section .select2-container--default.select2-container--open .select2-selection--single{
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
.bill-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:10px;border-bottom:2px solid var(--accent);}
.bill-title{font-size:1.5rem;font-weight:700;color:var(--primary);}
.toggle-switch{display:flex;align-items:center;gap:10px;}
.switch{position:relative;display:inline-block;width:60px;height:30px;}
.switch input{opacity:0;width:0;height:0;}
.slider{position:absolute;cursor:pointer;inset:0;background:#ccc;transition:.4s;border-radius:34px;}
.slider:before{position:absolute;content:"";height:22px;width:22px;left:4px;bottom:4px;background:#fff;transition:.4s;border-radius:50%;}
.switch input:checked+.slider{background:var(--success);}
.switch input:checked+.slider:before{transform:translateX(30px);}
.customer-display{background:#f8f9fa;padding:15px;border-radius:var(--radius);margin-bottom:20px;}
.bill-items{margin-bottom:20px;max-height:300px;overflow-y:auto;}
.bill-item{padding:10px 0;border-bottom:1px dashed #eee;}
.item-details{display:flex;justify-content:space-between;margin-bottom:5px;gap:12px;}
.item-sub{font-size:.9rem;color:#666;margin-top:5px;}
.stitching-detail{background:#f9f0ff;padding:8px 12px;border-radius:4px;margin-top:5px;font-size:.9rem;border-left:3px solid #7b1fa2;}
.product-actions{display:flex;gap:10px;margin-top:8px;}
.product-actions button{padding:4px 10px;font-size:.8rem;}
.product-category{display:inline-block;padding:4px 10px;border-radius:20px;font-size:.8rem;font-weight:600;margin-left:10px;}
.fabric-cat{background:#e3f2fd;color:#1565c0;}
.ready-made-cat{background:#e8f5e9;color:#2e7d32;}
.custom-cat{background:#f3e5f5;color:#7b1fa2;}
.tailoring-breakdown{background:#f9f0ff;padding:12px;border-radius:var(--radius);margin-bottom:15px;}
.tailoring-breakdown h5{margin:0 0 8px;color:#7b1fa2;}
.tailoring-item{display:flex;justify-content:space-between;padding:4px 0;}
.tailoring-total{font-weight:700;border-top:1px solid #ddd;margin-top:8px;padding-top:8px;}
.discount-section{background:#f8f9fa;padding:15px;border-radius:var(--radius);margin-top:15px;}
.discount-row{display:flex;gap:10px;margin-top:10px;}
.discount-row .tp-input{flex:1;}
.discount-display{margin-top:10px;font-weight:700;color:var(--success);}
.bill-summary{border-top:2px solid var(--primary);padding-top:15px;margin-top:15px;}
.summary-row{display:flex;justify-content:space-between;padding:8px 0;}
.total-row{font-weight:700;font-size:1.2rem;color:var(--primary);border-top:2px solid #ddd;margin-top:10px;padding-top:10px;}
.bill-actions{margin-top:20px;display:flex;gap:10px;}
.bill-actions>*{flex:1;}
.tp-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1200;display:flex;align-items:center;justify-content:center;padding:20px;}
.tp-modal{width:100%;max-width:860px;background:#fff;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.30);overflow:hidden;}
.tp-modal-header{background:var(--primary);color:#fff;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;}
.tp-modal-header h3{margin:0;color:#fff;border:0;padding:0;}
.tp-modal-close{background:transparent;border:0;color:#fff;font-size:24px;cursor:pointer;padding:0;width:30px;height:30px;display:flex;align-items:center;justify-content:center;}
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
@media print{body *{visibility:hidden !important;}#billPrintArea,#billPrintArea *{visibility:visible !important;}#billPrintArea{position:absolute !important;top:0;left:0;width:100% !important;max-width:100% !important;margin:0 !important;padding:0 !important;border:0 !important;box-shadow:none !important;background:#fff !important;}#billPrintArea .bill-actions,#billPrintArea .toggle-switch,#billPrintArea .discount-section button,#billPrintArea #clearBill,#billPrintArea #printBill{display:none !important;}}
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
    const unitHint = document.getElementById('qtyUnitHint');
    const addBtn = document.getElementById('addToBill');

    const billItemsEl = document.getElementById('billItems');
    const tailoringBreakdownEl = document.getElementById('tailoringBreakdown');

    const customerSelect = document.getElementById('customer_id');
    const billCustomerEl = document.getElementById('billCustomer');
    const customerPhoneInput = document.getElementById('customerPhone');
    const checkCustomerBtn = document.getElementById('checkCustomerBtn');
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
    const discountAmountInputEl = document.getElementById('discount_amount');

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

    function initOrderSelect2(selectEl, placeholder = 'Select option') {
        if (!selectEl || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
            return;
        }

        if (selectEl.dataset.select2Ready === '1') {
            return;
        }

        const hasEmptyOption = Array.from(selectEl.options || []).some((option) => option.value === '');

        window.jQuery(selectEl).select2({
            width: '100%',
            placeholder,
            allowClear: hasEmptyOption,
            dropdownParent: window.jQuery(selectEl.closest('.tp-modal') || document.body),
        });

        selectEl.dataset.select2Ready = '1';
    }

    function refreshOrderSelect2(selectEl) {
        if (!selectEl || selectEl.dataset.select2Ready !== '1' || !window.jQuery) {
            return;
        }

        window.jQuery(selectEl).trigger('change.select2');
    }

    function bindOrderSelectChange(selectEl, onChange) {
        if (!selectEl || typeof onChange !== 'function') {
            return;
        }

        selectEl.addEventListener('change', onChange);

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            window.jQuery(selectEl).on('select2:select select2:clear', onChange);
        }
    }

    // Bill State
    let billItems = [];
    let discount = { type: 'none', value: 0 };
    let vatEnabled = Boolean(initialVatEnabled);
    let latestGrandTotal = 0;

    // For modal add/edit
    let pendingCustom = null; // {productId, qty, unitPrice, name, unitLabel}
    let editingCustomIndex = -1;

    const customersByPhone = new Map();
    const customersById = new Map();

    function money(n) { return Number(n || 0).toFixed(2); }
    function normalizePhone(value) { return String(value || '').replace(/\D+/g, ''); }

    function updatePaymentSummary(discountAmount, grandTotal) {
        latestGrandTotal = Number(grandTotal || 0);
        const safeDiscount = Math.max(Number(discountAmount || 0), 0);

        if (discountAmountInputEl) discountAmountInputEl.value = money(safeDiscount);
    }

    function upsertCustomerIndex(customer) {
        const customerId = String(customer?.id || '');
        if (customerId) {
            customersById.set(customerId, {
                id: Number(customer.id),
                name: String(customer.name || ''),
                phone: String(customer.phone || ''),
                customerType: String(customer.customer_type || ''),
            });
        }
        const normalized = normalizePhone(customer?.phone || '');
        if (!normalized) return;
        customersByPhone.set(normalized, {
            id: Number(customer.id),
            name: String(customer.name || ''),
            phone: String(customer.phone || ''),
            customerType: String(customer.customer_type || ''),
        });
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
        if (cat === 'fabric') return ['Fabric', 'product-category fabric-cat'];
        if (cat === 'readymade') return ['Ready-Made', 'product-category ready-made-cat'];
        return ['Custom', 'product-category custom-cat'];
    }

    function getAvailableQtyForProduct(product) {
        if (!product) return 0;
        return Number(product.availableQty || 0);
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

    function resolveDefaultPrice(productId) {
        const p = productMap.get(String(productId));
        if (!p) return 0;
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
            opt.textContent = `${p.name} (${p.code}) | Available: ${available}${p.unitLabel ? ' ' + p.unitLabel : ''}`;
            productSelect.appendChild(opt);
        });

        productSelect.value = '';
        unitHint.textContent = '-';
        refreshOrderSelect2(productSelect);
    }

    function updateVariantOptions() {
        const pid = productSelect.value;
        const p = productMap.get(String(pid));
        unitHint.textContent = p ? `Available: ${money(Number(p.availableQty || 0))}${p.unitLabel ? ' ' + p.unitLabel : ''}` : '-';
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

        refreshOrderSelect2(garmentTypeSelect);
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
            row.className = 'bill-item';

            const unitLabel = item.unitLabel ? ` ${item.unitLabel}` : '';

            let measurementInfo = '';
            if (item.measurements && item.measurements.length) {
                measurementInfo = `<div class="item-sub"><i class="fas fa-ruler"></i> ${item.garmentTitle || 'Garment'} | ${item.measurements.slice(0,3).map(m => `${m.type}: ${m.measurement}${m.unit ? ' '+m.unit : ''}`).join(', ')}${item.measurements.length>3 ? ' ...' : ''}</div>`;
            }

            row.innerHTML = `
                <div class="item-details">
                    <div>
                        <div>
                            ${item.name}
                            <span class="${chipClass}">${label}</span>
                            <div class="item-sub">${money(item.qty)}${unitLabel} × NPR ${money(item.unitPrice)}</div>
                            ${measurementInfo}
                            ${item.tailoring ? `<div class="stitching-detail"><i class="fas fa-cut"></i> <strong>Tailoring:</strong> ${item.tailoring.package} - NPR ${money(item.tailoring.amount)}</div>` : ''}
                        </div>
                        <div class="product-actions">
                            <button type="button" class="btn-secondary" data-action="edit" data-id="${item.id}">Edit</button>
                            <button type="button" class="btn-danger" data-action="remove" data-id="${item.id}">Remove</button>
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
                html += `<div class="tailoring-item"><span>${l.label}</span><span>NPR ${money(l.amount)}</span></div>`;
            });
            html += `<div class="tailoring-item tailoring-total"><span>Total Tailoring Charges</span><span>NPR ${money(totalTailoring)}</span></div>`;
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
        return true;
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
                    qty: Number(item.garmentQty || item.qty || 0), // keep pcs qty if you add garmentQty later
                    unitPrice: Number(item.baseUnitPrice || item.unitPrice || 0),
                    name: item.name,
                    unitLabel: item.unitLabel || '',
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
        const qty = Number(qtyInput.value || 0); // for custom: pcs qty
        const unitPrice = resolveDefaultPrice(productId);

        if (!customerSelect.value) {
            alert('Check/select customer first.');
            customerPhoneInput.focus();
            return;
        }

        if (!productId) { alert('Select a product.'); return; }
        if (qty <= 0) { alert('Quantity must be > 0'); return; }
        if (unitPrice < 0) { alert('Unit price must be >= 0'); return; }

        const p = productMap.get(String(productId));

        if (category !== 'custom' && p) {
            const availableQty = Number(p.availableQty || 0);
            if (qty > availableQty) {
                alert(`Only ${money(availableQty)} ${p.unitLabel || ''} is available in current outlet.`);
                return;
            }
        }

        if (category !== 'custom') {
            billItems.push({
                id: Date.now(),
                category,
                productId,
                name: p ? `${p.name} (${p.code})` : 'Product',
                unitLabel: p?.unitLabel || '',
                qty,
                unitPrice,
            });
            renderBill();

            productSelect.value = '';
            qtyInput.value = '1.00';
            unitHint.textContent = '-';
            return;
        }

        // Custom => open modal
        const customPayload = {
            productId,
            qty, // pcs qty
            unitPrice, // fabric per-unit price
            name: p ? `${p.name} (${p.code})` : 'Custom Product',
            unitLabel: p?.unitLabel || '',
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

            let availableQty = Number(product.availableQty || 0);

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

            name: pendingCustom.name,
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
        if (!customer) {
            billCustomerEl.textContent = '-';
            return;
        }

        const customerName = customer.phone
            ? `${customer.name} (${customer.phone})`
            : customer.name;

        billCustomerEl.innerHTML = `
            <div><strong>Customer:</strong> ${customerName}</div>
            <div><strong>Type:</strong> ${formatCustomerTypeLabel(customer.customerType)}</div>
        `;
    }

    checkCustomerBtn.addEventListener('click', () => {
        const normalizedPhone = normalizePhone(customerPhoneInput.value);
        if (normalizedPhone.length < 7) {
            alert('Enter a valid phone number before checking.');
            return;
        }

        const foundCustomer = customersByPhone.get(normalizedPhone);
        if (foundCustomer) {
            selectCustomer(foundCustomer);
            return;
        }

        customerSelect.value = '';
        updateCustomerDisplay();
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

        if (payload.phone.length < 7) { alert('Enter a valid phone number before creating customer.'); return; }
        if (!payload.name || !payload.email || !payload.address) { alert('Name, email and address are required to create customer.'); return; }

        createCustomerBtn.disabled = true;
        try {
            const response = await fetch(resolveCustomerUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(payload),
            });

            const data = await response.json();
            if (!response.ok) { alert(data?.message || 'Unable to create customer.'); return; }

            const customer = data?.customer;
            if (!customer?.id) { alert('Customer response was invalid. Please try again.'); return; }

            upsertCustomerIndex(customer);
            selectCustomer(customer);
        } catch (error) {
            alert('Unable to reach server. Please retry.');
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
    bindOrderSelectChange(categorySelect, updateProductOptions);
    bindOrderSelectChange(productSelect, updateVariantOptions);

    // Init
    initOrderSelect2(productSelect, 'Select or scan barcode');
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
