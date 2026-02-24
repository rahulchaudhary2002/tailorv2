@extends('layouts.app')

@section('title', 'Edit Customer')

@section('page-specific-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
@endsection

@section('content')
@php
$activeTab = old('active_tab', request('tab', 'details'));
$selectedGarmentTypeIds = collect(old('garment_type_ids', $customer->customerGarmentTypes->pluck('garment_type_id')->all()))
    ->map(fn ($id) => (string) $id)
    ->all();
$defaultMeasurementRows = $customer->customerGarmentTypes
    ->flatMap(function ($customerGarmentType) {
        return $customerGarmentType->measurements->map(function ($measurement) use ($customerGarmentType) {
            return [
                'garment_type_id' => (string) $customerGarmentType->garment_type_id,
                'type' => (string) $measurement->type,
                'measurement' => (string) $measurement->measurement,
                'unit' => (string) $measurement->unit,
            ];
        });
    })
    ->values()
    ->all();
$measurementRows = collect(old('measurements', $defaultMeasurementRows))
    ->map(function ($row) {
        return [
            'garment_type_id' => (string) ($row['garment_type_id'] ?? ''),
            'type' => (string) ($row['type'] ?? ''),
            'measurement' => (string) ($row['measurement'] ?? ''),
            'unit' => (string) ($row['unit'] ?? ''),
        ];
    })
    ->values();
$garmentTypeTemplates = $garmentTypes->map(function ($garmentType) {
    return [
        'id' => (string) $garmentType->id,
        'title' => $garmentType->title,
        'measurements' => $garmentType->measurements->map(function ($measurement) {
            return [
                'type' => $measurement->title,
                'unit' => $measurement->unit?->symbol ?: ($measurement->unit?->name ?: ''),
            ];
        })->values(),
    ];
})->values();
@endphp

<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Edit Customer</h1>
        <p>Update customer details and measurements in separate forms.</p>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="role-tabs">
    <button type="button" class="role-tab-btn {{ $activeTab === 'details' ? 'active' : '' }}" data-tab-target="details">Customer Details</button>
    <button type="button" class="role-tab-btn {{ $activeTab === 'measurements' ? 'active' : '' }}" data-tab-target="measurements">Measurements</button>
</div>

<div class="role-tab-pane {{ $activeTab === 'details' ? 'active' : '' }}" data-tab-pane="details">
    <form action="{{ route('customer.update', ['customer' => $customer, 'tab' => 'details']) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="active_tab" value="details">

        <div class="table-card">
            <div class="table-header">
                <div class="table-title">Customer Information</div>
            </div>

            @if ($errors->any() && $activeTab === 'details')
            <div class="alert alert-danger">
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
                    <label for="name">Customer Name</label>
                    <input id="name" name="name" type="text" class="outlet-input" value="{{ old('name', $customer->name) }}" placeholder="John Doe" required>
                </div>

                <div class="outlet-form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" class="outlet-input" value="{{ old('email', $customer->email) }}" placeholder="john@example.com" required>
                </div>

                <div class="outlet-form-group">
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" type="text" class="outlet-input" value="{{ old('phone', $customer->phone) }}" placeholder="+1 555 123 4567" required>
                </div>

                <div class="outlet-form-group">
                    <label for="customer_type">Customer Type</label>
                    <select id="customer_type" name="customer_type" class="outlet-input" required>
                        <option value="retail" @selected(old('customer_type', $customer->customer_type) === 'retail')>Retail</option>
                        <option value="wholesale" @selected(old('customer_type', $customer->customer_type) === 'wholesale')>Wholesale</option>
                        <option value="custom" @selected(old('customer_type', $customer->customer_type) === 'custom')>Custom / VIP</option>
                    </select>
                </div>

                <div class="outlet-form-group outlet-form-group-full">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" class="outlet-input" rows="3" placeholder="123 Main Street, Cityville" required>{{ old('address', $customer->address) }}</textarea>
                </div>
            </div>

            <div class="outlet-form-actions">
                <a href="{{ route('customer.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Details</button>
            </div>
        </div>
    </form>
</div>

<div class="role-tab-pane {{ $activeTab === 'measurements' ? 'active' : '' }}" data-tab-pane="measurements">
    <form action="{{ route('customer.updateMeasurements', ['customer' => $customer, 'tab' => 'measurements']) }}" method="POST" id="customer-measurement-form">
        @csrf
        @method('PUT')
        <input type="hidden" name="active_tab" value="measurements">

        <div class="table-card">
            <div class="table-header">
                <div class="table-title">Customer Measurements</div>
            </div>

            @if ($errors->any() && $activeTab === 'measurements')
            <div class="alert alert-danger">
                <strong>Please fix the following errors:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="role-form-grid" style="padding: 16px;">
                <div class="role-form-group role-form-group-full">
                    <label for="garment_type_ids">Garment Types</label>
                    <select id="garment_type_ids" name="garment_type_ids[]" class="role-input js-garment-types-select" multiple>
                        @foreach ($garmentTypes as $garmentType)
                        <option value="{{ $garmentType->id }}" @selected(in_array((string) $garmentType->id, $selectedGarmentTypeIds, true))>
                            {{ $garmentType->title }}
                        </option>
                        @endforeach
                    </select>
                    <p class="role-tab-note" style="margin-top: 8px;">Selecting garment types auto-loads default measurement rows. You can edit values, add custom rows, and remove rows.</p>
                </div>
            </div>

            <div class="customer-measurement-toolbar mb-3">
                <button type="button" class="btn btn-secondary" id="add-measurement-row">Add Measurement Row</button>
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Garment Type</th>
                            <th>Type</th>
                            <th>Measurement</th>
                            <th>Unit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="customer-measurement-rows">
                        @foreach ($measurementRows as $index => $row)
                        <tr class="js-measurement-row">
                            <td>
                                <select class="role-input js-measurement-garment-type" name="measurements[{{ $index }}][garment_type_id]" required>
                                    <option value="">Select Garment Type</option>
                                    @foreach ($garmentTypes as $garmentType)
                                    <option value="{{ $garmentType->id }}" @selected($row['garment_type_id'] === (string) $garmentType->id)>
                                        {{ $garmentType->title }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" class="role-input js-measurement-type" name="measurements[{{ $index }}][type]" value="{{ $row['type'] }}" placeholder="Chest" required>
                            </td>
                            <td>
                                <input type="text" class="role-input js-measurement-value" name="measurements[{{ $index }}][measurement]" value="{{ $row['measurement'] }}" placeholder="40" required>
                            </td>
                            <td>
                                <input type="text" class="role-input js-measurement-unit" name="measurements[{{ $index }}][unit]" value="{{ $row['unit'] }}" placeholder="inch" required>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger js-remove-measurement-row">Remove</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="outlet-form-actions">
                <a href="{{ route('customer.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Measurements</button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('page-specific-script')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = Array.from(document.querySelectorAll('.role-tab-btn'));
        const panes = Array.from(document.querySelectorAll('.role-tab-pane'));
        const garmentTypeSelect = document.getElementById('garment_type_ids');
        const addRowButton = document.getElementById('add-measurement-row');
        const rowsContainer = document.getElementById('customer-measurement-rows');

        const garmentTypeTemplates = @json($garmentTypeTemplates);
        const garmentTypeMap = Object.fromEntries(garmentTypeTemplates.map((item) => [item.id, item]));

        const activateTab = (tabName) => {
            tabButtons.forEach((button) => {
                button.classList.toggle('active', button.dataset.tabTarget === tabName);
            });

            panes.forEach((pane) => {
                pane.classList.toggle('active', pane.dataset.tabPane === tabName);
            });
        };

        tabButtons.forEach((button) => {
            button.addEventListener('click', function() {
                activateTab(button.dataset.tabTarget);
            });
        });

        if (window.Choices) {
            new Choices(garmentTypeSelect, {
                removeItemButton: true,
                searchEnabled: true,
                searchResultLimit: 30,
                placeholder: true,
                placeholderValue: 'Select garment types',
                itemSelectText: '',
                shouldSort: false,
            });
        }

        const getSelectedGarmentTypeIds = () => Array.from(garmentTypeSelect.selectedOptions).map((option) => option.value);

        const makeGarmentTypeOptions = (selectedId = '') => {
            const selectedIds = getSelectedGarmentTypeIds();
            let html = '<option value="">Select Garment Type</option>';

            selectedIds.forEach((id) => {
                const selectedAttr = selectedId === id ? ' selected' : '';
                const title = garmentTypeMap[id] ? garmentTypeMap[id].title : 'Unknown';
                html += `<option value="${id}"${selectedAttr}>${title}</option>`;
            });

            return html;
        };

        const reindexRows = () => {
            const rows = Array.from(rowsContainer.querySelectorAll('.js-measurement-row'));

            rows.forEach((row, index) => {
                row.querySelector('.js-measurement-garment-type').name = `measurements[${index}][garment_type_id]`;
                row.querySelector('.js-measurement-type').name = `measurements[${index}][type]`;
                row.querySelector('.js-measurement-value').name = `measurements[${index}][measurement]`;
                row.querySelector('.js-measurement-unit').name = `measurements[${index}][unit]`;
            });
        };

        const toggleEmptyState = () => {
            const hasRows = rowsContainer.querySelector('.js-measurement-row') !== null;
            const emptyRow = rowsContainer.querySelector('.js-empty-row');

            if (!hasRows && !emptyRow) {
                const tr = document.createElement('tr');
                tr.className = 'js-empty-row';
                tr.innerHTML = '<td colspan="5" class="empty">Select garment types to load measurements, or add a row manually.</td>';
                rowsContainer.appendChild(tr);
            }

            if (hasRows && emptyRow) {
                emptyRow.remove();
            }
        };

        const createRow = ({
            garment_type_id = '',
            type = '',
            measurement = '',
            unit = ''
        } = {}) => {
            const row = document.createElement('tr');
            row.className = 'js-measurement-row';
            row.innerHTML = `
                <td><select class="role-input js-measurement-garment-type" required></select></td>
                <td><input type="text" class="role-input js-measurement-type" placeholder="Chest" required></td>
                <td><input type="text" class="role-input js-measurement-value" placeholder="40" required></td>
                <td><input type="text" class="role-input js-measurement-unit" placeholder="inch" required></td>
                <td><button type="button" class="btn btn-sm btn-danger js-remove-measurement-row">Remove</button></td>
            `;

            const garmentTypeField = row.querySelector('.js-measurement-garment-type');
            garmentTypeField.innerHTML = makeGarmentTypeOptions(garment_type_id);

            row.querySelector('.js-measurement-type').value = type;
            row.querySelector('.js-measurement-value').value = measurement;
            row.querySelector('.js-measurement-unit').value = unit;

            rowsContainer.appendChild(row);
            reindexRows();
            toggleEmptyState();
        };

        const applySelectedGarmentTypesToRows = () => {
            const selectedIds = getSelectedGarmentTypeIds();
            const rows = Array.from(rowsContainer.querySelectorAll('.js-measurement-row'));

            rows.forEach((row) => {
                const garmentTypeField = row.querySelector('.js-measurement-garment-type');
                const currentValue = garmentTypeField.value;

                if (!selectedIds.includes(currentValue)) {
                    row.remove();
                    return;
                }

                garmentTypeField.innerHTML = makeGarmentTypeOptions(currentValue);
            });

            const existingKeys = new Set(
                Array.from(rowsContainer.querySelectorAll('.js-measurement-row')).map((row) => {
                    const garmentTypeId = row.querySelector('.js-measurement-garment-type').value;
                    const typeValue = row.querySelector('.js-measurement-type').value.trim().toLowerCase();
                    return `${garmentTypeId}::${typeValue}`;
                })
            );

            selectedIds.forEach((garmentTypeId) => {
                const template = garmentTypeMap[garmentTypeId];

                if (!template) {
                    return;
                }

                template.measurements.forEach((measurementTemplate) => {
                    const typeValue = String(measurementTemplate.type || '').trim();

                    if (typeValue === '') {
                        return;
                    }

                    const dedupeKey = `${garmentTypeId}::${typeValue.toLowerCase()}`;

                    if (existingKeys.has(dedupeKey)) {
                        return;
                    }

                    createRow({
                        garment_type_id: garmentTypeId,
                        type: typeValue,
                        unit: String(measurementTemplate.unit || ''),
                    });

                    existingKeys.add(dedupeKey);
                });
            });

            reindexRows();
            toggleEmptyState();
            addRowButton.disabled = selectedIds.length === 0;
        };

        addRowButton.addEventListener('click', function() {
            const selectedIds = getSelectedGarmentTypeIds();

            if (selectedIds.length === 0) {
                return;
            }

            createRow({
                garment_type_id: selectedIds[0],
            });
        });

        rowsContainer.addEventListener('click', function(event) {
            const removeButton = event.target.closest('.js-remove-measurement-row');

            if (!removeButton) {
                return;
            }

            removeButton.closest('.js-measurement-row').remove();
            reindexRows();
            toggleEmptyState();
        });

        garmentTypeSelect.addEventListener('change', applySelectedGarmentTypesToRows);

        applySelectedGarmentTypesToRows();
        activateTab('{{ $activeTab }}');
    });
</script>
@endsection
