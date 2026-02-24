@extends('layouts.app')

@section('title', 'Create Customer')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Create Customer</h1>
        <p>Create customer first, then add measurements in the next tab.</p>
    </div>
</div>

<div class="role-tabs">
    <button type="button" class="role-tab-btn active" data-tab-target="details">Create Customer</button>
    <button type="button" class="role-tab-btn" data-tab-target="measurements" disabled>Add Measurements</button>
</div>

<div class="role-tab-pane active" data-tab-pane="details">
    <form action="{{ route('customer.store') }}" method="POST">
        @csrf

        <div class="table-card">
            <div class="table-header">
                <div class="table-title">Customer Information</div>
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

            <div class="outlet-form-grid">
                <div class="outlet-form-group">
                    <label for="name">Customer Name</label>
                    <input id="name" name="name" type="text" class="outlet-input" value="{{ old('name') }}" placeholder="John Doe" required>
                </div>

                <div class="outlet-form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" class="outlet-input" value="{{ old('email') }}" placeholder="john@example.com" required>
                </div>

                <div class="outlet-form-group">
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" type="text" class="outlet-input" value="{{ old('phone') }}" placeholder="+1 555 123 4567" required>
                </div>

                <div class="outlet-form-group">
                    <label for="customer_type">Customer Type</label>
                    <select id="customer_type" name="customer_type" class="outlet-input" required>
                        <option value="retail" @selected(old('customer_type', 'retail') === 'retail')>Retail</option>
                        <option value="wholesale" @selected(old('customer_type') === 'wholesale')>Wholesale</option>
                        <option value="custom" @selected(old('customer_type') === 'custom')>Custom / VIP</option>
                    </select>
                </div>

                <div class="outlet-form-group outlet-form-group-full">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" class="outlet-input" rows="3" placeholder="123 Main Street, Cityville" required>{{ old('address') }}</textarea>
                </div>
            </div>

            <div class="outlet-form-actions">
                <a href="{{ route('customer.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Customer</button>
            </div>
        </div>
    </form>
</div>

<div class="role-tab-pane" data-tab-pane="measurements">
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">Add Measurements</div>
        </div>
        <p class="role-tab-note">Save customer details first. You will be redirected to edit page where you can add measurements.</p>
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
