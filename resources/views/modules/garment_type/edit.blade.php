@extends('layouts.app')

@section('title', 'Edit Garment Type')


@section('content')
@php
$activeTab = request('tab', 'details');
@endphp

<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Edit Garment Type</h1>
        <p>Update garment type details and manage its measurement fields in tabs.</p>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="role-tabs">
    <button type="button" class="role-tab-btn {{ $activeTab === 'details' ? 'active' : '' }}" data-tab-target="details">Garment Type</button>
    <button type="button" class="role-tab-btn {{ $activeTab === 'measurements' ? 'active' : '' }}" data-tab-target="measurements">Measurements</button>
    <button type="button" class="role-tab-btn {{ $activeTab === 'tailoring' ? 'active' : '' }}" data-tab-target="tailoring">Tailoring Packages</button>
</div>

<div class="role-tab-pane {{ $activeTab === 'details' ? 'active' : '' }}" data-tab-pane="details">
    <form action="{{ route('garmentType.update', ['garmentType' => $garmentType, 'tab' => 'details']) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="table-card">
            <div class="table-header">
                <div class="table-title">Garment Type Information</div>
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

            <div class="role-form-grid">
                <div class="role-form-group role-form-group-full">
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" class="role-input" value="{{ old('title', $garmentType->title) }}" required>
                </div>
            </div>

            <div class="role-form-actions">
                <a href="{{ route('garmentType.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Garment Type</button>
            </div>
        </div>
    </form>
</div>

<div class="role-tab-pane {{ $activeTab === 'measurements' ? 'active' : '' }}" data-tab-pane="measurements">
    <div class="table-card" style="margin-bottom: 16px;">
        <div class="table-header">
            <div class="table-title">Add Measurement</div>
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

        <form action="{{ route('garmentType.measurement.store', ['garmentType' => $garmentType, 'tab' => 'measurements']) }}" method="POST">
            @csrf
            <div class="role-form-grid" style="padding: 16px;">
                <div class="role-form-group">
                    <label for="measurement_title">Measurement Title</label>
                    <input id="measurement_title" name="title" type="text" class="role-input" value="{{ old('title') }}" placeholder="Chest" required>
                </div>

                <div class="role-form-group">
                    <label for="measurement_unit_id">Unit</label>
                    <select id="measurement_unit_id" name="unit_id" class="role-input" required>
                        <option value="">Select Unit</option>
                        @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected((int) old('unit_id')===(int) $unit->id)>
                            {{ $unit->name }}{{ $unit->symbol ? ' (' . $unit->symbol . ')' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="role-form-group role-form-group-full">
                    <label for="measurement_order">Order</label>
                    <input id="measurement_order" name="order" type="number" min="1" class="role-input" value="{{ old('order', max(1, $garmentType->measurements->count() + 1)) }}" required>
                </div>
            </div>

            <div class="role-form-actions" style="padding: 0 16px 16px;">
                <button type="submit" class="btn btn-primary">Add Measurement</button>
            </div>
        </form>
    </div>

    <div class="table-card">
        <div class="table-header">
            <div class="table-title">Measurements</div>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Unit</th>
                        <th>Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($garmentType->measurements as $measurement)
                    <tr>
                        <form id="edit-form-{{ $measurement->id }}" action="{{ route('garmentType.measurement.update', ['garmentType' => $garmentType, 'measurement' => $measurement, 'tab' => 'measurements']) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <td>
                                <input type="text" name="title" class="role-input" value="{{ $measurement->title }}" required>
                            </td>
                            <td>
                                <select name="unit_id" class="role-input" required>
                                    @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}" @selected((int) $measurement->unit_id === (int) $unit->id)>
                                        {{ $unit->name }}{{ $unit->symbol ? ' (' . $unit->symbol . ')' : '' }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" min="1" name="order" class="role-input" value="{{ $measurement->order }}" required>
                            </td>
                        </form>
                        <td>
                            <div class="actions">
                                <button class="btn btn-sm btn-secondary" onclick="document.getElementById('edit-form-{{ $measurement->id }}').submit();">Update</button>
                                <form action="{{ route('garmentType.measurement.destroy', ['garmentType' => $garmentType, 'measurement' => $measurement, 'tab' => 'measurements']) }}" method="POST" onsubmit="return confirm('Delete this measurement?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="empty">No measurements found. Add one above.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="role-form-actions">
            <a href="{{ route('garmentType.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>
</div>

<div class="role-tab-pane {{ $activeTab === 'tailoring' ? 'active' : '' }}" data-tab-pane="tailoring">
    <div class="table-card" style="margin-bottom: 16px;">
        <div class="table-header">
            <div class="table-title">Add Tailoring Package</div>
        </div>

        @if ($errors->any() && $activeTab === 'tailoring')
        <div class="alert alert-danger">
            <strong>Please fix the following errors:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('garmentType.tailoringPackage.store', ['garmentType' => $garmentType, 'tab' => 'tailoring']) }}" method="POST">
            @csrf
            <div class="role-form-grid" style="padding: 16px;">
                <div class="role-form-group">
                    <label for="package_name">Package Name</label>
                    <input id="package_name" name="name" type="text" class="role-input" value="{{ old('name') }}" placeholder="Basic Stitching" required>
                </div>

                <div class="role-form-group">
                    <label for="package_amount">Amount (Per Piece)</label>
                    <input id="package_amount" name="amount" type="number" min="0" step="0.01" class="role-input" value="{{ old('amount') }}" placeholder="500.00" required>
                </div>

                <div class="role-form-group">
                    <label for="package_order">Order</label>
                    <input id="package_order" name="order" type="number" min="1" class="role-input" value="{{ old('order', max(1, $garmentType->tailoringPackages->count() + 1)) }}" required>
                </div>

                <div class="role-form-group">
                    <label for="package_active">Active</label>
                    <select id="package_active" name="is_active" class="role-input" required>
                        <option value="1" @selected(old('is_active', '1') === '1')>Yes</option>
                        <option value="0" @selected(old('is_active') === '0')>No</option>
                    </select>
                </div>

                <div class="role-form-group role-form-group-full">
                    <label for="package_description">Description</label>
                    <input id="package_description" name="description" type="text" class="role-input" value="{{ old('description') }}" placeholder="Standard finish">
                </div>
            </div>

            <div class="role-form-actions" style="padding: 0 16px 16px;">
                <button type="submit" class="btn btn-primary">Add Tailoring Package</button>
            </div>
        </form>
    </div>

    <div class="table-card">
        <div class="table-header">
            <div class="table-title">Tailoring Packages</div>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Amount (Per Piece)</th>
                        <th>Description</th>
                        <th>Order</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($garmentType->tailoringPackages as $package)
                    <tr>
                        <form id="edit-package-form-{{ $package->id }}" action="{{ route('garmentType.tailoringPackage.update', ['garmentType' => $garmentType, 'package' => $package, 'tab' => 'tailoring']) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <td>
                                <input type="text" name="name" class="role-input" value="{{ $package->name }}" required>
                            </td>
                            <td>
                                <input type="number" min="0" step="0.01" name="amount" class="role-input" value="{{ $package->amount }}" required>
                            </td>
                            <td>
                                <input type="text" name="description" class="role-input" value="{{ $package->description }}">
                            </td>
                            <td>
                                <input type="number" min="1" name="order" class="role-input" value="{{ $package->order }}" required>
                            </td>
                            <td>
                                <select name="is_active" class="role-input" required>
                                    <option value="1" @selected((bool) $package->is_active)>Yes</option>
                                    <option value="0" @selected(!(bool) $package->is_active)>No</option>
                                </select>
                            </td>
                        </form>
                        <td>
                            <div class="actions">
                                <button class="btn btn-sm btn-secondary" onclick="document.getElementById('edit-package-form-{{ $package->id }}').submit();">Update</button>
                                <form action="{{ route('garmentType.tailoringPackage.destroy', ['garmentType' => $garmentType, 'package' => $package, 'tab' => 'tailoring']) }}" method="POST" onsubmit="return confirm('Delete this tailoring package?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="empty">No tailoring packages found. Add one above.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="role-form-actions">
            <a href="{{ route('garmentType.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>
</div>
@endsection

@section('page-specific-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = Array.from(document.querySelectorAll('.role-tab-btn'));
        const panes = Array.from(document.querySelectorAll('.role-tab-pane'));

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
    });
</script>
@endsection
