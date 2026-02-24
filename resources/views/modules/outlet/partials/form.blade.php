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
            <label for="name">Outlet Name</label>
            <input
                id="name"
                name="name"
                type="text"
                class="outlet-input"
                value="{{ old('name', isset($outlet) ? $outlet->name : '') }}"
                placeholder="Main Outlet"
                required
            >
        </div>

        <div class="outlet-form-group">
            <label for="code">Outlet Code</label>
            <input
                id="code"
                name="code"
                type="text"
                class="outlet-input"
                value="{{ old('code', isset($outlet) ? $outlet->code : '') }}"
                placeholder="MAIN001"
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
            >{{ old('address', isset($outlet) ? $outlet->address : '') }}</textarea>
        </div>
    </div>

    <div class="outlet-form-actions">
        <a href="{{ route('outlet.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</div>
