@php
    $existingMedia = $existingMedia ?? (isset($product) ? $product->mediaFiles()->orderBy('sort_order')->get() : collect());
    $existingVariants = $existingVariants ?? (isset($product) ? $product->variants()->orderBy('id')->get(['id', 'sku', 'size', 'color', 'material']) : collect());
    $variantRows = collect(old('variants', $existingVariants->map(fn ($variant) => [
        'sku' => $variant->sku,
        'size' => $variant->size,
        'color' => $variant->color,
        'material' => $variant->material,
    ])->all()));
@endphp

<div class="table-card outlet-form-card product-form-card">
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
            <label for="name">Product Name</label>
            <input
                id="name"
                name="name"
                type="text"
                class="outlet-input"
                value="{{ old('name', isset($product) ? $product->name : '') }}"
                placeholder="Premium Cotton Shirt"
                required
            >
        </div>

        <div class="outlet-form-group">
            <label for="sku">Base SKU</label>
            <input
                id="sku"
                name="sku"
                type="text"
                class="outlet-input"
                value="{{ old('sku', isset($product) ? $product->sku : '') }}"
                placeholder="SKU-1001"
                required
            >
            <small>Required even if product has no variants.</small>
        </div>

        <div class="outlet-form-group">
            <label for="product_category_id">Category</label>
            <select id="product_category_id" name="product_category_id" class="outlet-input" required>
                <option value="">Select Category</option>
                @foreach ($categories as $category)
                    <option
                        value="{{ $category->id }}"
                        @selected((string) old('product_category_id', isset($product) ? $product->product_category_id : '') === (string) $category->id)
                    >
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="outlet-form-group">
            <label for="unit_id">Measurement Unit</label>
            <select id="unit_id" name="unit_id" class="outlet-input" required>
                <option value="">Select Unit</option>
                @foreach ($units as $unit)
                    <option
                        value="{{ $unit->id }}"
                        @selected((string) old('unit_id', isset($product) ? $product->unit_id : '') === (string) $unit->id)
                    >
                        {{ $unit->name }}{{ $unit->symbol ? ' (' . $unit->symbol . ')' : '' }}
                    </option>
                @endforeach
            </select>
            <small>This unit is used consistently in inventory and purchases.</small>
        </div>

        <div class="outlet-form-group outlet-form-group-full">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                <label>Product Variants (Multiple SKUs)</label>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-variant-row">
                    <i class="fas fa-plus"></i> Add Variant
                </button>
            </div>
            <div class="table-container" style="margin-top: 10px;">
                <table class="table" id="variant-table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Size</th>
                            <th>Color</th>
                            <th>Material</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($variantRows as $index => $variant)
                            <tr class="variant-row">
                                <td>
                                    <input
                                        type="text"
                                        name="variants[{{ $index }}][sku]"
                                        class="outlet-input"
                                        value="{{ $variant['sku'] ?? '' }}"
                                        placeholder="SKU-BLK-M"
                                        required
                                    >
                                </td>
                                <td>
                                    <input
                                        type="text"
                                        name="variants[{{ $index }}][size]"
                                        class="outlet-input"
                                        value="{{ $variant['size'] ?? '' }}"
                                        placeholder="M"
                                    >
                                </td>
                                <td>
                                    <input
                                        type="text"
                                        name="variants[{{ $index }}][color]"
                                        class="outlet-input"
                                        value="{{ $variant['color'] ?? '' }}"
                                        placeholder="Black"
                                    >
                                </td>
                                <td>
                                    <input
                                        type="text"
                                        name="variants[{{ $index }}][material]"
                                        class="outlet-input"
                                        value="{{ $variant['material'] ?? '' }}"
                                        placeholder="Cotton"
                                    >
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger js-remove-variant-row">Remove</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <small>Optional. Use variants only where needed (color/size/material combinations).</small>
        </div>

        <div class="outlet-form-group outlet-form-group-full">
            <label for="description">Description</label>
            <textarea
                id="description"
                name="description"
                class="outlet-input"
                rows="3"
                placeholder="Optional product details"
            >{{ old('description', isset($product) ? $product->description : '') }}</textarea>
        </div>

        <div class="outlet-form-group outlet-form-group-full">
            <label for="media_files">Product Media</label>
            <div class="product-dropzone" data-dropzone>
                <input
                    id="media_files"
                    name="media_files[]"
                    type="file"
                    class="product-media-input"
                    accept="image/*,video/*"
                    multiple
                    data-dropzone-input
                >

                <div class="product-dropzone-surface" data-dropzone-surface>
                    <div class="product-dropzone-icon">
                        <i class="fas fa-cloud-arrow-up"></i>
                    </div>
                    <p class="product-dropzone-title">Drop files here or browse</p>
                    <p class="product-dropzone-subtitle">JPG, PNG, WEBP, GIF, MP4, MOV, AVI, WEBM, MKV</p>
                    <div class="product-media-constraints">
                        <span class="product-media-pill">Image up to 5MB</span>
                        <span class="product-media-pill">Video up to 100MB</span>
                        <span class="product-media-pill">Video max 30 seconds</span>
                    </div>
                    <label class="btn btn-outline-primary product-media-browse-btn" for="media_files" data-dropzone-browse>
                        <i class="fas fa-folder-open"></i>
                        <span>Select Files</span>
                    </label>
                    <p class="product-dropzone-counter" data-dropzone-counter>No files selected yet</p>
                </div>

                <div class="product-dropzone-preview-grid" data-dropzone-preview></div>
            </div>
        </div>

        @if (isset($product) && $existingMedia->isNotEmpty())
            <div class="outlet-form-group outlet-form-group-full">
                <label>Existing Media</label>
                <div class="product-media-grid">
                    @foreach ($existingMedia as $media)
                        <div class="product-media-card">
                            @if ($media->media_type === 'image')
                                <img
                                    src="{{ asset('storage/' . $media->file_path) }}"
                                    alt="Product media"
                                    class="product-media-preview"
                                >
                            @else
                                <video
                                    src="{{ asset('storage/' . $media->file_path) }}"
                                    class="product-media-preview"
                                    muted
                                    controls
                                ></video>
                            @endif

                            <div class="product-media-meta">
                                <span class="product-media-type">{{ strtoupper($media->media_type) }}</span>
                                @if ($media->duration_seconds !== null)
                                    <span class="product-media-duration">{{ number_format((float) $media->duration_seconds, 2) }}s</span>
                                @endif
                            </div>

                            <label class="product-media-remove">
                                <input type="checkbox" name="remove_media_ids[]" value="{{ $media->id }}">
                                <span>Remove on save</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="outlet-form-group outlet-form-group-full">
            <label for="is_active" class="product-active-toggle">
                <input
                    id="is_active"
                    name="is_active"
                    type="checkbox"
                    value="1"
                    @checked(old('is_active', isset($product) ? (int) $product->is_active : 1))
                >
                Active Product
            </label>
        </div>
    </div>

    <div class="outlet-form-actions">
        <a href="{{ route('product.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</div>

<script>
    (function () {
        const tableBody = document.querySelector('#variant-table tbody');
        const addButton = document.getElementById('add-variant-row');

        if (!tableBody || !addButton) {
            return;
        }

        const reindexRows = () => {
            const rows = tableBody.querySelectorAll('.variant-row');
            rows.forEach((row, index) => {
                row.querySelectorAll('input').forEach((input) => {
                    const name = input.getAttribute('name') || '';
                    const updated = name.replace(/variants\[\d+\]/, 'variants[' + index + ']');
                    input.setAttribute('name', updated);
                });
            });
        };

        const bindRemove = (row) => {
            const removeBtn = row.querySelector('.js-remove-variant-row');
            if (!removeBtn) {
                return;
            }

            removeBtn.addEventListener('click', () => {
                row.remove();
                reindexRows();
            });
        };

        tableBody.querySelectorAll('.variant-row').forEach(bindRemove);

        addButton.addEventListener('click', () => {
            const row = document.createElement('tr');
            row.className = 'variant-row';
                row.innerHTML = `
                    <td><input type="text" name="variants[0][sku]" class="outlet-input" placeholder="SKU-NEW" required></td>
                    <td><input type="text" name="variants[0][size]" class="outlet-input" placeholder="L"></td>
                    <td><input type="text" name="variants[0][color]" class="outlet-input" placeholder="Blue"></td>
                    <td><input type="text" name="variants[0][material]" class="outlet-input" placeholder="Cotton"></td>
                    <td><button type="button" class="btn btn-sm btn-danger js-remove-variant-row">Remove</button></td>
                `;

            tableBody.appendChild(row);
            bindRemove(row);
            reindexRows();
        });
    })();
</script>
