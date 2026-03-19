@php
    $productPayload = $products->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'amount' => (float) $product->amount,
            'category' => $product->category?->slug,
        ];
    })->values();
@endphp
<script>
    (function () {
        const products = @json($productPayload);
        const prefixes = {
            fabrics: 'FAB',
            accessories: 'ACC',
            'ready-made': 'RM',
        };

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
                const type = row.querySelector('.item-product-type');
                const typeHidden = row.querySelector('.item-product-type-hidden');
                const code = row.querySelector('.item-product-code');
                const unitPrice = row.querySelector('.item-unit-price');
                const qty = row.querySelector('.item-quantity');

                if (product) product.name = `items[${index}][product_reference]`;
                if (type) type.name = `items[${index}][product_type]`;
                if (typeHidden) typeHidden.name = `items[${index}][product_type]`;
                if (code) code.name = `items[${index}][product_code]`;
                if (unitPrice) unitPrice.name = `items[${index}][unit_price]`;
                if (qty) qty.name = `items[${index}][quantity]`;
            });
        }

        function syncProductTypeLock(row) {
            const selectedProduct = getSelectedProduct(row);
            const productTypeInput = row.querySelector('.item-product-type');
            const productTypeHidden = row.querySelector('.item-product-type-hidden');

            if (!productTypeInput || !productTypeHidden) {
                return;
            }

            if (selectedProduct) {
                productTypeInput.value = selectedProduct.category || 'fabrics';
                productTypeHidden.value = productTypeInput.value;
                productTypeInput.disabled = true;
                productTypeHidden.disabled = false;
                return;
            }

            productTypeInput.disabled = false;
            productTypeHidden.value = productTypeInput.value || 'fabrics';
            productTypeHidden.disabled = true;
        }

        function initPurchaseMaterialSelect2(root = document) {
            if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                return;
            }

            root.querySelectorAll('#vendor_id').forEach((selectEl) => {
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

            root.querySelectorAll('.item-product').forEach((selectEl) => {
                if (selectEl.dataset.select2Ready === '1') {
                    return;
                }

                window.jQuery(selectEl).select2({
                    width: '100%',
                    tags: true,
                    placeholder: selectEl.options[0]?.textContent?.trim() || 'Select or type vendor product',
                    allowClear: !selectEl.required,
                    createTag(params) {
                        const term = params.term.trim();

                        if (!term) {
                            return null;
                        }

                        return {
                            id: `new:${term}`,
                            text: term,
                            newTag: true,
                        };
                    },
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

        function getSelectedProduct(row) {
            const reference = row.querySelector('.item-product')?.value || '';

            if (!reference.startsWith('existing:')) {
                return null;
            }

            return productMap.get(reference.replace('existing:', '')) || null;
        }

        function buildNextCodeMap() {
            const nextByType = {
                fabrics: 0,
                accessories: 0,
                'ready-made': 0,
            };

            products.forEach((product) => {
                const prefix = prefixes[product.category];

                if (!prefix || typeof product.code !== 'string') {
                    return;
                }

                const match = product.code.match(new RegExp(`^${prefix}-(\\d+)$`));
                if (!match) {
                    return;
                }

                nextByType[product.category] = Math.max(nextByType[product.category] || 0, Number(match[1]));
            });

            return nextByType;
        }

        function refreshGeneratedCodes() {
            const nextByType = buildNextCodeMap();

            body.querySelectorAll('.purchase-item-row').forEach((row) => {
                const selectedProduct = getSelectedProduct(row);
                const codeInput = row.querySelector('.item-product-code');
                const productTypeInput = row.querySelector('.item-product-type');
                const reference = row.querySelector('.item-product')?.value || '';
                const productType = productTypeInput?.value || 'fabrics';
                const prefix = prefixes[productType] || 'FAB';

                if (!codeInput) {
                    return;
                }

                if (selectedProduct) {
                    codeInput.value = selectedProduct.code || '';
                    return;
                }

                if (reference.startsWith('new:')) {
                    nextByType[productType] = (nextByType[productType] || 0) + 1;
                    codeInput.value = `${prefix}-${String(nextByType[productType]).padStart(4, '0')}`;
                    return;
                }

                codeInput.value = '';
            });
        }

        function syncRowFromSelection(row, options = {}) {
            const selectedProduct = getSelectedProduct(row);
            const productTypeInput = row.querySelector('.item-product-type');
            const unitPriceInput = row.querySelector('.item-unit-price');
            const shouldSyncAmount = Boolean(options.syncAmount);

            if (selectedProduct) {
                if (productTypeInput) {
                    productTypeInput.value = selectedProduct.category || 'fabrics';
                }

                if (unitPriceInput && shouldSyncAmount) {
                    unitPriceInput.value = Number(selectedProduct.amount || 0).toFixed(2);
                }
            }

            syncProductTypeLock(row);
            refreshGeneratedCodes();
            updateRowTotal(row);
            updateGrandTotal();
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
            refreshGeneratedCodes();
            body.querySelectorAll('.purchase-item-row').forEach((row) => updateRowTotal(row));
            updateGrandTotal();
        }

        function bindRowEvents(row) {
            const productSelect = row.querySelector('.item-product');
            const productTypeInput = row.querySelector('.item-product-type');
            const qtyInput = row.querySelector('.item-quantity');
            const unitPriceInput = row.querySelector('.item-unit-price');
            const removeBtn = row.querySelector('.remove-item-row');

            bindProductChange(productSelect, () => syncRowFromSelection(row, { syncAmount: true }));

            productTypeInput?.addEventListener('change', () => {
                syncProductTypeLock(row);
                refreshGeneratedCodes();
            });

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
                syncTotals();
            });

            syncRowFromSelection(row);
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
