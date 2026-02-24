<div class="table-card outlet-form-card">
    <div class="table-header">
        <div class="table-title">{{ $title }}</div>
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
            <input
                id="name"
                name="name"
                type="text"
                class="outlet-input"
                value="{{ old('name', isset($customer) ? $customer->name : '') }}"
                placeholder="John Doe"
                required
            >
        </div>

        <div class="outlet-form-group">
            <label for="email">Email</label>
            <input
                id="email"
                name="email"
                type="email"
                class="outlet-input"
                value="{{ old('email', isset($customer) ? $customer->email : '') }}"
                placeholder="john@example.com"
                required
            >
        </div>

        <div class="outlet-form-group">
            <label for="phone">Phone</label>
            <input
                id="phone"
                name="phone"
                type="text"
                class="outlet-input"
                value="{{ old('phone', isset($customer) ? $customer->phone : '') }}"
                placeholder="+1 555 123 4567"
                required
            >
        </div>

        <div class="outlet-form-group outlet-form-group-full">
            <label for="address">Address</label>
            <textarea
                id="address"
                name="address"
                class="outlet-input"
                rows="3"
                placeholder="123 Main Street, Cityville"
                required
            >{{ old('address', isset($customer) ? $customer->address : '') }}</textarea>
        </div>
    </div>

    <div class="outlet-form-actions">
        <a href="{{ route('customer.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</div>
