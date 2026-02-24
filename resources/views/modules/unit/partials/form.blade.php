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
            <label for="name">Unit Name</label>
            <input
                id="name"
                name="name"
                type="text"
                class="outlet-input"
                value="{{ old('name', isset($unit) ? $unit->name : '') }}"
                placeholder="Centimeter"
                required
            >
        </div>

        <div class="outlet-form-group">
            <label for="code">Unit Code</label>
            <input
                id="code"
                name="code"
                type="text"
                class="outlet-input"
                value="{{ old('code', isset($unit) ? $unit->code : '') }}"
                placeholder="CM"
                required
            >
        </div>

        <div class="outlet-form-group">
            <label for="symbol">Symbol</label>
            <input
                id="symbol"
                name="symbol"
                type="text"
                class="outlet-input"
                value="{{ old('symbol', isset($unit) ? $unit->symbol : '') }}"
                placeholder="cm"
            >
        </div>

        <div class="outlet-form-group outlet-form-group-full">
            <label for="description">Description</label>
            <textarea
                id="description"
                name="description"
                class="outlet-input"
                rows="3"
                placeholder="Used for body measurements."
            >{{ old('description', isset($unit) ? $unit->description : '') }}</textarea>
        </div>
    </div>

    <div class="outlet-form-actions">
        <a href="{{ route('unit.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</div>
