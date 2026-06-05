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

@php
    $query = trim((string) request('q', ''));
    $selectedProductId = (int) request('product_id', 0);
    $selectedLocationId = (int) request('location_id', 0);
@endphp

<div class="directory-reporting" style="margin-bottom: 16px;">
    <div class="directory-reporting__filter-bar">
        <div class="directory-reporting__filter-head">
            <h3 class="directory-reporting__filter-title">Filter Records</h3>
            @if ($query !== '' || $selectedProductId > 0 || $selectedLocationId > 0)
                <a href="{{ url()->current() }}" class="btn btn-light btn-sm">Clear Filters</a>
            @endif
        </div>

        <form method="GET" class="listing-filter-form">
            <div class="listing-filter-form__fields listing-filter-form__fields--triple">
                <div class="outlet-form-group listing-filter-form__field listing-filter-form__field--search">
                    <label for="q_filter">Search</label>
                    <input id="q_filter" type="text" name="q" class="outlet-input" value="{{ $query }}" placeholder="Search by product code, product name, or location...">
                </div>

                <div class="outlet-form-group listing-filter-form__field">
                    <label for="product_filter">Product</label>
                    <select id="product_filter" name="product_id" class="outlet-input js-product-filter-select">
                        <option value="">All Products</option>
                        @foreach ($productionProducts as $product)
                            <option value="{{ $product->id }}" data-code="{{ $product->code }}" @selected($selectedProductId === (int) $product->id)>
                                {{ $product->name }}@if($product->code) ({{ $product->code }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="outlet-form-group listing-filter-form__field">
                    <label for="location_filter">Location</label>
                    <select id="location_filter" name="location_id" class="outlet-input">
                        <option value="">All Locations</option>
                        @foreach ($stockFilterLocations as $location)
                            <option value="{{ $location->id }}" @selected($selectedLocationId === (int) $location->id)>
                                {{ $location->name }} ({{ $location->type }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="listing-filter-form__actions">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="{{ url()->current() }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

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
                                @if ($availableTransfer->targetProduct?->code)
                                    ({{ $availableTransfer->targetProduct->code }})
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
                    <label for="workflow_unit_cost">Unit Cost</label>
                    <input id="workflow_unit_cost" name="unit_cost" type="number" min="0" step="0.01" class="outlet-input" value="{{ old('unit_cost', '0.00') }}" required>
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
                    <th>Code</th>
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
                        <td>{{ $stock->product?->code ?: '-' }}</td>
                        <td>{{ number_format((float) $stock->on_hand_qty, 2) }}</td>
                        <td>{{ $stock->unit?->symbol ?: ($stock->product?->defaultUnitLabel() ?: '-') }}</td>
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
                                            data-code="{{ $stock->product?->code ?: '-' }}"
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
                                            data-code="{{ $stock->product?->code ?: '-' }}"
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
                    <th>Target Finished Good</th>
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
                        <td>{{ $transfer->targetProduct?->name ?: '-' }}</td>
                        <td>{{ number_format((float) ($transferItem?->qty ?? 0), 2) }}</td>
                        <td>{{ $transfer->fromLocation?->name ?: '-' }}</td>
                        <td>
                            <span class="app-badge {{ \App\Models\InventoryTransaction::statusBadgeClass((string) ($transfer->status ?: 'pending')) }}">
                                {{ \App\Models\InventoryTransaction::statusLabel((string) ($transfer->status ?: 'pending')) }}
                            </span>
                        </td>
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
                        <td colspan="8" class="empty">No production transfer records found.</td>
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
                                        data-code="{{ $item?->product?->code ?: '-' }}"
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
                        <td colspan="10" class="empty">No production records found.</td>
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
                        <option value="{{ $productionProduct->id }}" data-code="{{ $productionProduct->code }}">
                            {{ $productionProduct->name }} ({{ $productionProduct->code }})
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

    .listing-filter-form {
        display: grid;
        gap: 14px;
    }

    .listing-filter-form__fields {
        display: grid;
        grid-template-columns: minmax(280px, 1.4fr) repeat(3, minmax(200px, 1fr));
        gap: 12px;
        align-items: end;
    }

    .listing-filter-form__fields--triple {
        grid-template-columns: minmax(320px, 1.6fr) repeat(2, minmax(220px, 1fr));
    }

    .listing-filter-form__field {
        margin-bottom: 0;
    }

    .listing-filter-form__actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    @media (max-width: 992px) {
        .listing-filter-form__fields,
        .listing-filter-form__fields--triple {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .listing-filter-form__field--search {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 640px) {
        .listing-filter-form__fields,
        .listing-filter-form__fields--triple {
            grid-template-columns: 1fr;
        }

        .listing-filter-form__actions {
            flex-direction: column;
        }

        .listing-filter-form__actions .btn {
            width: 100%;
        }
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
        const productFilter = document.querySelector('.js-product-filter-select');

        const codeMatcher = (params, data) => {
            const term = String(params.term || '').trim().toLowerCase();
            if (!term) {
                return data;
            }
            const text = String(data.text || '').toLowerCase();
            const code = String(data.element?.dataset?.code || '').toLowerCase();
            return text.includes(term) || code.includes(term) ? data : null;
        };

        const bindExactCodeSelection = (selectEl) => {
            const $select = window.jQuery(selectEl);
            $select.on('select2:open.codeSelect', () => {
                window.setTimeout(() => {
                    const searchInput = document.querySelector('.select2-container--open .select2-search__field');
                    if (!searchInput) {
                        return;
                    }
                    const selectExactMatch = () => {
                        const term = String(searchInput.value || '').trim().toLowerCase();
                        if (!term) {
                            return;
                        }
                        const matchedOption = Array.from(selectEl.options || []).find((option) => {
                            const code = String(option.dataset.code || '').toLowerCase();
                            return code !== '' && code === term;
                        });
                        if (!matchedOption) {
                            return;
                        }
                        const newVal = String(matchedOption.value || '');
                        if (selectEl.multiple) {
                            const current = $select.val() || [];
                            if (!current.includes(newVal)) {
                                $select.val([...current, newVal]).trigger('change');
                            }
                        } else {
                            $select.val(newVal).trigger('change');
                        }
                        $select.select2('close');
                    };
                    searchInput.addEventListener('input', selectExactMatch);
                    searchInput.addEventListener('change', selectExactMatch);
                }, 0);
            });
        };

        const attachSelect2 = (selectEl, options = {}) => {
            if (!selectEl || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                return;
            }

            const $select = window.jQuery(selectEl);

            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            $select.select2({
                width: '100%',
                ...options,
            });
        };

        const initManufactureTargetProductSelect2 = () => {
            if (!targetProduct || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                return;
            }

            if (targetProduct.dataset.select2Ready === '1') {
                return;
            }

            attachSelect2(targetProduct, {
                placeholder: targetProduct.options[0]?.textContent?.trim() || 'Select Product',
                allowClear: !targetProduct.required,
                dropdownParent: window.jQuery(productionModal),
                matcher: codeMatcher,
            });
            bindExactCodeSelection(targetProduct);

            targetProduct.dataset.select2Ready = '1';
        };

        const initManufactureProductFilterSelect2 = () => {
            if (!productFilter || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                return;
            }

            if (productFilter.dataset.select2Ready === '1') {
                return;
            }

            attachSelect2(productFilter, {
                placeholder: productFilter.options[0]?.textContent?.trim() || 'All Products',
                allowClear: true,
                matcher: codeMatcher,
            });
            bindExactCodeSelection(productFilter);

            productFilter.dataset.select2Ready = '1';
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

        initManufactureTargetProductSelect2();
        initManufactureProductFilterSelect2();
        bindProductChange(targetProduct, () => {});

        document.querySelectorAll('.js-open-transfer-production-modal').forEach((button) => {
            button.addEventListener('click', () => {
                if (!productionModal || !productionForm || !productionQtyInput) {
                    return;
                }

                const action = String(button.dataset.action || '#');
                const maxQty = String(button.dataset.maxQty || '0.00');
                const material = String(button.dataset.material || '-');
                const code = String(button.dataset.code || '-');

                productionForm.setAttribute('action', action);
                productionQtyInput.setAttribute('max', maxQty);
                productionQtyInput.value = maxQty;
                if (targetProduct) {
                    targetProduct.value = '';
                    refreshSelect2(targetProduct);
                }
                if (productionItemMeta) {
                    productionItemMeta.textContent = 'Raw material: ' + material + ' (' + code + ')';
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
                const code = String(button.dataset.code || '-');

                outletForm.setAttribute('action', action);
                outletQtyInput.setAttribute('max', maxQty);
                outletQtyInput.value = maxQty;
                if (outletItemMeta) {
                    outletItemMeta.textContent = 'Produced item: ' + product + ' (' + code + ')';
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
                const code = String(button.dataset.code || '-');

                finalGoodsForm.setAttribute('action', action);
                finalGoodsQtyInput.setAttribute('max', maxQty);
                finalGoodsQtyInput.value = maxQty;
                if (finalGoodsDestination) {
                    finalGoodsDestination.value = '';
                }
                if (finalGoodsItemMeta) {
                    finalGoodsItemMeta.textContent = 'Final goods: ' + product + ' (' + code + ')';
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
