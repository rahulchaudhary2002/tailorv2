@extends('layouts.app')

@section('title', 'Edit Worker')


@section('content')
@php
    $activeTab = request('tab', 'worker');
    $selectedOutletIds = collect(old('outlet_ids', $worker->outlets->pluck('id')->all()))->map(fn($id) => (int) $id)->all();
    $permissionOverridesOld = collect(old('permission_overrides', $assignedPermissionOverrides))
        ->mapWithKeys(fn($type, $id) => [(int) $id => $type])
        ->filter(fn($type) => in_array($type, ['allow', 'deny'], true))
        ->all();
@endphp

<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Edit Worker</h1>
        <p>Manage worker details and permission overrides. Worker role is assigned automatically.</p>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="user-tabs">
    <button type="button" class="user-tab-btn {{ $activeTab === 'worker' ? 'active' : '' }}" data-tab-target="worker">Worker</button>
    <button type="button" class="user-tab-btn {{ $activeTab === 'permissions' ? 'active' : '' }}" data-tab-target="permissions">Assign Permissions</button>
</div>

<div class="user-tab-pane {{ $activeTab === 'worker' ? 'active' : '' }}" data-tab-pane="worker">
    <form action="{{ route('worker.update', ['worker' => $worker, 'tab' => 'worker']) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="table-card">
            <div class="table-header">
                <div class="table-title">Worker Information</div>
            </div>

            @if ($errors->any() && $activeTab === 'worker')
                <div class="alert alert-danger">
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="alert alert-info">Worker role is fixed and will stay assigned automatically for every selected outlet.</div>

            <div class="user-form-grid">
                <div class="user-form-group">
                    <label for="name">Name</label>
                    <input id="name" name="name" type="text" class="user-input" value="{{ old('name', $worker->name) }}" required>
                </div>

                <div class="user-form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" class="user-input" value="{{ old('email', $worker->email) }}" required>
                </div>

                <div class="user-form-group">
                    <label for="avatar">Avatar</label>
                    <input id="avatar" name="avatar" type="file" class="user-input" accept=".jpg,.jpeg,.png,.webp">
                    @if ($worker->avatar)
                        <div style="margin-top: 8px;">
                            <img src="{{ asset('storage/' . $worker->avatar) }}" alt="{{ $worker->name }}" style="width: 48px; height: 48px; object-fit: cover; border-radius: 50%;">
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
                <a href="{{ route('worker.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Worker</button>
            </div>
        </div>
    </form>
</div>

<div class="user-tab-pane {{ $activeTab === 'permissions' ? 'active' : '' }}" data-tab-pane="permissions">
    <form action="{{ route('worker.updatePermissions', ['worker' => $worker, 'tab' => 'permissions', 'assignment_outlet_id' => $managedOutletId]) }}" method="POST">
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
                    <p class="user-tab-note">Permission overrides are assigned for selected worker outlet only. Worker role remains fixed.</p>
                    <span class="assignment-outlet-pill">
                        <i class="fas fa-store"></i>
                        {{ optional($assignmentOutlets->firstWhere('id', $managedOutletId))->name ?? 'No outlet selected' }}
                    </span>
                </div>
                <div class="assignment-stats">
                    <div class="assignment-stat">
                        <strong id="worker-permission-selected-count">{{ count($permissionOverridesOld) }}</strong>
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
                <a href="{{ route('worker.index') }}" class="btn btn-secondary">Cancel</a>
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
        const editBaseUrl = @json(route('worker.edit', $worker));

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

        const permissionsOutletSelect = document.getElementById('permissions_assignment_outlet_id');
        if (permissionsOutletSelect) {
            permissionsOutletSelect.addEventListener('change', function () {
                navigateToAssignmentOutlet('permissions', permissionsOutletSelect.value);
            });
        }

        const overrideSelects = Array.from(document.querySelectorAll('.js-user-permission-override'));
        const groupCountNodes = Array.from(document.querySelectorAll('.js-user-group-selected-count'));
        const selectedCountNode = document.getElementById('worker-permission-selected-count');

        const updatePermissionCounts = () => {
            const selectedTotal = overrideSelects.filter((select) => select.value !== '').length;
            if (selectedCountNode) {
                selectedCountNode.textContent = selectedTotal;
            }

            groupCountNodes.forEach((node) => {
                const groupName = node.dataset.groupCount;
                const selectedInGroup = overrideSelects.filter((select) => select.dataset.group === groupName && select.value !== '').length;
                node.textContent = selectedInGroup;
            });
        };

        overrideSelects.forEach((select) => {
            select.addEventListener('change', updatePermissionCounts);
        });

        document.querySelectorAll('.js-user-set-all-overrides').forEach((button) => {
            button.addEventListener('click', function () {
                overrideSelects.forEach((select) => {
                    select.value = button.dataset.value;
                });
                updatePermissionCounts();
            });
        });

        document.querySelectorAll('.js-user-set-group-overrides').forEach((button) => {
            button.addEventListener('click', function () {
                const groupName = button.dataset.group;
                const value = button.dataset.value;

                overrideSelects
                    .filter((select) => select.dataset.group === groupName)
                    .forEach((select) => {
                        select.value = value;
                    });

                updatePermissionCounts();
            });
        });

        updatePermissionCounts();
    });
</script>
@endsection
