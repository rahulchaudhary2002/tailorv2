<div class="table-card role-form-card">
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

    @php
        $selectedPermissionIds = collect(old('permissions', isset($role) ? $role->permissions->pluck('id')->all() : []))
            ->map(fn($id) => (int) $id)
            ->all();
    @endphp

    <div class="role-form-grid">
        <div class="role-form-group">
            <label for="name">Role Name</label>
            <input
                id="name"
                name="name"
                type="text"
                class="role-input"
                value="{{ old('name', isset($role) ? $role->name : '') }}"
                placeholder="Manager"
                required
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
            >{{ old('description', isset($role) ? $role->description : '') }}</textarea>
        </div>

        <div class="role-form-group role-form-group-full">
            <label>Permissions</label>
            <div class="role-permission-controls">
                <label class="role-permission-toggle">
                    <input type="checkbox" id="role-select-all-permissions">
                    <span>Select All Permissions</span>
                </label>
            </div>
            <div class="role-permission-groups">
                @forelse ($permissionsByGroup as $group => $permissions)
                    <div class="role-permission-group">
                        <div class="role-permission-group-header">
                            <div class="role-permission-group-title">{{ $group }}</div>
                            <label class="role-permission-toggle">
                                <input
                                    type="checkbox"
                                    class="js-role-select-group"
                                    data-group="{{ \Illuminate\Support\Str::slug($group) }}"
                                >
                                <span>Select All</span>
                            </label>
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
                                            data-group="{{ \Illuminate\Support\Str::slug($group) }}"
                                            @checked(in_array($permission->id, $selectedPermissionIds, true))
                                        >
                                        <span class="role-switch-slider"></span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="empty">No permissions available.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="role-form-actions">
        <a href="{{ route('role.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</div>
