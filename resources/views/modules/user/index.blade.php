@extends('layouts.app')

@section('title', 'User Management')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">User Management</h1>
        <p>Manage users, role assignments, and permission overrides.</p>
    </div>
    @canany(['manage-users', 'create-users'])
        <div class="page-actions">
            <a href="{{ route('user.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add User
            </a>
        </div>
    @endcanany
</div>

@include('includes.reporting-filter', ['paginator' => $users, 'placeholder' => 'Search by user name or email...', 'reporting' => $reporting])

<div class="table-card">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="table-header">
        <div class="table-title">Users</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Avatar</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Outlets</th>
                    <th>Current Outlet</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            @if ($user->avatar)
                                <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            @else
                                <span>-</span>
                            @endif
                        </td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->outlets_count }}</td>
                        <td>{{ $user->currentOutlet?->name ?? '-' }}</td>
                        <td>{{ $user->is_super_admin ? 'Super Admin' : 'User' }}</td>
                        <td>
                            <div class="actions">
                                @unless($user->is_super_admin)
                                    @canany(['manage-users', 'edit-users'])
                                        <a href="{{ route('user.edit', $user) }}" class="btn btn-sm btn-secondary">Edit</a>
                                    @endcanany
                                @endunless

                                @unless($user->is_super_admin)
                                    @canany(['manage-users', 'delete-users'])
                                        <form
                                            action="{{ route('user.destroy', $user) }}"
                                            method="POST"
                                            class="inline-form"
                                            onsubmit="return confirm('Delete this user?');"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    @endcanany
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="pagination">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection
