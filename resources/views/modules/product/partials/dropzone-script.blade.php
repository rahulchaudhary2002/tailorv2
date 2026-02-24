<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropzone = document.querySelector('[data-dropzone]');
    if (!dropzone) return;

    const input = dropzone.querySelector('[data-dropzone-input]');
    const browseButton = dropzone.querySelector('[data-dropzone-browse]');
    const surface = dropzone.querySelector('[data-dropzone-surface]');
    const previewGrid = dropzone.querySelector('[data-dropzone-preview]');
    const counter = dropzone.querySelector('[data-dropzone-counter]');

    if (!input || !browseButton || !surface || !previewGrid || !counter) return;

    const objectUrls = [];
    const supportsDataTransfer = typeof DataTransfer !== 'undefined';
    let canManageSelectedFiles = supportsDataTransfer;
    let selectedFiles = [];

    const clearObjectUrls = function () {
        objectUrls.forEach((url) => URL.revokeObjectURL(url));
        objectUrls.length = 0;
    };

    const keyOf = function (file) {
        return [file.name, file.size, file.lastModified].join('::');
    };

    const mergeUniqueFiles = function (base, incoming) {
        const existing = new Set(base.map(keyOf));
        incoming.forEach((file) => {
            const key = keyOf(file);
            if (existing.has(key)) return;
            existing.add(key);
            base.push(file);
        });
        return base;
    };

    const setInputFiles = function (files) {
        if (!supportsDataTransfer) return false;

        try {
            const transfer = new DataTransfer();
            files.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
            return true;
        } catch (error) {
            return false;
        }
    };

    const createPreviewCard = function (file, index) {
        const card = document.createElement('div');
        card.className = 'product-dropzone-preview-card';

        const mediaWrap = document.createElement('div');
        mediaWrap.className = 'product-dropzone-preview-media';

        const url = URL.createObjectURL(file);
        objectUrls.push(url);

        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = url;
            img.alt = file.name;
            img.className = 'product-dropzone-preview-file';
            mediaWrap.appendChild(img);
        } else {
            const video = document.createElement('video');
            video.src = url;
            video.className = 'product-dropzone-preview-file';
            video.muted = true;
            video.controls = true;
            mediaWrap.appendChild(video);
        }

        const meta = document.createElement('div');
        meta.className = 'product-dropzone-preview-meta';
        meta.innerHTML =
            '<span>' + file.name + '</span>' +
            '<small>' + Math.max(1, Math.ceil(file.size / 1024 / 1024)) + ' MB</small>';

        card.appendChild(mediaWrap);
        card.appendChild(meta);

        if (canManageSelectedFiles) {
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'btn btn-sm btn-outline-secondary product-dropzone-remove-btn';
            removeButton.innerHTML = '<i class="fas fa-times"></i> Remove';
            removeButton.addEventListener('click', function () {
                selectedFiles.splice(index, 1);

                if (!setInputFiles(selectedFiles)) {
                    canManageSelectedFiles = false;
                    selectedFiles = Array.from(input.files || []);
                }

                renderPreview();
            });

            card.appendChild(removeButton);
        }

        return card;
    };

    const renderPreview = function () {
        clearObjectUrls();
        previewGrid.innerHTML = '';

        counter.textContent = selectedFiles.length > 0
            ? selectedFiles.length + (selectedFiles.length === 1 ? ' file selected' : ' files selected')
            : 'No files selected yet';

        if (!canManageSelectedFiles && selectedFiles.length > 0) {
            counter.textContent += ' (remove and multi-select merge disabled in this browser)';
        }

        selectedFiles.forEach((file, index) => {
            previewGrid.appendChild(createPreviewCard(file, index));
        });
    };

    const addFiles = function (files) {
        const validFiles = [];

        files.forEach((file) => {
            if (!(file instanceof File)) return;
            if (!file.type.startsWith('image/') && !file.type.startsWith('video/')) return;
            validFiles.push(file);
        });

        if (validFiles.length === 0) return;

        if (canManageSelectedFiles) {
            selectedFiles = mergeUniqueFiles(selectedFiles, validFiles);

            if (!setInputFiles(selectedFiles)) {
                canManageSelectedFiles = false;
                selectedFiles = Array.from(input.files || []);
            }
        } else {
            selectedFiles = Array.from(input.files || []);
        }

        renderPreview();
    };

    browseButton.addEventListener('click', function () {
        if (browseButton.tagName.toLowerCase() === 'button') {
            input.click();
        }
    });

    input.addEventListener('change', function (event) {
        const picked = Array.from(event.target.files || []);

        if (canManageSelectedFiles && selectedFiles.length > 0) {
            addFiles(picked);
            return;
        }

        selectedFiles = picked;
        renderPreview();
    });

    ['dragenter', 'dragover'].forEach((name) => {
        surface.addEventListener(name, function (event) {
            event.preventDefault();
            event.stopPropagation();
            dropzone.classList.add('is-dragover');
        });
    });

    ['dragleave', 'drop'].forEach((name) => {
        surface.addEventListener(name, function (event) {
            event.preventDefault();
            event.stopPropagation();
            dropzone.classList.remove('is-dragover');
        });
    });

    surface.addEventListener('drop', function (event) {
        const droppedFiles = Array.from((event.dataTransfer && event.dataTransfer.files) || []);

        if (!canManageSelectedFiles) {
            counter.textContent = 'Drag-drop not supported in this browser. Use Select Files.';
            return;
        }

        addFiles(droppedFiles);
    });
});
</script>
