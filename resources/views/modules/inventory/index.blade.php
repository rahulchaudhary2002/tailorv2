@extends('layouts.app')

@section('title', 'Inventory Management')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Inventory Management</h1>
        <p>Track stock-in, stock-out, transfer, and adjustments with location and vendor consistency.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary js-open-transaction-modal">New Transaction</button>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 16px;">
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-number">{{ $stats['products_in_stock'] }}</div>
            <div class="stat-label">Products in Stock</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-number">{{ number_format((float) $stats['total_quantity'], 2) }}</div>
            <div class="stat-label">On Hand Qty</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-number">{{ $stats['open_low_stock_alerts'] }}</div>
            <div class="stat-label">Low Stock Alerts</div>
        </div>
    </div>
</div>

@php
    $query = trim((string) request('q', ''));
    $selectedProductId = (int) request('product_id', 0);
    $selectedLocationId = (int) request('location_id', 0);
    $selectedVendorId = (int) request('vendor_id', 0);
@endphp

@if (session('success'))
    <div class="alert alert-success" style="margin-bottom: 16px;">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger" style="margin-bottom: 16px;">{{ session('error') }}</div>
@endif

<div class="directory-reporting">
    <div class="directory-reporting__filter-bar">
        <div class="directory-reporting__filter-head">
            <h3 class="directory-reporting__filter-title">Filter Records</h3>
            @if ($query !== '' || $selectedProductId > 0 || $selectedLocationId > 0 || $selectedVendorId > 0)
                <a href="{{ url()->current() }}" class="btn btn-light btn-sm">Clear Filters</a>
            @endif
        </div>

        <form method="GET" class="inventory-filter-form">
            <div class="inventory-filter-form__fields">
                <div class="outlet-form-group inventory-filter-form__field inventory-filter-form__field--search">
                    <label for="q_filter">Search</label>
                    <input id="q_filter" type="text" name="q" class="outlet-input" value="{{ $query }}" placeholder="Search by product code, product name, or location...">
                </div>

                <div class="outlet-form-group inventory-filter-form__field">
                    <label for="product_filter">Product</label>
                    <select id="product_filter" name="product_id" class="outlet-input">
                        <option value="">All Products</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected($selectedProductId === (int) $product->id)>
                                {{ $product->name }}@if($product->code) ({{ $product->code }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="outlet-form-group inventory-filter-form__field">
                    <label for="location_filter">Location</label>
                    <select id="location_filter" name="location_id" class="outlet-input">
                        <option value="">All Locations</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected($selectedLocationId === (int) $location->id)>
                                {{ $location->name }} ({{ $location->type }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="outlet-form-group inventory-filter-form__field">
                    <label for="vendor_filter">Vendor</label>
                    <select id="vendor_filter" name="vendor_id" class="outlet-input">
                        <option value="">All Vendors</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected($selectedVendorId === (int) $vendor->id)>
                                {{ $vendor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="inventory-filter-form__actions">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="{{ url()->current() }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div id="inventoryTransactionModal" class="app-modal" aria-hidden="true">
    <div class="app-modal__backdrop js-transaction-modal-close"></div>
    <div class="app-modal__panel inventory-transaction-modal__panel" role="dialog" aria-modal="true" aria-labelledby="inventoryTransactionModalTitle">
        <div class="app-modal__header">
            <h3 id="inventoryTransactionModalTitle">Inventory Transaction</h3>
            <button type="button" class="inventory-modal-close js-transaction-modal-close" aria-label="Close transaction modal">&times;</button>
        </div>

        <form action="{{ route('inventory.adjust') }}" method="POST" style="padding: 16px;">
            @csrf
            <input type="hidden" name="tab" value="{{ old('tab', 'stock-summary') }}" class="js-active-tab-input">
            @if ($errors->any())
                <div class="alert alert-danger inventory-modal-alert">
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
                    <label for="trx_type">Transaction Type</label>
                    <select id="trx_type" name="trx_type" class="outlet-input @error('trx_type') is-invalid @enderror" required>
                        <option value="in" @selected(old('trx_type') === 'in')>Stock In</option>
                        <option value="out" @selected(old('trx_type') === 'out')>Stock Out</option>
                        <option value="transfer" @selected(old('trx_type') === 'transfer')>Transfer</option>
                        <option value="adjustment" @selected(old('trx_type', 'adjustment') === 'adjustment')>Adjustment</option>
                    </select>
                    @error('trx_type')
                        <div class="inventory-field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="outlet-form-group" id="group-location-id">
                    <label for="location_id">Location (in/out/adjustment)</label>
                    <select id="location_id" name="location_id" class="outlet-input @error('location_id') is-invalid @enderror">
                        <option value="">Select Location</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" data-type="{{ $location->type }}" @selected((string) old('location_id') === (string) $location->id)>
                                {{ $location->name }} ({{ $location->type }})
                            </option>
                        @endforeach
                    </select>
                    @error('location_id')
                        <div class="inventory-field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="outlet-form-group" id="group-from-location-id">
                    <label for="from_location_id">From Location (transfer)</label>
                    <select id="from_location_id" name="from_location_id" class="outlet-input @error('from_location_id') is-invalid @enderror">
                        <option value="">Select Source</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" data-type="{{ $location->type }}" @selected((string) old('from_location_id') === (string) $location->id)>
                                {{ $location->name }} ({{ $location->type }})
                            </option>
                        @endforeach
                    </select>
                    @error('from_location_id')
                        <div class="inventory-field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="outlet-form-group" id="group-to-location-id">
                    <label for="to_location_id">To Location (transfer)</label>
                    <select id="to_location_id" name="to_location_id" class="outlet-input @error('to_location_id') is-invalid @enderror">
                        <option value="">Select Destination</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" data-type="{{ $location->type }}" @selected((string) old('to_location_id') === (string) $location->id)>
                                {{ $location->name }} ({{ $location->type }})
                            </option>
                        @endforeach
                    </select>
                    @error('to_location_id')
                        <div class="inventory-field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="outlet-form-group">
                    <label for="product_id">Product</label>
                    <select id="product_id" name="product_id" class="outlet-input js-inventory-product-select @error('product_id') is-invalid @enderror" required>
                        <option value="">Select Product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>
                                {{ $product->name }} ({{ $product->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                        <div class="inventory-field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="outlet-form-group" id="group-adjustment-type">
                    <label for="adjustment_type">Adjustment Mode</label>
                    <select id="adjustment_type" name="adjustment_type" class="outlet-input @error('adjustment_type') is-invalid @enderror">
                        <option value="add" @selected(old('adjustment_type') === 'add')>Add</option>
                        <option value="remove" @selected(old('adjustment_type') === 'remove')>Remove</option>
                        <option value="set" @selected(old('adjustment_type', 'set') === 'set')>Set Final Qty</option>
                    </select>
                    @error('adjustment_type')
                        <div class="inventory-field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="outlet-form-group">
                    <label for="quantity">Quantity</label>
                    <input id="quantity" name="quantity" type="number" min="0.01" step="0.01" class="outlet-input @error('quantity') is-invalid @enderror" value="{{ old('quantity', '1.00') }}" required>
                    @error('quantity')
                        <div class="inventory-field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="outlet-form-group">
                    <label for="unit_cost">Unit Cost</label>
                    <input id="unit_cost" name="unit_cost" type="number" min="0" step="0.01" class="outlet-input @error('unit_cost') is-invalid @enderror" value="{{ old('unit_cost', '0.00') }}" required>
                    @error('unit_cost')
                        <div class="inventory-field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="outlet-form-group outlet-form-group-full">
                    <div class="inventory-reorder-toggle">
                        <label for="set_reorder_level" class="user-switch">
                            <input
                                id="set_reorder_level"
                                name="set_reorder_level"
                                type="checkbox"
                                value="1"
                                @checked((bool) old('set_reorder_level'))
                            >
                            <span class="user-switch-slider"></span>
                        </label>
                        <label for="set_reorder_level" class="inventory-reorder-label">
                            Set/Update Reorder Level for this product at selected location
                        </label>
                    </div>
                    <label id="reorder-location-hint" for="set_reorder_level" class="inventory-reorder-hint">
                        Applies to selected location (or destination location for transfer).
                    </label>
                    @error('set_reorder_level')
                        <div class="inventory-field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="outlet-form-group" id="group-reorder-min-qty">
                    <label for="reorder_min_qty">Reorder Min Qty</label>
                    <input
                        id="reorder_min_qty"
                        name="reorder_min_qty"
                        type="number"
                        min="0.01"
                        step="0.01"
                        class="outlet-input @error('reorder_min_qty') is-invalid @enderror"
                        value="{{ old('reorder_min_qty') }}"
                        placeholder="e.g. 10.00"
                    >
                    @error('reorder_min_qty')
                        <div class="inventory-field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="outlet-form-group" id="group-reorder-qty">
                    <label for="reorder_qty">Reorder Qty (Optional)</label>
                    <input
                        id="reorder_qty"
                        name="reorder_qty"
                        type="number"
                        min="0.01"
                        step="0.01"
                        class="outlet-input @error('reorder_qty') is-invalid @enderror"
                        value="{{ old('reorder_qty') }}"
                        placeholder="e.g. 25.00"
                    >
                    @error('reorder_qty')
                        <div class="inventory-field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="outlet-form-group outlet-form-group-full">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" class="outlet-input @error('notes') is-invalid @enderror" rows="2" placeholder="Optional transaction note">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="inventory-field-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="outlet-form-actions">
                <button type="submit" class="btn btn-primary">Save Transaction</button>
            </div>
        </form>
    </div>
</div>

<div class="app-tabs" role="tablist" aria-label="Inventory sections">
    <button type="button" class="app-tab-button js-page-tab is-active" data-tab-target="stock-summary" aria-selected="true">Stock Summary</button>
    <button type="button" class="app-tab-button js-page-tab" data-tab-target="alerts" aria-selected="false">Low Stock Alerts</button>
</div>

<div class="js-page-tab-panel" data-tab-panel="alerts" hidden>
<div class="table-card" style="margin-bottom: 16px;">
    <div class="table-header">
        <div class="table-title">Open Low Stock Alerts</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Location</th>
                    <th>Current Qty</th>
                    <th>Min Qty</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($alerts as $alert)
                    <tr>
                        <td>{{ $alert->product?->name }} ({{ $alert->product?->code }})</td>
                        <td>{{ $alert->location?->name }} ({{ $alert->location?->type }})</td>
                        <td>{{ number_format((float) $alert->current_qty, 2) }}</td>
                        <td>{{ number_format((float) $alert->min_qty, 2) }}</td>
                        <td>
                            <span class="app-badge {{ \App\Models\InventoryAlert::statusBadgeClass((string) $alert->status) }}">
                                {{ \App\Models\InventoryAlert::statusLabel((string) $alert->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty">No open low stock alerts.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>

<div class="js-page-tab-panel" data-tab-panel="stock-summary" hidden>
<div class="table-card">
    <div class="table-header">
        <div class="table-title">Current Stock Summary</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Type</th>
                    <th>Vendor</th>
                    <th>Product</th>
                    <th>On Hand</th>
                    <th>Reserved</th>
                    <th>Unit</th>
                    <th>Unit Cost</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stocks as $stock)
                    <tr>
                        <td>{{ $stock->location?->name ?: '-' }}</td>
                        <td>{{ $stock->location?->type ?: '-' }}</td>
                        <td>{{ $stock->vendor?->name ?: '-' }}</td>
                        <td>{{ $stock->product?->name ?: '-' }}</td>
                        <td>{{ number_format((float) $stock->on_hand_qty, 2) }}</td>
                        <td>{{ number_format((float) $stock->reserved_qty, 2) }}</td>
                        <td>{{ $stock->unit?->symbol ?: ($stock->product?->defaultUnitLabel() ?: '-') }}</td>
                        <td>{{ number_format((float) $stock->unit_cost, 2) }}</td>
                        <td>{{ $stock->updated_at->format('M d, Y h:i A') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="empty">No inventory records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($stocks->hasPages())
        <div class="pagination">
            {{ $stocks->links() }}
        </div>
    @endif
</div>
</div>

@endsection

@section('page-specific-script')
<script>
    (function () {
        const body = document.body;
        const tabButtons = Array.from(document.querySelectorAll('.js-page-tab'));
        const tabPanels = Array.from(document.querySelectorAll('.js-page-tab-panel'));
        const transactionModal = document.getElementById('inventoryTransactionModal');
        const transactionModalPanel = transactionModal?.querySelector('.app-modal__panel') || null;
        const openTransactionModalButtons = Array.from(document.querySelectorAll('.js-open-transaction-modal'));
        const trxType = document.getElementById('trx_type');
        const location = document.getElementById('location_id');
        const fromLocation = document.getElementById('from_location_id');
        const toLocation = document.getElementById('to_location_id');
        const product = document.querySelector('.js-inventory-product-select');
        const adjustmentType = document.getElementById('adjustment_type');
        const setReorderLevel = document.getElementById('set_reorder_level');
        const reorderMinQty = document.getElementById('reorder_min_qty');
        const reorderQty = document.getElementById('reorder_qty');
        const reorderLocationHint = document.getElementById('reorder-location-hint');

        const groupLocation = document.getElementById('group-location-id');
        const groupFrom = document.getElementById('group-from-location-id');
        const groupTo = document.getElementById('group-to-location-id');
        const groupAdjustment = document.getElementById('group-adjustment-type');
        const groupReorderMinQty = document.getElementById('group-reorder-min-qty');
        const groupReorderQty = document.getElementById('group-reorder-qty');

        const closeTransactionModal = () => {
            if (!transactionModal) {
                return;
            }

            transactionModal.classList.remove('is-open');
            transactionModal.setAttribute('aria-hidden', 'true');
            body.classList.remove('app-modal-open');
        };

        const openTransactionModal = () => {
            if (!transactionModal) {
                return;
            }

            transactionModal.classList.add('is-open');
            transactionModal.setAttribute('aria-hidden', 'false');
            body.classList.add('app-modal-open');
        };

        const setActiveTab = (tab) => {
            tabButtons.forEach((button) => {
                const isActive = button.dataset.tabTarget === tab;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            tabPanels.forEach((panel) => {
                panel.hidden = panel.dataset.tabPanel !== tab;
            });

            document.querySelectorAll('.js-active-tab-input').forEach((input) => {
                input.value = tab;
            });
        };

        if (tabButtons.length > 0 && tabPanels.length > 0) {
            const hashTab = String(window.location.hash || '').replace('#', '');
            const initialTab = tabPanels.some((panel) => panel.dataset.tabPanel === hashTab)
                ? hashTab
                : String(tabButtons[0].dataset.tabTarget || 'stock-summary');

            setActiveTab(initialTab);

            tabButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const tab = String(button.dataset.tabTarget || '');
                    if (!tab) {
                        return;
                    }

                    setActiveTab(tab);
                    history.replaceState(null, '', '#' + tab);
                });
            });
        }

        openTransactionModalButtons.forEach((button) => {
            button.addEventListener('click', openTransactionModal);
        });

        transactionModal?.querySelectorAll('.js-transaction-modal-close').forEach((button) => {
            button.addEventListener('click', closeTransactionModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && transactionModal?.classList.contains('is-open')) {
                closeTransactionModal();
            }
        });

        if (!trxType) {
            return;
        }

        const toggleGroup = (groupEl, inputEl, show, required = false) => {
            if (!groupEl || !inputEl) {
                return;
            }
            groupEl.style.display = show ? '' : 'none';
            inputEl.required = required;
        };

        const initInventoryProductSelect2 = () => {
            if (!product || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                return;
            }

            if (product.dataset.select2Ready === '1') {
                return;
            }

            window.jQuery(product).select2({
                width: '100%',
                placeholder: product.options[0]?.textContent?.trim() || 'Select Product',
                allowClear: !product.required,
                dropdownParent: window.jQuery(transactionModalPanel || transactionModal || document.body),
            });

            product.dataset.select2Ready = '1';
        };

        const bindProductChange = (selectEl, onChange) => {
            if (!selectEl || typeof onChange !== 'function') {
                return;
            }

            selectEl.addEventListener('change', onChange);

            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                window.jQuery(selectEl).on('select2:select select2:clear', onChange);
            }
        };

        const applyDependentData = () => {};

        const applyVisibility = () => {
            const selectedTrx = (trxType.value || '').toLowerCase();
            const isTransfer = selectedTrx === 'transfer';
            const isAdjustment = selectedTrx === 'adjustment';
            const needsSingleLocation = selectedTrx === 'in' || selectedTrx === 'out' || isAdjustment;
            const reorderEnabled = !!setReorderLevel?.checked;

            toggleGroup(groupLocation, location, needsSingleLocation, needsSingleLocation);
            toggleGroup(groupFrom, fromLocation, isTransfer, isTransfer);
            toggleGroup(groupTo, toLocation, isTransfer, isTransfer);

            if (!needsSingleLocation && location) {
                location.value = '';
            }
            if (!isTransfer && fromLocation) {
                fromLocation.value = '';
            }
            if (!isTransfer && toLocation) {
                toLocation.value = '';
            }

            toggleGroup(groupAdjustment, adjustmentType, isAdjustment, false);
            if (!isAdjustment && adjustmentType) {
                adjustmentType.value = 'add';
            }

            toggleGroup(groupReorderMinQty, reorderMinQty, reorderEnabled, reorderEnabled);
            toggleGroup(groupReorderQty, reorderQty, reorderEnabled, false);
            if (!reorderEnabled) {
                if (reorderMinQty) reorderMinQty.value = '';
                if (reorderQty) reorderQty.value = '';
            }

            if (reorderLocationHint) {
                reorderLocationHint.textContent = isTransfer
                    ? 'Applies to destination location for transfer transactions.'
                    : 'Applies to selected location.';
            }

            applyDependentData();
        };

        trxType.addEventListener('change', applyVisibility);
        setReorderLevel?.addEventListener('change', applyVisibility);
        bindProductChange(product, applyDependentData);
        initInventoryProductSelect2();
        applyVisibility();

        if (@json($errors->any())) {
            openTransactionModal();
        }
    })();
</script>
@endsection

@section('page-specific-style')
<style>
    .app-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }

    .app-tab-button {
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        color: #334155;
        border-radius: 8px;
        padding: 8px 14px;
        font-size: 0.875rem;
        cursor: pointer;
    }

    .app-tab-button.is-active {
        background: #0f766e;
        border-color: #0f766e;
        color: #fff;
    }

    .js-page-tab-panel[hidden] {
        display: none !important;
    }

    .inventory-reorder-toggle {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
        font-weight: 500;
    }

    .inventory-reorder-label,
    .inventory-reorder-hint {
        cursor: pointer;
    }

    .inventory-reorder-hint {
        color: #64748b;
        display: block;
        margin-top: 4px;
    }

    .inventory-actions-card .table-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .inventory-modal-alert {
        margin-bottom: 16px;
    }

    .inventory-field-error {
        margin-top: 6px;
        color: #b91c1c;
        font-size: 0.875rem;
        line-height: 1.4;
    }

    .inventory-transaction-modal__panel .outlet-input.is-invalid {
        border-color: #dc2626;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
    }

    .app-modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1200;
    }

    .app-modal.is-open {
        display: flex;
    }

    .app-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
    }

    .app-modal__panel {
        position: relative;
        width: min(960px, calc(100vw - 32px));
        max-height: calc(100vh - 32px);
        overflow-x: hidden;
        overflow-y: auto;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
        box-sizing: border-box;
    }

    .inventory-transaction-modal__panel {
        width: min(1080px, calc(100vw - 32px));
    }

    .inventory-transaction-modal__panel .outlet-form-grid {
        min-width: 0;
    }

    .inventory-transaction-modal__panel .outlet-form-group,
    .inventory-transaction-modal__panel .outlet-input {
        min-width: 0;
    }

    .app-modal__header {
        position: sticky;
        top: 0;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 20px;
        background: #fff;
        border-bottom: 1px solid #e2e8f0;
    }

    .app-modal__header h3 {
        margin: 0;
    }

    .inventory-modal-close {
        border: 0;
        background: transparent;
        color: #334155;
        font-size: 28px;
        line-height: 1;
        padding: 0;
        cursor: pointer;
    }

    .select2-container--open {
        z-index: 1300;
    }

    body.app-modal-open {
        overflow: hidden;
    }

    .inventory-filter-form {
        display: grid;
        gap: 14px;
    }

    .inventory-filter-form__fields {
        display: grid;
        grid-template-columns: minmax(280px, 1.4fr) repeat(3, minmax(200px, 1fr));
        gap: 12px;
        align-items: end;
    }

    .inventory-filter-form__field {
        margin-bottom: 0;
    }

    .inventory-filter-form__field--search .outlet-input {
        min-height: 44px;
    }

    .inventory-filter-form__actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding-top: 2px;
    }

    @media (max-width: 992px) {
        .inventory-filter-form__fields {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .inventory-filter-form__field--search {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 640px) {
        .inventory-filter-form__fields {
            grid-template-columns: 1fr;
        }

        .inventory-filter-form__actions {
            justify-content: stretch;
            flex-direction: column;
        }

        .inventory-filter-form__actions .btn {
            width: 100%;
        }
    }
</style>
@endsection
