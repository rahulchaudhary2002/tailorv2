@extends('layouts.app')

@section('title', 'Edit Role')


@section('content')
@php
    $selectedPermissionIds = collect(old('permissions', $role->permissions->pluck('id')->all()))
        ->map(fn($id) => (int) $id)
        ->all();
    $activeTab = request('tab', 'details');
    $isFixedRole = $role->isFixed();
@endphp

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
                activateTab(button.dataset.tabTarget);
            });
        });

        const allCheckbox = document.getElementById('role-select-all-permissions');
        const permissionCheckboxes = Array.from(document.querySelectorAll('.js-role-permission-checkbox'));
        const groupToggles = Array.from(document.querySelectorAll('.js-role-select-group'));
        const selectedPermissionCount = document.getElementById('role-permission-selected-count');
        const groupSelectedCountNodes = Array.from(document.querySelectorAll('.js-role-group-selected-count'));

        if (!allCheckbox || permissionCheckboxes.length === 0) {
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
            const checkedCount = permissionCheckboxes.filter((checkbox) => checkbox.checked).length;

            allCheckbox.checked = checkedCount > 0 && checkedCount === permissionCheckboxes.length;
            allCheckbox.indeterminate = checkedCount > 0 && checkedCount < permissionCheckboxes.length;

            if (selectedPermissionCount) {
                selectedPermissionCount.textContent = checkedCount;
            }
        };

        const syncAllStates = () => {
            groupToggles.forEach((groupToggle) => {
                updateGroupToggleState(groupToggle);
                updateGroupSelectedCount(groupToggle.dataset.group);
            });
            updateAllToggleState();
        };

        allCheckbox.addEventListener('change', function () {
            permissionCheckboxes.forEach((checkbox) => {
                checkbox.checked = allCheckbox.checked;
            });
            syncAllStates();
        });

        groupToggles.forEach((groupToggle) => {
            groupToggle.addEventListener('change', function () {
                const groupCheckboxes = getGroupCheckboxes(groupToggle.dataset.group);
                groupCheckboxes.forEach((checkbox) => {
                    checkbox.checked = groupToggle.checked;
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
