@extends('layouts.app')

@section('title', 'Edit User')


@section('content')
@php
    $activeTab = request('tab', 'user');
    $canManageSuperAdmin = auth()->user()->is_super_admin;
    $selectedOutletIds = collect(old('outlet_ids', $user->outlets->pluck('id')->all()))->map(fn($id) => (int) $id)->all();
    $roleIdsOld = collect(old('role_ids', $assignedRoleIds))->map(fn($id) => (int) $id)->all();
    $permissionOverridesOld = collect(old('permission_overrides', $assignedPermissionOverrides))
        ->mapWithKeys(fn($type, $id) => [(int) $id => $type])
        ->filter(fn($type) => in_array($type, ['allow', 'deny'], true))
        ->all();
@endphp

<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Edit User</h1>
        <p>Use tabs to update user details, assign roles, and assign permission overrides.</p>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="user-tabs">
    <button type="button" class="user-tab-btn {{ $activeTab === 'user' ? 'active' : '' }}" data-tab-target="user">User</button>
    <button type="button" class="user-tab-btn {{ $activeTab === 'roles' ? 'active' : '' }}" data-tab-target="roles">Assign Roles</button>
    <button type="button" class="user-tab-btn {{ $activeTab === 'permissions' ? 'active' : '' }}" data-tab-target="permissions">Assign Permissions</button>
</div>

<div class="user-tab-pane {{ $activeTab === 'user' ? 'active' : '' }}" data-tab-pane="user">
    <form action="{{ route('user.update', ['user' => $user, 'tab' => 'user']) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="table-card">
            <div class="table-header">
                <div class="table-title">User Information</div>
            </div>

            @if ($errors->any() && $activeTab === 'user')
                <div class="alert alert-danger">
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="user-form-grid">
                <div class="user-form-group">
                    <label for="name">Name</label>
                    <input id="name" name="name" type="text" class="user-input" value="{{ old('name', $user->name) }}" required>
                </div>

                <div class="user-form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" class="user-input" value="{{ old('email', $user->email) }}" required>
                </div>

                <div class="user-form-group">
                    <label for="avatar">Avatar</label>
                    <input id="avatar" name="avatar" type="file" class="user-input" accept=".jpg,.jpeg,.png,.webp">
                    @if ($user->avatar)
                        <div style="margin-top: 8px;">
                            <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" style="width: 48px; height: 48px; object-fit: cover; border-radius: 50%;">
                        </div>
                    @endif
                </div>

                <div class="user-form-group">
                    <label for="password">Password (Leave blank to keep current)</label>
                    <input id="password" name="password" type="password" class="user-input">
                </div>

                <div class="user-form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="user-input">
                </div>

                @if ($canManageSuperAdmin)
                    <div class="user-form-group user-form-group-full">
                        <label class="user-toggle">
                            <input type="checkbox" name="is_super_admin" value="1" @checked(old('is_super_admin', $user->is_super_admin))>
                            <span>Super Admin</span>
                        </label>
                    </div>
                @endif

                <div class="user-form-group user-form-group-full">
                    <label>Allowed Outlets</label>
                    <div class="user-outlet-grid">
                        @foreach ($outlets as $outlet)
                            <label class="user-outlet-item">
                                <input
                                    type="checkbox"
                                    name="outlet_ids[]"
                                    value="{{ $outlet->id }}"
                                    class="js-user-outlet-checkbox"
                                    @checked(in_array($outlet->id, $selectedOutletIds, true))
                                >
                                <span class="user-outlet-card">
                                    <span class="user-outlet-icon"><i class="fas fa-store"></i></span>
                                    <span class="user-outlet-name">{{ $outlet->name }}</span>
                                    <span class="user-outlet-address">{{ $outlet->address }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="user-form-actions">
                <a href="{{ route('user.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save User</button>
            </div>
        </div>
    </form>
</div>

<div class="user-tab-pane {{ $activeTab === 'roles' ? 'active' : '' }}" data-tab-pane="roles">
    <form action="{{ route('user.updateRoles', ['user' => $user, 'tab' => 'roles', 'assignment_outlet_id' => $managedOutletId]) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="table-card">
            <div class="table-header">
                <div class="table-title">Assign Roles</div>
            </div>

            @if ($errors->any() && $activeTab === 'roles')
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
                    <p class="user-tab-note">Roles are assigned for selected user outlet only.</p>
                    <span class="assignment-outlet-pill">
                        <i class="fas fa-store"></i>
                        {{ optional($assignmentOutlets->firstWhere('id', $managedOutletId))->name ?? 'No outlet selected' }}
                    </span>
                </div>
                <div class="assignment-stats">
                    <div class="assignment-stat">
                        <strong id="user-role-selected-count">{{ count($roleIdsOld) }}</strong>
                        <span>Selected</span>
                    </div>
                    <div class="assignment-stat">
                        <strong>{{ $roles->count() }}</strong>
                        <span>Total Roles</span>
                    </div>
                </div>
            </div>

            <div class="user-form-grid" style="margin-bottom: 12px;">
                <div class="user-form-group">
                    <label for="roles_assignment_outlet_id">Assignment Outlet</label>
                    <select id="roles_assignment_outlet_id" name="assignment_outlet_id" class="user-input">
                        @foreach ($assignmentOutlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected((int) $managedOutletId === (int) $outlet->id)>
                                {{ $outlet->name }} - {{ $outlet->address }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="user-role-controls">
                <label class="user-role-toggle">
                    <input type="checkbox" id="user-select-all-roles">
                    <span>Select All Roles</span>
                </label>
            </div>

            <div class="user-role-list">
                @foreach ($roles as $role)
                    <label class="user-role-item">
                        <span class="user-role-content">
                            <strong>{{ $role->name }}</strong>
                            <small>{{ $role->description ?: 'No description available.' }}</small>
                        </span>
                        <span class="user-switch">
                            <input
                                type="checkbox"
                                name="role_ids[]"
                                value="{{ $role->id }}"
                                class="js-user-role-checkbox"
                                @checked(in_array($role->id, $roleIdsOld, true))
                            >
                            <span class="user-switch-slider"></span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="user-form-actions">
                <a href="{{ route('user.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Roles</button>
            </div>
        </div>
    </form>
</div>

<div class="user-tab-pane {{ $activeTab === 'permissions' ? 'active' : '' }}" data-tab-pane="permissions">
    <form action="{{ route('user.updatePermissions', ['user' => $user, 'tab' => 'permissions', 'assignment_outlet_id' => $managedOutletId]) }}" method="POST">
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
                    <p class="user-tab-note">Permission overrides are assigned for selected user outlet only. Set each permission to Inherit, Allow, or Deny.</p>
                    <span class="assignment-outlet-pill">
                        <i class="fas fa-store"></i>
                        {{ optional($assignmentOutlets->firstWhere('id', $managedOutletId))->name ?? 'No outlet selected' }}
                    </span>
                </div>
                <div class="assignment-stats">
                    <div class="assignment-stat">
                        <strong id="user-permission-selected-count">{{ count($permissionOverridesOld) }}</strong>
                        <span>Overrides</span>
                    </div>
                    <div class="assignment-stat">
                        <strong>{{ $permissionsByGroup->flatten(1)->count() }}</strong>
                        <span>Total Permissions</span>
                    </div>
                </div>
            </div>

            <div class="user-form-grid" style="margin-bottom: 12px;">
                <div class="user-form-group">
                    <label for="permissions_assignment_outlet_id">Assignment Outlet</label>
                    <select id="permissions_assignment_outlet_id" name="assignment_outlet_id" class="user-input">
                        @foreach ($assignmentOutlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected((int) $managedOutletId === (int) $outlet->id)>
                                {{ $outlet->name }} - {{ $outlet->address }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="user-permission-controls">
                <button type="button" class="btn btn-sm btn-secondary js-user-set-all-overrides" data-value="allow">Allow All</button>
                <button type="button" class="btn btn-sm btn-secondary js-user-set-all-overrides" data-value="deny">Deny All</button>
                <button type="button" class="btn btn-sm btn-secondary js-user-set-all-overrides" data-value="">Clear All</button>
            </div>

            <div class="user-permission-groups">
                @foreach ($permissionsByGroup as $group => $permissions)
                    @php
                        $groupSlug = \Illuminate\Support\Str::slug($group);
                        $groupSelectedCount = $permissions->filter(fn($permission) => isset($permissionOverridesOld[$permission->id]))->count();
                    @endphp
                    <div class="user-permission-group">
                        <div class="user-permission-group-header">
                            <div class="user-permission-group-title">{{ $group }}</div>
                            <div class="user-permission-group-actions">
                                <span class="assignment-group-count">
                                    <span class="js-user-group-selected-count" data-group-count="{{ $groupSlug }}">{{ $groupSelectedCount }}</span>/{{ $permissions->count() }}
                                </span>
                                <button type="button" class="btn btn-sm btn-secondary js-user-set-group-overrides" data-group="{{ $groupSlug }}" data-value="allow">Allow</button>
                                <button type="button" class="btn btn-sm btn-secondary js-user-set-group-overrides" data-group="{{ $groupSlug }}" data-value="deny">Deny</button>
                                <button type="button" class="btn btn-sm btn-secondary js-user-set-group-overrides" data-group="{{ $groupSlug }}" data-value="">Clear</button>
                            </div>
                        </div>

                        <div class="user-role-list">
                            @foreach ($permissions as $permission)
                                @php
                                    $currentPermissionOverride = $permissionOverridesOld[$permission->id] ?? '';
                                @endphp
                                <label class="user-permission-item">
                                    <span class="user-permission-content">
                                        <strong>{{ $permission->name }}</strong>
                                        <small>{{ $permission->description ?: 'No description available.' }}</small>
                                    </span>
                                    <select
                                        name="permission_overrides[{{ $permission->id }}]"
                                        class="user-input user-permission-select js-user-permission-override"
                                        data-group="{{ $groupSlug }}"
                                    >
                                        <option value="">Inherit Role</option>
                                        <option value="allow" @selected($currentPermissionOverride === 'allow')>Allow</option>
                                        <option value="deny" @selected($currentPermissionOverride === 'deny')>Deny</option>
                                    </select>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="user-form-actions">
                <a href="{{ route('user.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Permissions</button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('page-specific-script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabButtons = Array.from(document.querySelectorAll('.user-tab-btn'));
        const panes = Array.from(document.querySelectorAll('.user-tab-pane'));
        const editBaseUrl = @json(route('user.edit', $user));

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

        const navigateToAssignmentOutlet = (tab, outletId) => {
            if (!outletId) {
                return;
            }

            const params = new URLSearchParams(window.location.search);
            params.set('tab', tab);
            params.set('assignment_outlet_id', outletId);
            window.location.href = editBaseUrl + '?' + params.toString();
        };

        const rolesOutletSelect = document.getElementById('roles_assignment_outlet_id');
        if (rolesOutletSelect) {
            rolesOutletSelect.addEventListener('change', function () {
                navigateToAssignmentOutlet('roles', rolesOutletSelect.value);
            });
        }

        const permissionsOutletSelect = document.getElementById('permissions_assignment_outlet_id');
        if (permissionsOutletSelect) {
            permissionsOutletSelect.addEventListener('change', function () {
                navigateToAssignmentOutlet('permissions', permissionsOutletSelect.value);
            });
        }

        const allRoleCheckbox = document.getElementById('user-select-all-roles');
        const roleCheckboxes = Array.from(document.querySelectorAll('.js-user-role-checkbox'));
        const selectedRoleCount = document.getElementById('user-role-selected-count');

        const syncRoleSelectAll = () => {
            if (!allRoleCheckbox || roleCheckboxes.length === 0) {
                return;
            }

            const checkedCount = roleCheckboxes.filter((checkbox) => checkbox.checked).length;
            allRoleCheckbox.checked = checkedCount > 0 && checkedCount === roleCheckboxes.length;
            allRoleCheckbox.indeterminate = checkedCount > 0 && checkedCount < roleCheckboxes.length;

            if (selectedRoleCount) {
                selectedRoleCount.textContent = checkedCount;
            }
        };

        if (allRoleCheckbox) {
            allRoleCheckbox.addEventListener('change', function () {
                roleCheckboxes.forEach((checkbox) => {
                    checkbox.checked = allRoleCheckbox.checked;
                });
                syncRoleSelectAll();
            });

            roleCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', syncRoleSelectAll));
            syncRoleSelectAll();
        }

        const permissionOverrideInputs = Array.from(document.querySelectorAll('.js-user-permission-override'));
        const allOverrideButtons = Array.from(document.querySelectorAll('.js-user-set-all-overrides'));
        const groupOverrideButtons = Array.from(document.querySelectorAll('.js-user-set-group-overrides'));
        const selectedPermissionCount = document.getElementById('user-permission-selected-count');
        const groupSelectedCountNodes = Array.from(document.querySelectorAll('.js-user-group-selected-count'));

        if (permissionOverrideInputs.length === 0) {
            return;
        }

        const getGroupOverrides = (groupName) =>
            permissionOverrideInputs.filter((input) => input.dataset.group === groupName);

        const getOverriddenCount = (inputs) => inputs.filter((input) => input.value !== '').length;

        const updateGroupSelectedCount = (groupName) => {
            const groupOverrides = getGroupOverrides(groupName);
            const checkedCount = getOverriddenCount(groupOverrides);
            const counter = groupSelectedCountNodes.find((node) => node.dataset.groupCount === groupName);

            if (counter) {
                counter.textContent = checkedCount;
            }
        };

        const updateAllPermissionCount = () => {
            if (selectedPermissionCount) {
                selectedPermissionCount.textContent = getOverriddenCount(permissionOverrideInputs);
            }
        };

        const syncPermissionStates = () => {
            groupSelectedCountNodes.forEach((counterNode) => {
                updateGroupSelectedCount(counterNode.dataset.groupCount);
            });
            updateAllPermissionCount();
        };

        allOverrideButtons.forEach((button) => {
            button.addEventListener('click', function () {
                const value = button.dataset.value ?? '';
                permissionOverrideInputs.forEach((input) => {
                    input.value = value;
                });
                syncPermissionStates();
            });
        });

        groupOverrideButtons.forEach((button) => {
            button.addEventListener('click', function () {
                const groupName = button.dataset.group;
                const value = button.dataset.value ?? '';
                const groupOverrides = getGroupOverrides(groupName);
                groupOverrides.forEach((input) => {
                    input.value = value;
                });
                syncPermissionStates();
            });
        });

        permissionOverrideInputs.forEach((input) => input.addEventListener('change', syncPermissionStates));
        syncPermissionStates();
    });
</script>
@endsection
