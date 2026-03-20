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

@php
    $query = trim((string) request('q', ''));
    $selectedOutletId = (int) request('outlet_id', 0);
@endphp

<div class="directory-reporting" style="margin-bottom: 16px;">
    <div class="directory-reporting__filter-bar">
        <div class="directory-reporting__filter-head">
            <h3 class="directory-reporting__filter-title">Filter Records</h3>
            @if ($query !== '' || $selectedOutletId > 0)
                <a href="{{ url()->current() }}" class="btn btn-light btn-sm">Clear Filters</a>
            @endif
        </div>

        <form method="GET" class="listing-filter-form">
            <div class="listing-filter-form__fields listing-filter-form__fields--user">
                <div class="outlet-form-group listing-filter-form__field listing-filter-form__field--search">
                    <label for="q_filter">Search</label>
                    <input id="q_filter" type="text" name="q" class="outlet-input" value="{{ $query }}" placeholder="Search by user name or email...">
                </div>

                <div class="outlet-form-group listing-filter-form__field">
                    <label for="outlet_filter">Outlet</label>
                    <select id="outlet_filter" name="outlet_id" class="outlet-input">
                        <option value="">All Outlets</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected($selectedOutletId === (int) $outlet->id)>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="listing-filter-form__actions">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="{{ url()->current() }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

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
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($user->avatar) }}" alt="{{ $user->name }}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
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

@section('page-specific-script')
<style>
    .listing-filter-form {
        display: grid;
        gap: 14px;
    }

    .listing-filter-form__fields {
        display: grid;
        grid-template-columns: minmax(280px, 1.4fr) repeat(3, minmax(200px, 1fr));
        gap: 12px;
        align-items: end;
    }

    .listing-filter-form__fields--user {
        grid-template-columns: minmax(320px, 1.7fr) minmax(240px, 1fr);
    }

    .listing-filter-form__field {
        margin-bottom: 0;
    }

    .listing-filter-form__actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    @media (max-width: 768px) {
        .listing-filter-form__fields,
        .listing-filter-form__fields--user {
            grid-template-columns: 1fr;
        }

        .listing-filter-form__actions {
            flex-direction: column;
        }

        .listing-filter-form__actions .btn {
            width: 100%;
        }
    }
</style>
@endsection
