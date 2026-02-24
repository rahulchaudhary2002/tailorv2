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
            <label for="name">Vendor Name</label>
            <input
                id="name"
                name="name"
                type="text"
                class="outlet-input"
                value="{{ old('name', isset($vendor) ? $vendor->name : '') }}"
                placeholder="ABC Textiles"
                required
            >
        </div>

        <div class="outlet-form-group">
            <label for="vendor_type">Vendor Type</label>
            <input
                id="vendor_type"
                name="vendor_type"
                type="text"
                class="outlet-input"
                list="vendor-types-list"
                value="{{ old('vendor_type', isset($vendor) ? $vendor->vendorType?->name : '') }}"
                placeholder="China Vendors"
                required
            >
            <datalist id="vendor-types-list">
                @foreach ($vendorTypes as $vendorType)
                    <option value="{{ $vendorType->name }}"></option>
                @endforeach
            </datalist>
            <small>Select an existing type or enter a new one.</small>
        </div>

        <div class="outlet-form-group">
            <label for="contact_person">Contact Person</label>
            <input
                id="contact_person"
                name="contact_person"
                type="text"
                class="outlet-input"
                value="{{ old('contact_person', isset($vendor) ? $vendor->contact_person : '') }}"
                placeholder="Rahul Sharma"
            >
        </div>

        <div class="outlet-form-group">
            <label for="email">Email</label>
            <input
                id="email"
                name="email"
                type="email"
                class="outlet-input"
                value="{{ old('email', isset($vendor) ? $vendor->email : '') }}"
                placeholder="vendor@example.com"
            >
        </div>

        <div class="outlet-form-group">
            <label for="phone">Phone</label>
            <input
                id="phone"
                name="phone"
                type="text"
                class="outlet-input"
                value="{{ old('phone', isset($vendor) ? $vendor->phone : '') }}"
                placeholder="+977 90000 00000"
            >
        </div>

        <div class="outlet-form-group outlet-form-group-full">
            <label for="address">Address</label>
            <textarea
                id="address"
                name="address"
                class="outlet-input"
                rows="3"
                placeholder="Vendor address"
            >{{ old('address', isset($vendor) ? $vendor->address : '') }}</textarea>
        </div>
    </div>

    <div class="outlet-form-actions">
        <a href="{{ route('vendor.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</div>
