@extends('layouts.app')

@section('title', 'Role Management')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Role Management</h1>
        <p>Define roles and assign permission bundles.</p>
    </div>
    @canany(['manage-roles', 'create-roles'])
        <div class="page-actions">
            <a href="{{ route('role.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Role
            </a>
        </div>
    @endcanany
</div>

@include('includes.reporting-filter', ['paginator' => $roles, 'placeholder' => 'Search by role name or description...', 'reporting' => $reporting])

<div class="table-card">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="table-header">
        <div class="table-title">Roles</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Permissions</th>
                    <th>Assignments (Current Outlet)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $role)
                    <tr>
                        <td>{{ $role->name }}</td>
                        <td>{{ $role->description ?: '-' }}</td>
                        <td>
                            <div class="role-chip-list">
                                @forelse ($role->permissions as $permission)
                                    <span class="role-chip">{{ $permission->name }}</span>
                                @empty
                                    <span class="text-muted">None</span>
                                @endforelse
                            </div>
                        </td>
                        <td>{{ $role->users_count }}</td>
                        <td>
                            <div class="actions">
                                @canany(['manage-roles', 'edit-roles'])
                                    <a href="{{ route('role.edit', $role) }}" class="btn btn-sm btn-secondary">Edit</a>
                                @endcanany

                                @canany(['manage-roles', 'delete-roles'])
                                    <form
                                        action="{{ route('role.destroy', $role) }}"
                                        method="POST"
                                        class="inline-form"
                                        onsubmit="return confirm('Delete this role?');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                @endcanany
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty">No roles found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($roles->hasPages())
        <div class="pagination">
            {{ $roles->links() }}
        </div>
    @endif
</div>
@endsection
