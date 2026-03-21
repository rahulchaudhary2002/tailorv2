@extends('layouts.app')

@section('title', 'Edit Role')

@section('page-specific-style')
<style>
    .role-edit-mobile {
        display: none;
    }

    .role-edit-mobile-shell {
        width: 100%;
        padding-bottom: 108px;
    }

    .role-edit-mobile-hero {
        margin-bottom: 22px;
    }

    .role-edit-mobile-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 14px;
        border-radius: 999px;
        background: #8ef1e3;
        color: #046c5d;
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        margin-bottom: 14px;
    }

    .role-edit-mobile-chip--fixed {
        background: #f0ece9;
        color: #7d6f66;
    }

    .role-edit-mobile-hero h1 {
        margin: 0;
        font-size: clamp(2.2rem, 4vw, 3.4rem);
        line-height: 0.96;
        letter-spacing: -0.06em;
        color: #1b1715;
    }

    .role-edit-mobile-hero p {
        max-width: 740px;
        margin: 12px 0 0;
        color: #5f5249;
        font-size: 1rem;
        line-height: 1.55;
    }

    .role-edit-mobile-tabs {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0;
        margin-bottom: 26px;
        padding: 4px;
        border-radius: 18px;
        background: #f1efec;
        border: 1px solid #ebe2db;
    }

    .role-edit-mobile-tab {
        min-height: 54px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        color: #73675e;
        background: transparent;
        border: 0;
    }

    .role-edit-mobile-tab.is-active {
        background: #fff;
        color: #734b36;
        box-shadow: 0 12px 24px rgba(24, 18, 13, 0.05);
    }

    .role-edit-mobile-alert {
        margin-bottom: 16px;
    }

    .role-edit-mobile-group {
        margin-bottom: 28px;
    }

    .role-edit-mobile-group__head {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: center;
        padding-bottom: 10px;
        margin-bottom: 12px;
        border-bottom: 1px solid #e9dfd7;
    }

    .role-edit-mobile-group__title {
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-size: 1.05rem;
        line-height: 1.2;
        color: #7a4f31;
        font-weight: 800;
    }

    .role-edit-mobile-group__title i {
        width: 18px;
        text-align: center;
    }

    .role-edit-mobile-group__select {
        background: transparent;
        border: 0;
        color: #0b7a73;
        font-size: 0.82rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        cursor: pointer;
    }

    .role-edit-mobile-group__select:disabled {
        color: #b2a59a;
        cursor: not-allowed;
    }

    .role-edit-mobile-list {
        display: grid;
        gap: 12px;
    }

    .role-edit-details-card {
        padding: 22px 20px;
        border-radius: 22px;
        background: #fff;
        border: 1px solid rgba(138, 90, 68, 0.08);
        box-shadow: 0 16px 34px rgba(24, 18, 13, 0.05);
    }

    .role-edit-details-card__title {
        margin: 0 0 8px;
        font-size: 1.8rem;
        line-height: 1;
        letter-spacing: -0.05em;
        color: #191513;
    }

    .role-edit-details-card__copy {
        margin: 0 0 18px;
        color: #66584f;
        font-size: 0.98rem;
        line-height: 1.55;
    }

    .role-edit-details-grid {
        display: grid;
        gap: 20px;
    }

    .role-edit-details-field label {
        display: block;
        margin-bottom: 10px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: #7a4f31;
    }

    .role-edit-details-input,
    .role-edit-details-textarea {
        width: 100%;
        border: 1px solid #e4dfd8;
        background: #e3e7eb;
        border-radius: 14px;
        padding: 18px 20px;
        font-size: 1.05rem;
        color: #221a16;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.82);
    }

    .role-edit-details-input::placeholder,
    .role-edit-details-textarea::placeholder {
        color: #677489;
    }

    .role-edit-details-input:focus,
    .role-edit-details-textarea:focus {
        outline: none;
        border-color: rgba(138, 90, 68, 0.22);
        box-shadow: 0 0 0 3px rgba(138, 90, 68, 0.09);
        background: #e9edf1;
    }

    .role-edit-details-textarea {
        min-height: 160px;
        resize: vertical;
    }

    .role-edit-mobile-item {
        padding: 18px 18px;
        border-radius: 18px;
        background: #fff;
        border: 1px solid rgba(138, 90, 68, 0.08);
        box-shadow: 0 12px 28px rgba(24, 18, 13, 0.04);
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: center;
    }

    .role-edit-mobile-item--muted {
        opacity: 0.6;
        border-style: dashed;
        border-color: #dfd7d0;
        background: #fbfaf9;
        box-shadow: none;
    }

    .role-edit-mobile-item__copy {
        min-width: 0;
    }

    .role-edit-mobile-item__copy strong {
        display: block;
        margin-bottom: 4px;
        font-size: 1.05rem;
        line-height: 1.3;
        color: #1f1915;
    }

    .role-edit-mobile-item__copy small {
        display: block;
        color: #61544b;
        font-size: 0.95rem;
        line-height: 1.55;
    }

    .role-edit-mobile-switch {
        position: relative;
        width: 58px;
        height: 34px;
        flex-shrink: 0;
    }

    .role-edit-mobile-switch input {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .role-edit-mobile-switch span {
        position: absolute;
        inset: 0;
        border-radius: 999px;
        background: #e2e6ea;
        transition: all 0.18s ease;
    }

    .role-edit-mobile-switch span::after {
        content: "";
        position: absolute;
        top: 3px;
        left: 3px;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        transition: transform 0.18s ease;
    }

    .role-edit-mobile-switch input:checked + span {
        background: #8a5a44;
    }

    .role-edit-mobile-switch input:checked + span::after {
        transform: translateX(24px);
    }

    .role-edit-mobile-note {
        margin-top: 10px;
        color: #9b8d82;
        font-size: 0.84rem;
        font-style: italic;
    }

    .role-edit-mobile-sticky {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 25;
        display: none;
        padding: 16px;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(20px);
        border-top: 1px solid rgba(233, 226, 218, 0.92);
        box-shadow: 0 -10px 34px rgba(24, 18, 13, 0.06);
    }

    .role-edit-mobile-sticky__actions {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 2fr);
        gap: 14px;
    }

    .role-edit-mobile-sticky .btn {
        min-height: 56px;
        border-radius: 14px;
        font-size: 1rem;
        font-weight: 800;
    }

    .role-edit-mobile-sticky .btn-light {
        background: #f3f0ed;
        border-color: #ece2da;
        color: #2d2621;
    }

    @media (max-width: 1024px) {
        .role-edit-desktop {
            display: none;
        }

        .role-edit-mobile {
            display: block;
        }

        .role-edit-mobile-sticky {
            display: block;
        }
    }
</style>
@endsection

@section('content')
@php
    $selectedPermissionIds = collect(old('permissions', $role->permissions->pluck('id')->all()))
        ->map(fn($id) => (int) $id)
        ->all();
    $activeTab = request('tab', 'details');
    $isFixedRole = $role->isFixed();
@endphp

<div class="role-edit-desktop">
    <div class="page-header">
        <div class="page-title">
            <h1 class="text-dark">Edit Role</h1>
            <p>Use separate tabs to update role details and permissions.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($isFixedRole)
        <div class="alert alert-info">{{ $role->name }} is a fixed system role. Its details and permissions cannot be changed.</div>
    @endif

    <div class="role-tabs">
        <button type="button" class="role-tab-btn {{ $activeTab === 'details' ? 'active' : '' }}" data-tab-target="details">Role Details</button>
        <button type="button" class="role-tab-btn {{ $activeTab === 'permissions' ? 'active' : '' }}" data-tab-target="permissions">Assign Permissions</button>
    </div>

    <div class="role-tab-pane {{ $activeTab === 'details' ? 'active' : '' }}" data-tab-pane="details">
        <form action="{{ route('role.update', $role) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">Role Information</div>
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
                    <div class="role-form-group">
                        <label for="name">Role Name</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            class="role-input"
                            value="{{ old('name', $role->name) }}"
                            placeholder="Manager"
                            required
                            @disabled($isFixedRole)
                        >
                    </div>

                    <div class="role-form-group role-form-group-full">
                        <label for="description">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            class="role-input"
                            rows="3"
                            placeholder="Role summary and access scope"
                            @disabled($isFixedRole)
                        >{{ old('description', $role->description) }}</textarea>
                    </div>
                </div>

                <div class="role-form-actions">
                    <a href="{{ route('role.index') }}" class="btn btn-secondary">Cancel</a>
                    @unless ($isFixedRole)
                        <button type="submit" class="btn btn-primary">Save Role Details</button>
                    @endunless
                </div>
            </div>
        </form>
    </div>

    <div class="role-tab-pane {{ $activeTab === 'permissions' ? 'active' : '' }}" data-tab-pane="permissions">
        <form action="{{ route('role.updatePermissions', $role) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">Assign Permissions</div>
                </div>

                @if ($errors->any() && $activeTab === 'permissions')
                    <div class="alert alert-danger">
                        <strong>Please fix the following errors:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="assignment-banner">
                    <div class="assignment-banner-text">
                        <p class="role-tab-note">Choose permission scope for this role and save to apply instantly.</p>
                    </div>
                    <div class="assignment-stats">
                        <div class="assignment-stat">
                            <strong id="role-permission-selected-count">{{ count($selectedPermissionIds) }}</strong>
                            <span>Selected</span>
                        </div>
                        <div class="assignment-stat">
                            <strong>{{ $permissionsByGroup->flatten(1)->count() }}</strong>
                            <span>Total Permissions</span>
                        </div>
                    </div>
                </div>

                <div class="role-permission-controls">
                    <label class="role-permission-toggle">
                        <input type="checkbox" id="role-select-all-permissions" @disabled($isFixedRole)>
                        <span>Select All Permissions</span>
                    </label>
                </div>

                <div class="role-permission-groups">
                    @forelse ($permissionsByGroup as $group => $permissions)
                        @php
                            $groupSlug = \Illuminate\Support\Str::slug($group);
                            $groupSelectedCount = $permissions->whereIn('id', $selectedPermissionIds)->count();
                        @endphp
                        <div class="role-permission-group">
                            <div class="role-permission-group-header">
                                <div class="role-permission-group-title">{{ $group }}</div>
                                <div class="role-permission-group-actions">
                                    <span class="assignment-group-count">
                                        <span class="js-role-group-selected-count" data-group-count="{{ $groupSlug }}">{{ $groupSelectedCount }}</span>/{{ $permissions->count() }}
                                    </span>
                                    <label class="role-permission-toggle">
                                        <input
                                            type="checkbox"
                                            class="js-role-select-group"
                                            data-group="{{ $groupSlug }}"
                                            @disabled($isFixedRole)
                                        >
                                        <span>Select All</span>
                                    </label>
                                </div>
                            </div>
                            <div class="role-permission-list">
                                @foreach ($permissions as $permission)
                                    <label class="role-permission-item">
                                        <span class="role-permission-content">
                                            <strong>{{ $permission->name }}</strong>
                                            <small>{{ $permission->description ?: 'No description available.' }}</small>
                                        </span>
                                        <span class="role-switch">
                                            <input
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission->id }}"
                                                class="js-role-permission-checkbox"
                                                data-group="{{ $groupSlug }}"
                                                @checked(in_array($permission->id, $selectedPermissionIds, true))
                                                @disabled($isFixedRole)
                                            >
                                            <span class="role-switch-slider"></span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="role-permission-group">No permissions available.</div>
                    @endforelse
                </div>

                <div class="role-form-actions">
                    <a href="{{ route('role.index') }}" class="btn btn-secondary">Cancel</a>
                    @unless ($isFixedRole)
                        <button type="submit" class="btn btn-primary">Save Permissions</button>
                    @endunless
                </div>
            </div>
        </form>
    </div>
</div>

<div class="role-edit-mobile">
    <div class="role-edit-mobile-shell">
        <section class="role-edit-mobile-hero">
            <span class="role-edit-mobile-chip {{ $isFixedRole ? 'role-edit-mobile-chip--fixed' : '' }}">
                {{ $isFixedRole ? 'Fixed Role' : 'Active Role' }}
            </span>
            <h1>{{ $role->name }}</h1>
            <p>{{ $role->description ?: 'Configure access levels and permission coverage for this role.' }}</p>
        </section>

        @if (session('success'))
            <div class="role-edit-mobile-alert alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($isFixedRole)
            <div class="role-edit-mobile-alert alert alert-info">{{ $role->name }} is a fixed system role. Its details and permissions cannot be changed.</div>
        @endif

        <div class="role-edit-mobile-tabs">
            <button type="button" class="role-edit-mobile-tab {{ $activeTab === 'details' ? 'is-active' : '' }}" data-tab-target="details">Role Details</button>
            <button type="button" class="role-edit-mobile-tab {{ $activeTab === 'permissions' ? 'is-active' : '' }}" data-tab-target="permissions">Permissions</button>
        </div>

        <div class="role-tab-pane {{ $activeTab === 'details' ? 'active' : '' }}" data-tab-pane="details">
            <form action="{{ route('role.update', $role) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="role-edit-details-card">
                    <h2 class="role-edit-details-card__title">Role Details</h2>
                    <p class="role-edit-details-card__copy">Update the identity and scope of this administrative role within the atelier.</p>

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

                    <div class="role-edit-details-grid">
                        <div class="role-edit-details-field">
                            <label for="mobile_name">Role Name</label>
                            <input
                                id="mobile_name"
                                name="name"
                                type="text"
                                class="role-edit-details-input"
                                value="{{ old('name', $role->name) }}"
                                placeholder="e.g. Master Tailor"
                                required
                                @disabled($isFixedRole)
                            >
                        </div>

                        <div class="role-edit-details-field">
                            <label for="mobile_description">Description</label>
                            <textarea
                                id="mobile_description"
                                name="description"
                                class="role-edit-details-textarea"
                                rows="4"
                                placeholder="Briefly describe the responsibilities associated with this role..."
                                @disabled($isFixedRole)
                            >{{ old('description', $role->description) }}</textarea>
                        </div>
                    </div>

                    <div class="role-form-actions">
                        <a href="{{ route('role.index') }}" class="btn btn-secondary">Cancel</a>
                        @unless ($isFixedRole)
                            <button type="submit" class="btn btn-primary">Save Role Details</button>
                        @endunless
                    </div>
                </div>
            </form>
        </div>

        <div class="role-tab-pane {{ $activeTab === 'permissions' ? 'active' : '' }}" data-tab-pane="permissions">
            <form action="{{ route('role.updatePermissions', $role) }}" method="POST">
                @csrf
                @method('PUT')

                @if ($errors->any() && $activeTab === 'permissions')
                    <div class="alert alert-danger mb-4">
                        <strong>Please fix the following errors:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @foreach ($permissionsByGroup as $group => $permissions)
                    @php
                        $groupSlug = \Illuminate\Support\Str::slug($group);
                        $groupIcon = str_contains(strtolower($group), 'customer')
                            ? 'fa-user-gear'
                            : (str_contains(strtolower($group), 'garment')
                                ? 'fa-shirt'
                                : (str_contains(strtolower($group), 'billing') || str_contains(strtolower($group), 'financial')
                                    ? 'fa-money-bill-wave'
                                    : 'fa-shield-halved'));
                    @endphp
                    <section class="role-edit-mobile-group">
                        <div class="role-edit-mobile-group__head">
                            <h3 class="role-edit-mobile-group__title">
                                <i class="fas {{ $groupIcon }}"></i>
                                <span>{{ $group }}</span>
                            </h3>
                            <button
                                type="button"
                                class="role-edit-mobile-group__select js-role-mobile-select-group"
                                data-group="{{ $groupSlug }}"
                                @disabled($isFixedRole)
                            >
                                Select All
                            </button>
                        </div>

                        <div class="role-edit-mobile-list">
                            @foreach ($permissions as $permission)
                                @php
                                    $isChecked = in_array($permission->id, $selectedPermissionIds, true);
                                    $isLocked = $isFixedRole;
                                @endphp
                                <label class="role-edit-mobile-item {{ $isLocked ? 'role-edit-mobile-item--muted' : '' }}">
                                    <div class="role-edit-mobile-item__copy">
                                        <strong>{{ $permission->name }}</strong>
                                        <small>{{ $permission->description ?: 'No description available.' }}</small>
                                    </div>
                                    <span class="role-edit-mobile-switch">
                                        <input
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $permission->id }}"
                                            class="js-role-permission-checkbox"
                                            data-group="{{ $groupSlug }}"
                                            @checked($isChecked)
                                            @disabled($isLocked)
                                        >
                                        <span></span>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        @if ($isFixedRole)
                            <p class="role-edit-mobile-note">This role is protected by system security policies.</p>
                        @endif
                    </section>
                @endforeach

                @unless ($isFixedRole)
                    <div class="role-edit-mobile-sticky">
                        <div class="role-edit-mobile-sticky__actions">
                            <a href="{{ route('role.index') }}" class="btn btn-light">Discard</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </div>
                @endunless
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-specific-script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabButtons = Array.from(document.querySelectorAll('[data-tab-target]'));
        const panes = Array.from(document.querySelectorAll('.role-tab-pane'));

        const activateTab = (tabName) => {
            tabButtons.forEach((button) => {
                const isActive = button.dataset.tabTarget === tabName;
                button.classList.toggle('active', isActive);
                button.classList.toggle('is-active', isActive);
            });

            panes.forEach((pane) => {
                pane.classList.toggle('active', pane.dataset.tabPane === tabName);
            });
        };

        tabButtons.forEach((button) => {
            button.addEventListener('click', function () {
                activateTab(button.dataset.tabTarget);
            });
        });

        const allCheckbox = document.getElementById('role-select-all-permissions');
        const permissionCheckboxes = Array.from(document.querySelectorAll('.js-role-permission-checkbox'));
        const groupToggles = Array.from(document.querySelectorAll('.js-role-select-group'));
        const mobileGroupButtons = Array.from(document.querySelectorAll('.js-role-mobile-select-group'));
        const selectedPermissionCount = document.getElementById('role-permission-selected-count');
        const groupSelectedCountNodes = Array.from(document.querySelectorAll('.js-role-group-selected-count'));

        if (permissionCheckboxes.length === 0) {
            return;
        }

        const getGroupCheckboxes = (groupName) =>
            permissionCheckboxes.filter((checkbox) => checkbox.dataset.group === groupName);

        const updateGroupToggleState = (groupToggle) => {
            const groupCheckboxes = getGroupCheckboxes(groupToggle.dataset.group);
            const checkedCount = groupCheckboxes.filter((checkbox) => checkbox.checked).length;

            groupToggle.checked = checkedCount > 0 && checkedCount === groupCheckboxes.length;
            groupToggle.indeterminate = checkedCount > 0 && checkedCount < groupCheckboxes.length;
        };

        const updateGroupSelectedCount = (groupName) => {
            const groupCheckboxes = getGroupCheckboxes(groupName);
            const checkedCount = groupCheckboxes.filter((checkbox) => checkbox.checked).length;
            const counter = groupSelectedCountNodes.find((node) => node.dataset.groupCount === groupName);

            if (counter) {
                counter.textContent = checkedCount;
            }
        };

        const updateAllToggleState = () => {
            if (!allCheckbox) {
                return;
            }

            const checkedCount = permissionCheckboxes.filter((checkbox) => checkbox.checked).length;

            allCheckbox.checked = checkedCount > 0 && checkedCount === permissionCheckboxes.length;
            allCheckbox.indeterminate = checkedCount > 0 && checkedCount < permissionCheckboxes.length;

            if (selectedPermissionCount) {
                selectedPermissionCount.textContent = checkedCount;
            }
        };

        const updateMobileGroupButtonState = (button) => {
            const groupCheckboxes = getGroupCheckboxes(button.dataset.group);
            const checkedCount = groupCheckboxes.filter((checkbox) => checkbox.checked).length;
            button.textContent = checkedCount > 0 && checkedCount === groupCheckboxes.length ? 'Clear All' : 'Select All';
        };

        const syncAllStates = () => {
            groupToggles.forEach((groupToggle) => {
                updateGroupToggleState(groupToggle);
                updateGroupSelectedCount(groupToggle.dataset.group);
            });
            mobileGroupButtons.forEach(updateMobileGroupButtonState);
            updateAllToggleState();
        };

        if (allCheckbox) {
            allCheckbox.addEventListener('change', function () {
                permissionCheckboxes.forEach((checkbox) => {
                    checkbox.checked = allCheckbox.checked;
                });
                syncAllStates();
            });
        }

        groupToggles.forEach((groupToggle) => {
            groupToggle.addEventListener('change', function () {
                const groupCheckboxes = getGroupCheckboxes(groupToggle.dataset.group);
                groupCheckboxes.forEach((checkbox) => {
                    checkbox.checked = groupToggle.checked;
                });
                syncAllStates();
            });
        });

        mobileGroupButtons.forEach((button) => {
            button.addEventListener('click', function () {
                const groupCheckboxes = getGroupCheckboxes(button.dataset.group);
                const shouldSelect = groupCheckboxes.some((checkbox) => !checkbox.checked);
                groupCheckboxes.forEach((checkbox) => {
                    if (!checkbox.disabled) {
                        checkbox.checked = shouldSelect;
                    }
                });
                syncAllStates();
            });
        });

        permissionCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', syncAllStates);
        });

        syncAllStates();
    });
</script>
@endsection
