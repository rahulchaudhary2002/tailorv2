@extends('layouts.app')

@section('title', 'Edit Purchase')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Edit Purchase</h1>
        <p>Correct purchase details using the same purchase form layout.</p>
    </div>
</div>

<form action="{{ route('rawMaterialPurchase.update', $purchase) }}" method="POST">
    @csrf
    @method('PUT')
    @include('modules.raw_material_purchase.partials.form', [
        'title' => 'Purchase Information',
        'submitLabel' => 'Save Changes',
        'vendors' => $vendors,
        'products' => $products,
        'selectedVendorId' => $purchase->vendor_id,
        'selectedPurchaseDate' => optional($purchase->purchased_at)->toDateString(),
        'notesValue' => $purchase->notes,
        'allowMultipleItems' => false,
        'oldItems' => old('items', [
            [
                'product_id' => $purchase->product_id,
                'quantity' => $purchase->quantity,
                'unit_price' => $purchase->unit_price,
            ],
        ]),
    ])
</form>
@endsection

@section('page-specific-script')
@php
    $productPayload = $products->map(function ($product) {
        return [
            'id' => $product->id,
        ];
    })->values();
@endphp
<script>
    (function () {
        const products = @json($productPayload);

        const productMap = new Map(products.map((product) => [String(product.id), product]));
        const body = document.getElementById('purchase-items-body');
        const addBtn = document.getElementById('add-item-row');
        const template = document.getElementById('purchase-item-row-template');
        const grandTotalEl = document.getElementById('purchase-grand-total');

        if (!body || !grandTotalEl) {
            return;
        }

        function reindexRows() {
            const rows = body.querySelectorAll('.purchase-item-row');

            rows.forEach((row, index) => {
                const product = row.querySelector('.item-product');
                const qty = row.querySelector('.item-quantity');
                const unitPrice = row.querySelector('.item-unit-price');

                if (product) product.name = `items[${index}][product_id]`;
                if (qty) qty.name = `items[${index}][quantity]`;
                if (unitPrice) unitPrice.name = `items[${index}][unit_price]`;
            });
        }

        function initPurchaseMaterialSelect2(root = document) {
            if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                return;
            }

            root.querySelectorAll('.item-product, #vendor_id').forEach((selectEl) => {
                if (selectEl.dataset.select2Ready === '1') {
                    return;
                }

                window.jQuery(selectEl).select2({
                    width: '100%',
                    placeholder: selectEl.options[0]?.textContent?.trim() || 'Select Option',
                    allowClear: !selectEl.required,
                });

                selectEl.dataset.select2Ready = '1';
            });
        }

        function bindProductChange(selectEl, onChange) {
            if (!selectEl || typeof onChange !== 'function') {
                return;
            }

            selectEl.addEventListener('change', onChange);

            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                window.jQuery(selectEl).on('select2:select select2:clear', onChange);
            }
        }

        function updateRowTotal(row) {
            const qty = Number(row.querySelector('.item-quantity')?.value || 0);
            const unitPrice = Number(row.querySelector('.item-unit-price')?.value || 0);
            const total = qty * unitPrice;
            const totalCell = row.querySelector('.item-total');

            if (totalCell) {
                totalCell.textContent = total.toFixed(2);
            }
        }

        function updateGrandTotal() {
            let total = 0;
            body.querySelectorAll('.purchase-item-row').forEach((row) => {
                const qty = Number(row.querySelector('.item-quantity')?.value || 0);
                const unitPrice = Number(row.querySelector('.item-unit-price')?.value || 0);
                total += qty * unitPrice;
            });
            grandTotalEl.value = total.toFixed(2);
        }

        function syncTotals() {
            body.querySelectorAll('.purchase-item-row').forEach((row) => updateRowTotal(row));
            updateGrandTotal();
        }

        function bindRowEvents(row) {
            const productSelect = row.querySelector('.item-product');
            const qtyInput = row.querySelector('.item-quantity');
            const unitPriceInput = row.querySelector('.item-unit-price');
            const removeBtn = row.querySelector('.remove-item-row');

            bindProductChange(productSelect, () => {});

            qtyInput?.addEventListener('input', () => {
                updateRowTotal(row);
                updateGrandTotal();
            });

            unitPriceInput?.addEventListener('input', () => {
                updateRowTotal(row);
                updateGrandTotal();
            });

            removeBtn?.addEventListener('click', () => {
                const rows = body.querySelectorAll('.purchase-item-row');
                if (rows.length <= 1) {
                    return;
                }

                row.remove();
                reindexRows();
                updateGrandTotal();
            });
        }

        function addRow() {
            if (!template) {
                return;
            }

            const fragment = template.content.cloneNode(true);
            const row = fragment.querySelector('.purchase-item-row');

            if (!row) {
                return;
            }

            body.appendChild(row);
            initPurchaseMaterialSelect2(row);
            bindRowEvents(row);
            reindexRows();
            syncTotals();
        }

        addBtn?.addEventListener('click', addRow);

        body.querySelectorAll('.purchase-item-row').forEach((row) => {
            bindRowEvents(row);
        });

        initPurchaseMaterialSelect2(document);
        reindexRows();
        syncTotals();
    })();
</script>
@endsection
