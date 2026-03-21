@extends('layouts.app')

@section('title', 'Role Management')

@section('page-specific-style')
<style>
    .role-mobile-view {
        display: none;
    }

    .role-mobile-shell {
        width: 100%;
        padding-bottom: 28px;
    }

    .role-mobile-search {
        position: relative;
        margin-bottom: 14px;
    }

    .role-mobile-search i {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: #8f7d71;
        font-size: 1rem;
    }

    .role-mobile-search input {
        width: 100%;
        min-height: 56px;
        padding: 0 18px 0 48px;
        border-radius: 16px;
        border: 1px solid #ece3dc;
        background: #fff;
        box-shadow: 0 12px 24px rgba(24, 18, 13, 0.03);
        font-size: 0.98rem;
    }

    .role-mobile-search input:focus {
        outline: none;
        border-color: rgba(138, 90, 68, 0.22);
        box-shadow: 0 0 0 3px rgba(138, 90, 68, 0.08);
    }

    .role-mobile-actions {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.45fr);
        gap: 14px;
        margin-bottom: 18px;
    }

    .role-mobile-actions .btn {
        min-height: 54px;
        border-radius: 16px;
        font-weight: 700;
    }

    .role-mobile-actions .btn-light {
        background: #f3f0ed;
        border-color: #ece2da;
        color: #6e4a33;
    }

    .role-mobile-list {
        display: grid;
        gap: 18px;
    }

    .role-mobile-card {
        padding: 20px 18px;
        border-radius: 22px;
        background: #fff;
        border: 1px solid rgba(138, 90, 68, 0.08);
        box-shadow: 0 16px 34px rgba(24, 18, 13, 0.05);
    }

    .role-mobile-card--fixed {
        border-style: dashed;
        border-color: #dddbd7;
        background: linear-gradient(180deg, #fff 0%, #fbfaf8 100%);
    }

    .role-mobile-card__head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 14px;
    }

    .role-mobile-card__head-main {
        display: flex;
        gap: 14px;
        align-items: flex-start;
        min-width: 0;
    }

    .role-mobile-card__icon {
        width: 46px;
        height: 46px;
        flex-shrink: 0;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        background: #ffd7bd;
        color: #7a4f31;
    }

    .role-mobile-card__icon--worker {
        background: #98f5ea;
        color: #076d64;
    }

    .role-mobile-card__icon--inventory {
        background: #f4d4b6;
        color: #7d5334;
    }

    .role-mobile-card__icon--neutral {
        background: #ece9e6;
        color: #7a7068;
    }

    .role-mobile-card__title {
        margin: 0;
        font-size: 1.15rem;
        line-height: 1.1;
        letter-spacing: -0.03em;
        color: #1f1915;
    }

    .role-mobile-card__title-wrap {
        min-width: 0;
    }

    .role-mobile-card__description {
        margin: 0 0 14px;
        color: #6a5c53;
        font-size: 0.92rem;
        line-height: 1.55;
    }

    .role-mobile-card__tools {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-shrink: 0;
    }

    .role-mobile-card__icon-btn {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #a08d80;
        background: transparent;
        border: 0;
    }

    .role-mobile-card__icon-btn--danger {
        color: #b67069;
    }

    .role-mobile-label {
        display: block;
        margin-bottom: 10px;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: #a08f84;
    }

    .role-mobile-chip-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }

    .role-mobile-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 10px;
        border-radius: 999px;
        background: #98f5ea;
        color: #056a5f;
        font-size: 0.76rem;
        font-weight: 700;
        line-height: 1;
    }

    .role-mobile-chip--muted {
        background: #f0ece9;
        color: #7f7268;
    }

    .role-mobile-card__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding-top: 14px;
        border-top: 1px solid #efe5dc;
        color: #286e67;
        font-size: 0.86rem;
        font-weight: 700;
    }

    .role-mobile-card__footer span {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .role-mobile-safeguard {
        padding: 22px 20px;
        border-radius: 22px;
        background: linear-gradient(180deg, #f6f4f1 0%, #f2efeb 100%);
        border: 1px solid #ece2da;
        box-shadow: 0 16px 34px rgba(24, 18, 13, 0.04);
    }

    .role-mobile-safeguard h3 {
        margin: 0 0 10px;
        font-size: 1.2rem;
        line-height: 1.1;
        color: #7a4f31;
    }

    .role-mobile-safeguard p {
        margin: 0;
        color: #6e5b50;
        line-height: 1.6;
    }

    .role-mobile-safeguard__stats {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid #e8ddd3;
    }

    .role-mobile-safeguard__stat {
        text-align: center;
    }

    .role-mobile-safeguard__stat strong {
        display: block;
        font-size: 2rem;
        line-height: 1;
        color: #5c3d2a;
    }

    .role-mobile-safeguard__stat span {
        display: block;
        margin-top: 6px;
        font-size: 0.72rem;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: #9b897d;
        font-weight: 700;
    }

    @media (max-width: 1024px) {
        .role-desktop-view {
            display: none;
        }

        .role-mobile-view {
            display: block;
        }
    }
</style>
@endsection

@section('content')
@php
    $query = trim((string) request('q', ''));
    $totalAssignedUsers = $roles->sum(fn ($role) => (int) $role->users_count);
@endphp

<div class="role-desktop-view">
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

    @include('includes.listing-filter', ['paginator' => $roles, 'placeholder' => 'Search by role name or description...'])

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
                        @php
                            $isFixedRole = $role->isFixed();
                        @endphp
                        <tr>
                            <td>
                                {{ $role->name }}
                                @if ($isFixedRole)
                                    <span class="role-chip">Fixed</span>
                                @endif
                            </td>
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
                                        @unless ($isFixedRole)
                                        <a href="{{ route('role.edit', $role) }}" class="btn btn-sm btn-secondary">Edit</a>
                                        @endunless
                                    @endcanany

                                    @canany(['manage-roles', 'delete-roles'])
                                        @unless ($isFixedRole)
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
                                        @endunless
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
</div>

<div class="role-mobile-view">
    <div class="role-mobile-shell">
        @if (session('success'))
            <div class="alert alert-success mb-4">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger mb-4">{{ session('error') }}</div>
        @endif

        <form method="GET" class="role-mobile-search">
            <i class="fas fa-search"></i>
            <input
                type="text"
                name="q"
                value="{{ $query }}"
                placeholder="Search roles by name or permission..."
            >
        </form>

        <div class="role-mobile-actions">
            <a href="{{ url()->current() }}" class="btn btn-light">
                <i class="fas fa-filter"></i>
                <span>Filter</span>
            </a>
            @canany(['manage-roles', 'create-roles'])
                <a href="{{ route('role.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    <span>Add Role</span>
                </a>
            @endcanany
        </div>

        <section class="role-mobile-list">
            @forelse ($roles as $role)
                @php
                    $isFixedRole = $role->isFixed();
                    $permissions = $role->permissions ?? collect();
                    $visiblePermissions = $permissions->take(4);
                    $remainingPermissions = max(0, $permissions->count() - $visiblePermissions->count());
                    $iconClass = str_contains(strtolower($role->name), 'manager')
                        ? ''
                        : (str_contains(strtolower($role->name), 'tailor') || str_contains(strtolower($role->name), 'worker')
                            ? 'role-mobile-card__icon--worker'
                            : (str_contains(strtolower($role->name), 'inventory')
                                ? 'role-mobile-card__icon--inventory'
                                : 'role-mobile-card__icon--neutral'));
                @endphp
                <article class="role-mobile-card {{ $isFixedRole ? 'role-mobile-card--fixed' : '' }}">
                    <div class="role-mobile-card__head">
                        <div class="role-mobile-card__head-main">
                            <div class="role-mobile-card__icon {{ $iconClass }}">
                                <i class="fas {{ str_contains(strtolower($role->name), 'manager') ? 'fa-user-gear' : (str_contains(strtolower($role->name), 'tailor') || str_contains(strtolower($role->name), 'worker') ? 'fa-screwdriver-wrench' : (str_contains(strtolower($role->name), 'inventory') ? 'fa-box-archive' : 'fa-user-shield')) }}"></i>
                            </div>
                            <div class="role-mobile-card__title-wrap">
                                <h2 class="role-mobile-card__title">{{ $role->name }}</h2>
                            </div>
                        </div>
                        <div class="role-mobile-card__tools">
                            @canany(['manage-roles', 'edit-roles'])
                                @unless ($isFixedRole)
                                    <a href="{{ route('role.edit', $role) }}" class="role-mobile-card__icon-btn" aria-label="Edit {{ $role->name }}">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                @endunless
                            @endcanany
                            @canany(['manage-roles', 'delete-roles'])
                                @unless ($isFixedRole)
                                    <form action="{{ route('role.destroy', $role) }}" method="POST" onsubmit="return confirm('Delete this role?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="role-mobile-card__icon-btn role-mobile-card__icon-btn--danger" aria-label="Delete {{ $role->name }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endunless
                            @endcanany
                        </div>
                    </div>

                    <p class="role-mobile-card__description">{{ $role->description ?: 'No description provided for this role.' }}</p>

                    <span class="role-mobile-label">Core Permissions</span>
                    <div class="role-mobile-chip-list">
                        @forelse ($visiblePermissions as $permission)
                            <span class="role-mobile-chip">{{ $permission->name }}</span>
                        @empty
                            <span class="role-mobile-chip role-mobile-chip--muted">No Permissions</span>
                        @endforelse
                        @if ($remainingPermissions > 0)
                            <span class="role-mobile-chip role-mobile-chip--muted">+{{ $remainingPermissions }} more</span>
                        @endif
                        @if ($isFixedRole)
                            <span class="role-mobile-chip role-mobile-chip--muted">Fixed</span>
                        @endif
                    </div>

                    <div class="role-mobile-card__footer">
                        <span>
                            <i class="fas fa-users"></i>
                            {{ number_format((int) $role->users_count) }} active assignments
                        </span>
                    </div>
                </article>
            @empty
                <div class="table-card">
                    <div class="empty">No roles found.</div>
                </div>
            @endforelse
        </section>

        <section class="role-mobile-safeguard mt-4">
            <h3>Permission Safeguards</h3>
            <p>Role modifications are logged through the audit trail. Changing core permissions may affect active sessions across the organization.</p>

            <div class="role-mobile-safeguard__stats">
                <div class="role-mobile-safeguard__stat">
                    <strong>{{ number_format($totalAssignedUsers) }}</strong>
                    <span>Total Users</span>
                </div>
                <div class="role-mobile-safeguard__stat">
                    <strong>{{ number_format($roles->count()) }}</strong>
                    <span>Unique Roles</span>
                </div>
            </div>
        </section>

        @if ($roles->hasPages())
            <div class="pagination mt-4">
                {{ $roles->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
