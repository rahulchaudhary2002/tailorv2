@extends('layouts.app')

@section('title', 'Create Garment Type')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Create Garment Type</h1>
        <p>Create garment type first, then add its measurements from the next tab.</p>
    </div>
</div>

<div class="role-tabs">
    <button type="button" class="role-tab-btn active" data-tab-target="details">Create Garment Type</button>
    <button type="button" class="role-tab-btn" data-tab-target="measurements" disabled>Add Measurements</button>
</div>

<div class="role-tab-pane active" data-tab-pane="details">
    <form action="{{ route('garmentType.store') }}" method="POST">
        @csrf

        <div class="table-card">
            <div class="table-header">
                <div class="table-title">Garment Type Information</div>
            </div>

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

            <div class="role-form-grid">
                <div class="role-form-group">
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" class="role-input" value="{{ old('title') }}" placeholder="Shirt Stitching" required>
                </div>

                <div class="role-form-group">
                    <label for="amount">Amount</label>
                    <input id="amount" name="amount" type="number" step="0.01" min="0" class="role-input" value="{{ old('amount') }}" placeholder="500.00" required>
                </div>

                <div class="role-form-group role-form-group-full">
                    <label for="tax">Tax (%)</label>
                    <input id="tax" name="tax" type="number" step="0.01" min="0" class="role-input" value="{{ old('tax') }}" placeholder="18.00" required>
                </div>
            </div>

            <div class="role-form-actions">
                <a href="{{ route('garmentType.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Garment Type</button>
            </div>
        </div>
    </form>
</div>

<div class="role-tab-pane" data-tab-pane="measurements">
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">Add Measurements</div>
        </div>
        <p class="role-tab-note">Save garment type details first. Then you will be redirected to the edit page where you can add measurements.</p>
    </div>
</div>
@endsection

@section('page-specific-script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
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
            button.addEventListener('click', function () {
                if (button.disabled) {
                    return;
                }
                activateTab(button.dataset.tabTarget);
            });
        });
    });
</script>
@endsection
