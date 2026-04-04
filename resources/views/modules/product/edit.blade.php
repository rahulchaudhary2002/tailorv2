@extends('layouts.app')

@section('title', 'Edit Product')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Edit Product</h1>
        <p>Update product details and inventory information.</p>
    </div>
</div>

@php
    $inventoryErrorBag = $errors->getBag('inventoryTransaction');
@endphp

<div class="app-tabs" role="tablist" aria-label="Product edit sections">
    <button type="button" class="app-tab-button js-product-tab is-active" data-tab-target="details" aria-selected="true">Details</button>
    @if ($canManageInventory)
        <button type="button" class="app-tab-button js-product-tab" data-tab-target="inventory" aria-selected="false">Inventory</button>
    @endif
</div>

<div class="js-product-tab-panel" data-tab-panel="details" hidden>
    <form action="{{ route('product.update', $product) }}" method="POST">
        @csrf
        @method('PUT')
        @include('modules.product.partials.form', [
            'title' => 'Product Information',
            'submitLabel' => 'Save Changes',
            'product' => $product,
        ])
    </form>
</div>

@if ($canManageInventory)
    <div class="js-product-tab-panel" data-tab-panel="inventory" hidden>
        <div class="table-card outlet-form-card">
            <div class="table-header">
                <div class="table-title">Inventory Transaction</div>
            </div>

            @if (session('inventory_success'))
                <div class="alert alert-success">{{ session('inventory_success') }}</div>
            @endif
            @if (session('inventory_error'))
                <div class="alert alert-danger">{{ session('inventory_error') }}</div>
            @endif
            @if ($inventoryErrorBag->any())
                <div class="alert alert-danger">
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        @foreach ($inventoryErrorBag->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('product.inventory.store', $product) }}" method="POST">
                @csrf
                <div class="outlet-form-grid">
                    <div class="outlet-form-group">
                        <label for="trx_type">Transaction Type</label>
                        <select id="trx_type" name="trx_type" class="outlet-input @if($inventoryErrorBag->has('trx_type')) is-invalid @endif" required>
                            <option value="in" @selected(old('trx_type') === 'in')>Stock In</option>
                            <option value="out" @selected(old('trx_type') === 'out')>Stock Out</option>
                            <option value="transfer" @selected(old('trx_type') === 'transfer')>Transfer</option>
                            <option value="adjustment" @selected(old('trx_type', 'adjustment') === 'adjustment')>Adjustment</option>
                        </select>
                        @if ($inventoryErrorBag->has('trx_type'))
                            <div class="form-error">{{ $inventoryErrorBag->first('trx_type') }}</div>
                        @endif
                    </div>

                    <div class="outlet-form-group">
                        <label for="product_display">Product</label>
                        <input
                            id="product_display"
                            type="text"
                            class="outlet-input"
                            value="{{ $product->name }} ({{ $product->code }})"
                            readonly
                        >
                    </div>

                    <div class="outlet-form-group" id="group-location-id">
                        <label for="location_id">Location</label>
                        <select id="location_id" name="location_id" class="outlet-input @if($inventoryErrorBag->has('location_id')) is-invalid @endif">
                            <option value="">Select Location</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @selected((string) old('location_id') === (string) $location->id)>
                                    {{ $location->name }} ({{ $location->type }})
                                </option>
                            @endforeach
                        </select>
                        @if ($inventoryErrorBag->has('location_id'))
                            <div class="form-error">{{ $inventoryErrorBag->first('location_id') }}</div>
                        @endif
                    </div>

                    <div class="outlet-form-group" id="group-from-location-id">
                        <label for="from_location_id">From Location</label>
                        <select id="from_location_id" name="from_location_id" class="outlet-input @if($inventoryErrorBag->has('from_location_id')) is-invalid @endif">
                            <option value="">Select Source</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @selected((string) old('from_location_id') === (string) $location->id)>
                                    {{ $location->name }} ({{ $location->type }})
                                </option>
                            @endforeach
                        </select>
                        @if ($inventoryErrorBag->has('from_location_id'))
                            <div class="form-error">{{ $inventoryErrorBag->first('from_location_id') }}</div>
                        @endif
                    </div>

                    <div class="outlet-form-group" id="group-to-location-id">
                        <label for="to_location_id">To Location</label>
                        <select id="to_location_id" name="to_location_id" class="outlet-input @if($inventoryErrorBag->has('to_location_id')) is-invalid @endif">
                            <option value="">Select Destination</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @selected((string) old('to_location_id') === (string) $location->id)>
                                    {{ $location->name }} ({{ $location->type }})
                                </option>
                            @endforeach
                        </select>
                        @if ($inventoryErrorBag->has('to_location_id'))
                            <div class="form-error">{{ $inventoryErrorBag->first('to_location_id') }}</div>
                        @endif
                    </div>

                    <div class="outlet-form-group" id="group-adjustment-type">
                        <label for="adjustment_type">Adjustment Mode</label>
                        <select id="adjustment_type" name="adjustment_type" class="outlet-input @if($inventoryErrorBag->has('adjustment_type')) is-invalid @endif">
                            <option value="add" @selected(old('adjustment_type') === 'add')>Add</option>
                            <option value="remove" @selected(old('adjustment_type') === 'remove')>Remove</option>
                            <option value="set" @selected(old('adjustment_type', 'set') === 'set')>Set Final Qty</option>
                        </select>
                        @if ($inventoryErrorBag->has('adjustment_type'))
                            <div class="form-error">{{ $inventoryErrorBag->first('adjustment_type') }}</div>
                        @endif
                    </div>

                    <div class="outlet-form-group">
                        <label for="quantity">Quantity</label>
                        <input id="quantity" name="quantity" type="number" min="0.01" step="0.01" class="outlet-input @if($inventoryErrorBag->has('quantity')) is-invalid @endif" value="{{ old('quantity', '1.00') }}" required>
                        @if ($inventoryErrorBag->has('quantity'))
                            <div class="form-error">{{ $inventoryErrorBag->first('quantity') }}</div>
                        @endif
                    </div>

                    <div class="outlet-form-group">
                        <label for="unit_cost">Actual Price</label>
                        <input id="unit_cost" name="unit_cost" type="number" min="0" step="0.01" class="outlet-input @if($inventoryErrorBag->has('unit_cost')) is-invalid @endif" value="{{ old('unit_cost', number_format((float) $product->amount, 2, '.', '')) }}" required>
                        @if ($inventoryErrorBag->has('unit_cost'))
                            <div class="form-error">{{ $inventoryErrorBag->first('unit_cost') }}</div>
                        @endif
                    </div>

                    <div class="outlet-form-group outlet-form-group-full">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="outlet-input @if($inventoryErrorBag->has('notes')) is-invalid @endif" rows="3" placeholder="Optional transaction note">{{ old('notes') }}</textarea>
                        @if ($inventoryErrorBag->has('notes'))
                            <div class="form-error">{{ $inventoryErrorBag->first('notes') }}</div>
                        @endif
                    </div>
                </div>

                <div class="outlet-form-actions">
                    <button type="submit" class="btn btn-primary">Save Transaction</button>
                </div>
            </form>
        </div>

        <div class="table-card" style="margin-top: 16px;">
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
                            <th>On Hand</th>
                            <th>Reserved</th>
                            <th>Unit</th>
                            <th>Unit Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($product->inventoryStocks as $stock)
                            <tr>
                                <td>{{ $stock->location?->name ?: '-' }}</td>
                                <td>{{ $stock->location?->type ?: '-' }}</td>
                                <td>{{ $stock->vendor?->name ?: '-' }}</td>
                                <td>{{ number_format((float) $stock->on_hand_qty, 2) }}</td>
                                <td>{{ number_format((float) $stock->reserved_qty, 2) }}</td>
                                <td>{{ $stock->unit?->symbol ?: ($stock->product?->defaultUnitLabel() ?: '-') }}</td>
                                <td>{{ number_format((float) $stock->unit_cost, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty">No inventory found for this product.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-card" style="margin-top: 16px;">
            <div class="table-header">
                <div class="table-title">Recent Transactions</div>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Qty</th>
                            <th>Unit Cost</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inventoryTransactions as $transaction)
                            @php
                                $item = $transaction->items->first();
                            @endphp
                            <tr>
                                <td>{{ $transaction->trx_date?->format('M d, Y h:i A') ?: '-' }}</td>
                                <td>{{ ucfirst((string) $transaction->trx_type) }}</td>
                                <td>{{ $transaction->fromLocation?->name ?: '-' }}</td>
                                <td>{{ $transaction->toLocation?->name ?: '-' }}</td>
                                <td>{{ $item ? number_format((float) $item->qty, 2) : '-' }}</td>
                                <td>{{ $item ? number_format((float) $item->unit_cost, 2) : '-' }}</td>
                                <td>{{ $transaction->creator?->name ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty">No transactions found for this product.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection

@section('page-specific-script')
<script>
    (function () {
        const tabButtons = Array.from(document.querySelectorAll('.js-product-tab'));
        const tabPanels = Array.from(document.querySelectorAll('.js-product-tab-panel'));
        const trxType = document.getElementById('trx_type');
        const location = document.getElementById('location_id');
        const fromLocation = document.getElementById('from_location_id');
        const toLocation = document.getElementById('to_location_id');
        const adjustmentType = document.getElementById('adjustment_type');
        const groupLocation = document.getElementById('group-location-id');
        const groupFrom = document.getElementById('group-from-location-id');
        const groupTo = document.getElementById('group-to-location-id');
        const groupAdjustment = document.getElementById('group-adjustment-type');

        const setActiveTab = (tab) => {
            tabButtons.forEach((button) => {
                const isActive = button.dataset.tabTarget === tab;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            tabPanels.forEach((panel) => {
                panel.hidden = panel.dataset.tabPanel !== tab;
            });
        };

        if (tabButtons.length > 0 && tabPanels.length > 0) {
            const hashTab = String(window.location.hash || '').replace('#', '');
            const inventoryHasErrors = @json($inventoryErrorBag->any());
            const initialTab = tabPanels.some((panel) => panel.dataset.tabPanel === hashTab)
                ? hashTab
                : (inventoryHasErrors ? 'inventory' : String(tabButtons[0].dataset.tabTarget || 'details'));

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

        const applyVisibility = () => {
            const selectedTrx = (trxType.value || '').toLowerCase();
            const isTransfer = selectedTrx === 'transfer';
            const isAdjustment = selectedTrx === 'adjustment';
            const needsSingleLocation = selectedTrx === 'in' || selectedTrx === 'out' || isAdjustment;

            toggleGroup(groupLocation, location, needsSingleLocation, needsSingleLocation);
            toggleGroup(groupFrom, fromLocation, isTransfer, isTransfer);
            toggleGroup(groupTo, toLocation, isTransfer, isTransfer);
            toggleGroup(groupAdjustment, adjustmentType, isAdjustment, false);

            if (!needsSingleLocation && location) {
                location.value = '';
            }
            if (!isTransfer && fromLocation) {
                fromLocation.value = '';
            }
            if (!isTransfer && toLocation) {
                toLocation.value = '';
            }
            if (!isAdjustment && adjustmentType) {
                adjustmentType.value = 'add';
            }
        };

        trxType.addEventListener('change', applyVisibility);
        applyVisibility();
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

    .js-product-tab-panel[hidden] {
        display: none !important;
    }
</style>
@endsection
