@extends('layouts.app')

@section('title', 'Create Garment Type')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Create Garment Type</h1>
        <p>Create garment type first, then add its measurements and tailoring packages from the next tab.</p>
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
                <div class="role-form-group role-form-group-full">
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" class="role-input" value="{{ old('title') }}" placeholder="Shirt Stitching" required>
                </div>

                @php
                    $designNotes = old('design_note', []);
                    if (! is_array($designNotes)) {
                        $designNotes = [];
                    }
                @endphp

                <div class="role-form-group role-form-group-full">
                    <label for="design-note-input">Design Notes</label>
                    <style>
                        .design-note-input-wrap {
                            display: flex;
                            flex-wrap: wrap;
                            align-items: center;
                            gap: 8px;
                            min-height: 48px;
                            padding: 10px 12px;
                            border: 1px solid #d5deea;
                            border-radius: 10px;
                            background: #fff;
                        }

                        .design-note-input-wrap:focus-within {
                            border-color: #8a5a44;
                            box-shadow: 0 0 0 3px rgba(138, 90, 68, 0.1);
                        }

                        .design-note-chip {
                            display: inline-flex;
                            align-items: center;
                            gap: 8px;
                            padding: 6px 10px;
                            border-radius: 999px;
                            background: var(--secondary-light);
                            color: var(--primary);
                            font-size: 13px;
                            line-height: 1;
                        }

                        .design-note-chip-remove {
                            border: 0;
                            background: transparent;
                            color: inherit;
                            cursor: pointer;
                            padding: 0;
                            font-size: 14px;
                            line-height: 1;
                        }

                        .design-note-chip-input {
                            flex: 1 1 180px;
                            min-width: 180px;
                            border: 0;
                            outline: 0;
                            padding: 4px 0;
                            font: inherit;
                            color: #1f2d3d;
                            background: transparent;
                        }
                    </style>

                    <div class="design-note-input-wrap" id="design-note-editor">
                        <div id="design-note-chips" class="role-chip-list">
                            @foreach ($designNotes as $designNote)
                                @if (filled($designNote))
                                    <span class="design-note-chip" data-note="{{ $designNote }}">
                                        <span>{{ $designNote }}</span>
                                        <button type="button" class="design-note-chip-remove js-remove-design-note" aria-label="Remove design note">&times;</button>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                        <input id="design-note-input" type="text" class="design-note-chip-input" placeholder="Type a note and press Enter">
                    </div>
                    <div id="design-note-hidden-inputs">
                        @foreach ($designNotes as $designNote)
                            @if (filled($designNote))
                                <input type="hidden" name="design_note[]" value="{{ $designNote }}">
                            @endif
                        @endforeach
                    </div>
                    <p class="role-tab-note" style="margin-top: 8px;">Press Enter or comma to add each design note.</p>
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
        const designNoteEditor = document.getElementById('design-note-editor');
        const designNoteChips = document.getElementById('design-note-chips');
        const designNoteInput = document.getElementById('design-note-input');
        const designNoteHiddenInputs = document.getElementById('design-note-hidden-inputs');

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

        if (!designNoteEditor || !designNoteChips || !designNoteInput || !designNoteHiddenInputs) {
            return;
        }

        const getExistingNotes = () => Array.from(
            designNoteHiddenInputs.querySelectorAll('input[name="design_note[]"]')
        ).map((input) => input.value.toLowerCase());

        const removeNote = (note) => {
            const normalizedNote = note.toLowerCase();

            Array.from(designNoteHiddenInputs.querySelectorAll('input[name="design_note[]"]')).forEach((input) => {
                if (input.value.toLowerCase() === normalizedNote) {
                    input.remove();
                }
            });

            Array.from(designNoteChips.querySelectorAll('.design-note-chip')).forEach((chip) => {
                if ((chip.dataset.note || '').toLowerCase() === normalizedNote) {
                    chip.remove();
                }
            });
        };

        const addNote = (rawNote) => {
            const note = rawNote.trim();

            if (note === '' || getExistingNotes().includes(note.toLowerCase())) {
                return;
            }

            const chip = document.createElement('span');
            chip.className = 'design-note-chip';
            chip.dataset.note = note;
            chip.innerHTML = `
                <span></span>
                <button type="button" class="design-note-chip-remove js-remove-design-note" aria-label="Remove design note">&times;</button>
            `;
            chip.querySelector('span').textContent = note;

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'design_note[]';
            hiddenInput.value = note;

            designNoteChips.appendChild(chip);
            designNoteHiddenInputs.appendChild(hiddenInput);
            designNoteInput.value = '';
        };

        designNoteEditor.addEventListener('click', function () {
            designNoteInput.focus();
        });

        designNoteInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ',') {
                event.preventDefault();
                addNote(designNoteInput.value);
            } else if (event.key === 'Backspace' && designNoteInput.value === '') {
                const lastChip = designNoteChips.querySelector('.design-note-chip:last-child');
                if (lastChip) {
                    removeNote(lastChip.dataset.note || '');
                }
            }
        });

        designNoteInput.addEventListener('blur', function () {
            addNote(designNoteInput.value);
        });

        designNoteChips.addEventListener('click', function (event) {
            const removeButton = event.target.closest('.js-remove-design-note');

            if (!removeButton) {
                return;
            }

            const chip = removeButton.closest('.design-note-chip');
            if (chip) {
                removeNote(chip.dataset.note || '');
            }
        });
    });
</script>
@endsection
