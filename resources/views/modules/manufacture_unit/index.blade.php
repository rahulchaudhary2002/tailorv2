@extends('layouts.app')

@section('title', 'Manufacture Unit')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Warehouse & Manufacturing Stock</h1>
        <p>Track raw material quantities available in warehouse and manufacturing locations.</p>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 16px;">
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-number">{{ $stats['materials_count'] }}</div>
            <div class="stat-label">Raw Materials</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-number">{{ number_format((float) $stats['total_quantity'], 2) }}</div>
            <div class="stat-label">Total Quantity</div>
        </div>
    </div>
</div>

@include('includes.reporting-filter', ['paginator' => $stocks, 'placeholder' => 'Search by product, variant SKU, or location...', 'reporting' => $reporting])

<div class="app-tabs" role="tablist" aria-label="Manufacture unit sections">
    <button type="button" class="app-tab-button js-page-tab is-active" data-tab-target="stock-records" aria-selected="true">Stock Records</button>
    <button type="button" class="app-tab-button js-page-tab" data-tab-target="production-transfers" aria-selected="false">Production Transfers</button>
    <button type="button" class="app-tab-button js-page-tab" data-tab-target="production-log" aria-selected="false">Production Log</button>
</div>

<div class="table-card" style="margin-bottom: 16px;">
    <div class="table-header">
        <div class="table-title">Track Production Output</div>
    </div>

    @can('manage-manufacture-unit')
        <form action="{{ route('manufactureUnit.workflow.store') }}" method="POST" style="padding:16px;">
            @csrf
            <input type="hidden" name="tab" value="stock-records" class="js-active-tab-input">
            <div class="outlet-form-grid">
                <div class="outlet-form-group">
                    <label for="workflow_transfer_transaction_id">Transferred Goods For Production</label>
                    <select id="workflow_transfer_transaction_id" name="transfer_transaction_id" class="outlet-input" required>
                        <option value="">Select Transfer</option>
                        @foreach ($availableProductionTransfers as $availableTransfer)
                            <option value="{{ $availableTransfer->id }}" @selected((string) old('transfer_transaction_id') === (string) $availableTransfer->id)>
                                #{{ $availableTransfer->id }}
                                - {{ $availableTransfer->targetProduct?->name ?: 'N/A' }}
                                @if ($availableTransfer->targetVariant?->sku)
                                    ({{ $availableTransfer->targetVariant->sku }})
                                @endif
                                - {{ ucfirst($availableTransfer->status ?: 'pending') }}
                                - {{ $availableTransfer->trx_date?->format('M d, Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="outlet-form-group">
                    <label for="workflow_quantity">Produced Quantity</label>
                    <input id="workflow_quantity" name="quantity" type="number" min="0.01" step="0.01" class="outlet-input" value="{{ old('quantity', '1.00') }}" required>
                </div>

                <div class="outlet-form-group">
                    <label for="workflow_material_wastage_qty">Material Wastage Qty (Optional)</label>
                    <input id="workflow_material_wastage_qty" name="material_wastage_qty" type="number" min="0" step="0.01" class="outlet-input" value="{{ old('material_wastage_qty') }}">
                </div>

                <div class="outlet-form-group">
                    <label for="workflow_unit_cost">Unit Cost (Optional)</label>
                    <input id="workflow_unit_cost" name="unit_cost" type="number" min="0" step="0.01" class="outlet-input" value="{{ old('unit_cost') }}">
                </div>

                <div class="outlet-form-group">
                    <label for="workflow_base_price">Base Price</label>
                    <input id="workflow_base_price" name="base_price" type="number" min="0" step="0.01" class="outlet-input" value="{{ old('base_price', '0.00') }}" required>
                </div>

                <div class="outlet-form-group">
                    <label for="workflow_special_price">Special Price</label>
                    <input id="workflow_special_price" name="special_price" type="number" min="0" step="0.01" class="outlet-input" value="{{ old('special_price') }}">
                </div>

                <div class="outlet-form-group outlet-form-group-full">
                    <label for="workflow_notes">Notes</label>
                    <textarea id="workflow_notes" name="notes" class="outlet-input" rows="2" placeholder="Optional production notes">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="outlet-form-actions">
                <button type="submit" class="btn btn-primary">Record Production</button>
            </div>
        </form>
    @else
        <div style="padding:16px;">You do not have permission to record production output.</div>
    @endcan
</div>

<div class="js-page-tab-panel" data-tab-panel="stock-records">
<div class="table-card">
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

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-header">
        <div class="table-title">Stock Records</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Material</th>
                    <th>SKU</th>
                    <th>Available Quantity</th>
                    <th>Unit</th>
                    <th>Last Updated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stocks as $stock)
                    @php
                        $isFactoryStock = $stock->location?->type === \App\Models\InventoryLocation::TYPE_FACTORY;
                        $isFabricStock = $stock->product?->category?->slug === 'fabrics';
                        $canTransferFinalGoods = $isFactoryStock && !$isFabricStock;
                        $hasAvailableQuantity = (float) $stock->on_hand_qty > 0;
                    @endphp
                    <tr>
                        <td>{{ $stock->location?->name ?: '-' }}</td>
                        <td>{{ $stock->product?->name ?: '-' }}</td>
                        <td>{{ $stock->variant?->sku ?: ($stock->product?->sku ?: '-') }}</td>
                        <td>{{ number_format((float) $stock->on_hand_qty, 2) }}</td>
                        <td>{{ $stock->unit?->symbol ?: ($stock->unit?->name ?: '-') }}</td>
                        <td>{{ $stock->updated_at->format('M d, Y h:i A') }}</td>
                        <td>
                            @can('manage-manufacture-unit')
                                @if (!$hasAvailableQuantity)
                                    <span class="btn btn-sm btn-secondary" style="pointer-events:none; opacity:.75;">Transferred</span>
                                @else
                                    @if ($canTransferFinalGoods)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-secondary js-open-transfer-final-goods-modal"
                                            data-action="{{ route('manufactureUnit.finalGoods.transfer', $stock) }}"
                                            data-max-qty="{{ number_format((float) $stock->on_hand_qty, 2, '.', '') }}"
                                            data-product="{{ $stock->product?->name ?: '-' }}"
                                            data-sku="{{ $stock->variant?->sku ?: ($stock->product?->sku ?: '-') }}"
                                        >
                                            Transfer To Outlet/Warehouse
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-primary js-open-transfer-production-modal"
                                            data-action="{{ route('manufactureUnit.transfer', $stock) }}"
                                            data-max-qty="{{ number_format((float) $stock->on_hand_qty, 2, '.', '') }}"
                                            data-material="{{ $stock->product?->name ?: '-' }}"
                                            data-sku="{{ $stock->variant?->sku ?: ($stock->product?->sku ?: '-') }}"
                                        >
                                            Transfer for Production
                                        </button>
                                    @endif
                                @endif
                            @else
                                -
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty">No manufacture stock records found.</td>
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

<div class="js-page-tab-panel" data-tab-panel="production-transfers" hidden>
<div class="table-card" style="margin-top: 16px;">
    <div class="table-header">
        <div class="table-title">Transferred Goods For Production</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Raw Material</th>
                    <th>Variant</th>
                    <th>Target Finished Good</th>
                    <th>Target Variant</th>
                    <th>Qty</th>
                    <th>From Location</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($productionTransfers as $transfer)
                    @php
                        $transferItem = $transfer->items->first();
                    @endphp
                    <tr>
                        <td>{{ $transfer->trx_date?->format('M d, Y h:i A') ?: '-' }}</td>
                        <td>{{ $transferItem?->product?->name ?: '-' }}</td>
                        <td>{{ $transferItem?->variant?->sku ?: '-' }}</td>
                        <td>{{ $transfer->targetProduct?->name ?: '-' }}</td>
                        <td>{{ $transfer->targetVariant?->sku ?: '-' }}</td>
                        <td>{{ number_format((float) ($transferItem?->qty ?? 0), 2) }}</td>
                        <td>{{ $transfer->fromLocation?->name ?: '-' }}</td>
                        <td>{{ ucfirst($transfer->status ?: 'pending') }}</td>
                        <td>{{ $transfer->creator?->name ?: '-' }}</td>
                        <td>
                            @can('manage-manufacture-unit')
                                @if ($transfer->status === 'completed')
                                    <span class="btn btn-sm btn-secondary" style="pointer-events:none; opacity:.75;">Locked</span>
                                @else
                                    <form action="{{ route('manufactureUnit.transfer.status.update', $transfer) }}" method="POST" style="display:flex; gap:8px; align-items:center;">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="tab" value="production-transfers" class="js-active-tab-input">
                                        <select name="status" class="outlet-input" style="min-width:130px;">
                                            <option value="pending" @selected(($transfer->status ?: 'pending') === 'pending')>Pending</option>
                                            <option value="progress" @selected($transfer->status === 'progress')>Progress</option>
                                            <option value="completed" @selected($transfer->status === 'completed')>Completed</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-secondary">Update</button>
                                    </form>
                                @endif
                            @else
                                -
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="empty">No production transfer records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>

<div class="js-page-tab-panel" data-tab-panel="production-log" hidden>
<div class="table-card" style="margin-top: 16px;">
    <div class="table-header">
        <div class="table-title">Production Log</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Variant</th>
                    <th>Produced Qty</th>
                    <th>Material Wastage</th>
                    <th>Transferred Qty</th>
                    <th>Remaining Qty</th>
                    <th>To Location</th>
                    <th>Created By</th>
                    <th>Notes</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($productionLogs as $log)
                    @php
                        $item = $log->items->first();
                    @endphp
                    <tr>
                        <td>{{ $log->trx_date?->format('M d, Y h:i A') ?: '-' }}</td>
                        <td>{{ $item?->product?->name ?: '-' }}</td>
                        <td>{{ $item?->variant?->sku ?: '-' }}</td>
                        <td>{{ number_format((float) ($log->produced_qty ?? ($item?->qty ?? 0)), 2) }}</td>
                        <td>{{ number_format((float) ($log->material_wastage_qty ?? 0), 2) }}</td>
                        <td>{{ number_format((float) ($log->transferred_qty ?? 0), 2) }}</td>
                        <td>{{ number_format((float) ($log->remaining_qty ?? 0), 2) }}</td>
                        <td>{{ $log->toLocation?->name ?: '-' }}</td>
                        <td>{{ $log->creator?->name ?: '-' }}</td>
                        <td>{{ $log->notes ?: '-' }}</td>
                        <td>
                            @can('manage-manufacture-unit')
                                @if ((float) ($log->remaining_qty ?? 0) > 0)
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-primary js-open-transfer-outlet-modal"
                                        data-action="{{ route('manufactureUnit.productionOutput.transfer', $log) }}"
                                        data-max-qty="{{ number_format((float) ($log->remaining_qty ?? 0), 2, '.', '') }}"
                                        data-product="{{ $item?->product?->name ?: '-' }}"
                                        data-variant="{{ $item?->variant?->sku ?: '-' }}"
                                    >
                                        Transfer To Current Outlet
                                    </button>
                                @else
                                    <span class="btn btn-sm btn-secondary" style="pointer-events:none; opacity:.75;">Transferred</span>
                                @endif
                            @else
                                -
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="empty">No production records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>

@can('manage-manufacture-unit')
<div id="transferProductionModal" class="app-modal" aria-hidden="true">
    <div class="app-modal__backdrop js-modal-close"></div>
    <div class="app-modal__panel" role="dialog" aria-modal="true" aria-labelledby="transferProductionModalTitle">
        <div class="app-modal__header">
            <h3 id="transferProductionModalTitle">Transfer for Production</h3>
            <button type="button" class="btn btn-sm btn-secondary js-modal-close">Close</button>
        </div>
        <p class="app-modal__meta js-production-modal-item">-</p>
        <p class="app-modal__meta js-production-modal-max">Available Qty: 0.00</p>
        <form action="#" method="POST" class="js-transfer-production-form">
            @csrf
            <input type="hidden" name="tab" value="stock-records" class="js-active-tab-input">
            <div class="outlet-form-group" style="margin-bottom:8px;">
                <label>Target Finished Good</label>
                <select name="target_product_id" class="outlet-input js-target-product" required>
                    <option value="">Select Product</option>
                    @foreach ($productionProducts as $productionProduct)
                        <option value="{{ $productionProduct->id }}">
                            {{ $productionProduct->name }} ({{ $productionProduct->sku }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="outlet-form-group" style="margin-bottom:8px;">
                <label>Target Variant (Optional)</label>
                <select name="target_variant_id" class="outlet-input js-target-variant">
                    <option value="">No Variant</option>
                    @foreach ($productionProducts as $productionProduct)
                        @foreach ($productionProduct->variants as $productionVariant)
                            <option
                                value="{{ $productionVariant->id }}"
                                data-product-id="{{ $productionProduct->id }}"
                            >
                                {{ $productionVariant->sku }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>
            <div class="outlet-form-group" style="margin-bottom:8px;">
                <label>Quantity</label>
                <input
                    type="number"
                    name="quantity"
                    min="0.01"
                    step="0.01"
                    class="outlet-input js-production-qty"
                    required
                >
            </div>
            <div class="outlet-form-group" style="margin-bottom:8px;">
                <label>Notes</label>
                <input
                    type="text"
                    name="notes"
                    class="outlet-input"
                    placeholder="Optional production remark"
                >
            </div>
            <button type="submit" class="btn btn-sm btn-secondary">Submit Transfer</button>
        </form>
    </div>
</div>

<div id="transferFinalGoodsModal" class="app-modal" aria-hidden="true">
    <div class="app-modal__backdrop js-modal-close"></div>
    <div class="app-modal__panel" role="dialog" aria-modal="true" aria-labelledby="transferFinalGoodsModalTitle">
        <div class="app-modal__header">
            <h3 id="transferFinalGoodsModalTitle">Transfer Final Goods</h3>
            <button type="button" class="btn btn-sm btn-secondary js-modal-close">Close</button>
        </div>
        <p class="app-modal__meta js-final-goods-modal-item">-</p>
        <p class="app-modal__meta js-final-goods-modal-max">Max transferable: 0.00</p>
        <form action="#" method="POST" class="js-transfer-final-goods-form">
            @csrf
            <input type="hidden" name="tab" value="stock-records" class="js-active-tab-input">
            <div class="outlet-form-group" style="margin-bottom:8px;">
                <label>Destination</label>
                <select name="to_location_id" class="outlet-input js-final-goods-destination" required>
                    <option value="">Select Destination</option>
                    @foreach ($finalGoodsTransferLocations as $destinationLocation)
                        <option value="{{ $destinationLocation->id }}">
                            {{ $destinationLocation->name }} ({{ ucfirst($destinationLocation->type) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="outlet-form-group" style="margin-bottom:8px;">
                <label>Quantity</label>
                <input
                    type="number"
                    name="quantity"
                    min="0.01"
                    step="0.01"
                    class="outlet-input js-final-goods-qty"
                    required
                >
            </div>
            <div class="outlet-form-group" style="margin-bottom:8px;">
                <label>Notes</label>
                <input
                    type="text"
                    name="notes"
                    class="outlet-input"
                    placeholder="Optional transfer remark"
                >
            </div>
            <button type="submit" class="btn btn-sm btn-secondary">Transfer</button>
        </form>
    </div>
</div>

<div id="transferOutletModal" class="app-modal" aria-hidden="true">
    <div class="app-modal__backdrop js-modal-close"></div>
    <div class="app-modal__panel" role="dialog" aria-modal="true" aria-labelledby="transferOutletModalTitle">
        <div class="app-modal__header">
            <h3 id="transferOutletModalTitle">Transfer To Current Outlet</h3>
            <button type="button" class="btn btn-sm btn-secondary js-modal-close">Close</button>
        </div>
        <p class="app-modal__meta js-outlet-modal-item">-</p>
        <p class="app-modal__meta js-outlet-modal-max">Max transferable: 0.00</p>
        <form action="#" method="POST" class="js-transfer-outlet-form">
            @csrf
            <input type="hidden" name="tab" value="production-log" class="js-active-tab-input">
            <div class="outlet-form-group" style="margin-bottom:8px;">
                <label>Quantity</label>
                <input
                    type="number"
                    name="quantity"
                    min="0.01"
                    step="0.01"
                    class="outlet-input js-outlet-qty"
                    required
                >
            </div>
            <div class="outlet-form-group" style="margin-bottom:8px;">
                <label>Notes</label>
                <input
                    type="text"
                    name="notes"
                    class="outlet-input"
                    placeholder="Optional outlet transfer remark"
                >
            </div>
            <button type="submit" class="btn btn-sm btn-secondary">Transfer</button>
        </form>
    </div>
</div>
@endcan

@endsection

@section('page-specific-script')
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

    .app-modal {
        position: fixed;
        inset: 0;
        z-index: 1200;
        display: none;
    }

    .app-modal.is-open {
        display: block;
    }

    .app-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
    }

    .app-modal__panel {
        position: relative;
        width: min(520px, calc(100% - 32px));
        margin: 48px auto;
        padding: 16px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
    }

    .app-modal__header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .app-modal__header h3 {
        margin: 0;
        font-size: 1rem;
    }

    .app-modal__meta {
        margin: 0 0 8px;
        color: #475569;
        font-size: 0.85rem;
    }

    body.app-modal-open {
        overflow: hidden;
    }
</style>
<script>
    (function () {
        const body = document.body;
        const modals = Array.from(document.querySelectorAll('.app-modal'));
        const tabButtons = Array.from(document.querySelectorAll('.js-page-tab'));
        const tabPanels = Array.from(document.querySelectorAll('.js-page-tab-panel'));

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
                : String(tabButtons[0].dataset.tabTarget || 'stock-records');

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

        const closeModal = (modal) => {
            if (!modal) {
                return;
            }

            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            body.classList.remove('app-modal-open');
        };
        const openModal = (modal) => {
            if (!modal) {
                return;
            }

            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            body.classList.add('app-modal-open');
        };

        modals.forEach((modal) => {
            modal.querySelectorAll('.js-modal-close').forEach((closeButton) => {
                closeButton.addEventListener('click', () => closeModal(modal));
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            modals.forEach((modal) => {
                if (modal.classList.contains('is-open')) {
                    closeModal(modal);
                }
            });
        });

        const productionModal = document.getElementById('transferProductionModal');
        const productionForm = productionModal?.querySelector('.js-transfer-production-form');
        const productionQtyInput = productionModal?.querySelector('.js-production-qty');
        const productionItemMeta = productionModal?.querySelector('.js-production-modal-item');
        const productionMaxMeta = productionModal?.querySelector('.js-production-modal-max');
        const targetProduct = productionModal?.querySelector('.js-target-product');
        const targetVariant = productionModal?.querySelector('.js-target-variant');

        const initManufactureTargetProductSelect2 = () => {
            if (!targetProduct || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                return;
            }

            if (targetProduct.dataset.select2Ready === '1') {
                return;
            }

            window.jQuery(targetProduct).select2({
                width: '100%',
                placeholder: targetProduct.options[0]?.textContent?.trim() || 'Select Product',
                allowClear: !targetProduct.required,
                dropdownParent: window.jQuery(productionModal),
            });

            targetProduct.dataset.select2Ready = '1';
        };

        const refreshSelect2 = (selectEl) => {
            if (!selectEl || selectEl.dataset.select2Ready !== '1' || !window.jQuery) {
                return;
            }

            window.jQuery(selectEl).trigger('change.select2');
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

        const applyTransferVariantFilter = () => {
            if (!targetProduct || !targetVariant) {
                return;
            }

            const selectedProductId = String(targetProduct.value || '');
            const options = Array.from(targetVariant.options);

            options.forEach((option, index) => {
                if (index === 0) {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }

                const optionProductId = String(option.dataset.productId || '');
                const show = selectedProductId !== '' && optionProductId === selectedProductId;
                option.hidden = !show;
                option.disabled = !show;
            });

            const selected = targetVariant.options[targetVariant.selectedIndex];
            if (selected && (selected.hidden || selected.disabled)) {
                targetVariant.value = '';
            }
        };

        bindProductChange(targetProduct, applyTransferVariantFilter);
        initManufactureTargetProductSelect2();
        applyTransferVariantFilter();

        document.querySelectorAll('.js-open-transfer-production-modal').forEach((button) => {
            button.addEventListener('click', () => {
                if (!productionModal || !productionForm || !productionQtyInput) {
                    return;
                }

                const action = String(button.dataset.action || '#');
                const maxQty = String(button.dataset.maxQty || '0.00');
                const material = String(button.dataset.material || '-');
                const sku = String(button.dataset.sku || '-');

                productionForm.setAttribute('action', action);
                productionQtyInput.setAttribute('max', maxQty);
                productionQtyInput.value = maxQty;
                if (targetProduct) {
                    targetProduct.value = '';
                    refreshSelect2(targetProduct);
                }
                if (targetVariant) {
                    targetVariant.value = '';
                }
                applyTransferVariantFilter();
                if (productionItemMeta) {
                    productionItemMeta.textContent = 'Raw material: ' + material + ' (' + sku + ')';
                }
                if (productionMaxMeta) {
                    productionMaxMeta.textContent = 'Available Qty: ' + maxQty;
                }

                openModal(productionModal);
            });
        });

        const outletModal = document.getElementById('transferOutletModal');
        const outletForm = outletModal?.querySelector('.js-transfer-outlet-form');
        const outletQtyInput = outletModal?.querySelector('.js-outlet-qty');
        const outletItemMeta = outletModal?.querySelector('.js-outlet-modal-item');
        const outletMaxMeta = outletModal?.querySelector('.js-outlet-modal-max');

        document.querySelectorAll('.js-open-transfer-outlet-modal').forEach((button) => {
            button.addEventListener('click', () => {
                if (!outletModal || !outletForm || !outletQtyInput) {
                    return;
                }

                const action = String(button.dataset.action || '#');
                const maxQty = String(button.dataset.maxQty || '0.00');
                const product = String(button.dataset.product || '-');
                const variant = String(button.dataset.variant || '-');

                outletForm.setAttribute('action', action);
                outletQtyInput.setAttribute('max', maxQty);
                outletQtyInput.value = maxQty;
                if (outletItemMeta) {
                    outletItemMeta.textContent = 'Produced item: ' + product + ' (' + variant + ')';
                }
                if (outletMaxMeta) {
                    outletMaxMeta.textContent = 'Max transferable: ' + maxQty;
                }

                openModal(outletModal);
            });
        });

        const finalGoodsModal = document.getElementById('transferFinalGoodsModal');
        const finalGoodsForm = finalGoodsModal?.querySelector('.js-transfer-final-goods-form');
        const finalGoodsQtyInput = finalGoodsModal?.querySelector('.js-final-goods-qty');
        const finalGoodsDestination = finalGoodsModal?.querySelector('.js-final-goods-destination');
        const finalGoodsItemMeta = finalGoodsModal?.querySelector('.js-final-goods-modal-item');
        const finalGoodsMaxMeta = finalGoodsModal?.querySelector('.js-final-goods-modal-max');

        document.querySelectorAll('.js-open-transfer-final-goods-modal').forEach((button) => {
            button.addEventListener('click', () => {
                if (!finalGoodsModal || !finalGoodsForm || !finalGoodsQtyInput) {
                    return;
                }

                const action = String(button.dataset.action || '#');
                const maxQty = String(button.dataset.maxQty || '0.00');
                const product = String(button.dataset.product || '-');
                const sku = String(button.dataset.sku || '-');

                finalGoodsForm.setAttribute('action', action);
                finalGoodsQtyInput.setAttribute('max', maxQty);
                finalGoodsQtyInput.value = maxQty;
                if (finalGoodsDestination) {
                    finalGoodsDestination.value = '';
                }
                if (finalGoodsItemMeta) {
                    finalGoodsItemMeta.textContent = 'Final goods: ' + product + ' (' + sku + ')';
                }
                if (finalGoodsMaxMeta) {
                    finalGoodsMaxMeta.textContent = 'Max transferable: ' + maxQty;
                }

                openModal(finalGoodsModal);
            });
        });
    })();
</script>
@endsection
