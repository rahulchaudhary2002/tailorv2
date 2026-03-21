@extends('layouts.app')

@section('title', 'Outlet Management')

@section('page-specific-style')
<style>
    .outlet-mobile-view {
        display: none;
    }

    .outlet-mobile-shell {
        width: 100%;
        padding-bottom: 28px;
    }

    .outlet-mobile-hero {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        margin-bottom: 18px;
    }

    .outlet-mobile-hero h1 {
        margin: 0;
        font-size: clamp(2.2rem, 4vw, 3rem);
        line-height: 0.98;
        letter-spacing: -0.05em;
        color: #201915;
    }

    .outlet-mobile-hero p {
        margin: 12px 0 0;
        color: #66584f;
        font-size: 1rem;
        line-height: 1.55;
    }

    .outlet-mobile-add {
        width: 56px;
        height: 56px;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        background: linear-gradient(135deg, #8a5a44 0%, #6e4633 100%);
        color: #fff;
        box-shadow: 0 16px 30px rgba(110, 70, 51, 0.18);
    }

    .outlet-mobile-filters {
        display: grid;
        gap: 12px;
        margin-bottom: 22px;
    }

    .outlet-mobile-search {
        position: relative;
        display: flex;
        align-items: center;
        min-height: 62px;
        padding: 0 16px 0 56px;
        border-radius: 20px;
        background: #e8e8e8;
        border: 1px solid #dfdfdf;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
    }

    .outlet-mobile-search i {
        position: absolute;
        left: 20px;
        color: #8a7466;
        font-size: 1.18rem;
    }

    .outlet-mobile-search input {
        width: 100%;
        border: 0;
        background: transparent;
        font-size: 1rem;
        color: #2c231e;
    }

    .outlet-mobile-search input:focus {
        outline: none;
    }

    .outlet-mobile-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .outlet-mobile-actions .btn {
        min-height: 56px;
        border-radius: 16px;
        font-weight: 700;
        letter-spacing: 0.03em;
    }

    .outlet-mobile-actions .btn-light {
        border-color: #ece2d9;
        color: #7b5338;
        background: #fff;
    }

    .outlet-mobile-list {
        display: grid;
        gap: 18px;
    }

    .outlet-mobile-card {
        position: relative;
        overflow: hidden;
        padding: 22px 20px;
        border-radius: 24px;
        background: #fff;
        border: 1px solid rgba(138, 90, 68, 0.08);
        box-shadow: 0 16px 34px rgba(24, 18, 13, 0.05);
    }

    .outlet-mobile-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, #8a5a44 0%, #c8a78d 100%);
    }

    .outlet-mobile-card__top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 14px;
    }

    .outlet-mobile-card__code {
        display: inline-block;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: #8f796b;
    }

    .outlet-mobile-card__title {
        margin: 8px 0 0;
        font-size: 2rem;
        line-height: 1.02;
        letter-spacing: -0.05em;
    }

    .outlet-mobile-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 14px;
        border-radius: 999px;
        background: #8ef1e3;
        color: #046c5d;
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .outlet-mobile-card__meta {
        display: grid;
        gap: 12px;
        margin-bottom: 18px;
    }

    .outlet-mobile-card__line {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        color: #5f5047;
    }

    .outlet-mobile-card__line i {
        color: #8a5a44;
        width: 18px;
        margin-top: 3px;
    }

    .outlet-mobile-card__line span {
        font-size: 1rem;
        line-height: 1.5;
    }

    .outlet-mobile-card__stats {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        align-items: center;
        margin-bottom: 18px;
    }

    .outlet-mobile-card__stat {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: #211a16;
        font-weight: 700;
    }

    .outlet-mobile-card__stat i {
        color: #8a7466;
    }

    .outlet-mobile-card__actions {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 56px;
        gap: 10px;
        padding-top: 16px;
        border-top: 1px solid #efe5dc;
    }

    .outlet-mobile-card__edit {
        min-height: 48px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: linear-gradient(180deg, #f7f5f3 0%, #f2efec 100%);
        color: #7a4f31;
        border: 1px solid #eee5dd;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.92);
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        font-size: 0.92rem;
    }

    .outlet-mobile-card__edit:hover {
        background: linear-gradient(180deg, #f3efeb 0%, #ece7e2 100%);
    }

    .outlet-mobile-card__delete {
        width: 48px;
        height: 48px;
        min-height: 48px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        justify-self: end;
        background: linear-gradient(180deg, #fff8f7 0%, #fff3f1 100%);
        color: #c1362b;
        border: 1px solid #f7e4e1;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
    }

    .outlet-mobile-card__delete:hover {
        background: linear-gradient(180deg, #fff3f1 0%, #ffeceb 100%);
    }

    .outlet-mobile-pagination {
        margin-top: 18px;
    }

    @media (max-width: 1024px) {
        .outlet-desktop-view {
            display: none;
        }

        .outlet-mobile-view {
            display: block;
        }
    }

    @media (max-width: 640px) {
        .outlet-mobile-hero {
            gap: 14px;
            margin-bottom: 16px;
        }

        .outlet-mobile-hero h1 {
            font-size: 1.95rem;
        }

        .outlet-mobile-hero p,
        .outlet-mobile-card__line span {
            font-size: 0.96rem;
        }

        .outlet-mobile-card {
            padding: 20px 18px;
        }

        .outlet-mobile-card__title {
            font-size: 1.7rem;
        }

        .outlet-mobile-add {
            width: 52px;
            height: 52px;
            border-radius: 14px;
        }

        .outlet-mobile-search {
            min-height: 58px;
            border-radius: 18px;
        }

        .outlet-mobile-actions .btn {
            min-height: 52px;
        }
    }

    @media (max-width: 560px) {
        .outlet-mobile-hero h1 {
            font-size: 1.78rem;
        }

        .outlet-mobile-hero p {
            margin-top: 10px;
            font-size: 0.9rem;
        }

        .outlet-mobile-card {
            padding: 18px 16px;
            border-radius: 20px;
        }

        .outlet-mobile-card__title {
            font-size: 1.5rem;
        }

        .outlet-mobile-card__stats {
            gap: 14px;
        }

        .outlet-mobile-card__stat,
        .outlet-mobile-card__line span {
            font-size: 0.9rem;
        }

        .outlet-mobile-card__actions {
            grid-template-columns: minmax(0, 1fr) 48px;
        }

        .outlet-mobile-card__edit {
            min-height: 44px;
            font-size: 0.84rem;
        }

        .outlet-mobile-card__delete {
            width: 44px;
            height: 44px;
            min-height: 44px;
        }
    }
</style>
@endsection

@section('content')
@php
    $query = trim((string) request('q', ''));
@endphp

<div class="outlet-desktop-view">
    <div class="page-header">
        <div class="page-title">
            <h1 class="text-dark">Outlet Management</h1>
            <p>Manage outlet details, locations, and access points.</p>
        </div>
        @canany(['manage-outlets', 'create-outlets'])
            <div class="page-actions">
                <a href="{{ route('outlet.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Outlet
                </a>
            </div>
        @endcanany
    </div>

    @include('includes.listing-filter', ['paginator' => $outlets, 'placeholder' => 'Search by outlet name, code, or address...'])

    <div class="table-card">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="table-header">
            <div class="table-title">Outlets</div>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Address</th>
                        <th>Users</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($outlets as $outlet)
                        <tr>
                            <td>{{ $outlet->name }}</td>
                            <td>{{ $outlet->code }}</td>
                            <td>{{ $outlet->address }}</td>
                            <td>{{ $outlet->users_count }}</td>
                            <td>{{ $outlet->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="actions">
                                    @canany(['manage-outlets', 'edit-outlets'])
                                        <a href="{{ route('outlet.edit', $outlet) }}" class="btn btn-sm btn-secondary">Edit</a>
                                    @endcanany

                                    @canany(['manage-outlets', 'delete-outlets'])
                                        <form
                                            action="{{ route('outlet.destroy', $outlet) }}"
                                            method="POST"
                                            class="inline-form"
                                            onsubmit="return confirm('Delete this outlet?');"
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
                            <td colspan="6" class="empty">No outlets found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($outlets->hasPages())
            <div class="pagination">
                {{ $outlets->links() }}
            </div>
        @endif
    </div>
</div>

<div class="outlet-mobile-view">
    <div class="outlet-mobile-shell">
        <section class="outlet-mobile-hero">
            <div>
                <h1>Outlet Management</h1>
                <p>Manage your atelier locations, physical outlets, and regional hubs in one place.</p>
            </div>
            @canany(['manage-outlets', 'create-outlets'])
                <a href="{{ route('outlet.create') }}" class="outlet-mobile-add" aria-label="Add outlet">
                    <i class="fas fa-plus"></i>
                </a>
            @endcanany
        </section>

        @if (session('success'))
            <div class="alert alert-success mb-4">{{ session('success') }}</div>
        @endif

        <section class="outlet-mobile-filters">
            <form method="GET">
                <div class="outlet-mobile-search">
                    <i class="fas fa-search"></i>
                    <input
                        type="text"
                        name="q"
                        value="{{ $query }}"
                        placeholder="Search outlets..."
                    >
                </div>
                <div class="outlet-mobile-actions mt-3">
                    <a href="{{ url()->current() }}" class="btn btn-light">
                        <i class="fas fa-filter"></i>
                        <span>Reset</span>
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i>
                        <span>Apply</span>
                    </button>
                </div>
            </form>
        </section>

        <section class="outlet-mobile-list">
            @forelse ($outlets as $outlet)
                <article class="outlet-mobile-card">
                    <div class="outlet-mobile-card__top">
                        <div>
                            <span class="outlet-mobile-card__code">Outlet Code: {{ $outlet->code }}</span>
                            <h2 class="outlet-mobile-card__title">{{ $outlet->name }}</h2>
                        </div>
                        <span class="outlet-mobile-chip">{{ number_format((int) $outlet->users_count) }} Users</span>
                    </div>

                    <div class="outlet-mobile-card__meta">
                        <div class="outlet-mobile-card__line">
                            <i class="fas fa-location-dot"></i>
                            <span>{{ $outlet->address }}</span>
                        </div>
                    </div>

                    <div class="outlet-mobile-card__stats">
                        <div class="outlet-mobile-card__stat">
                            <i class="fas fa-users"></i>
                            <span>{{ number_format((int) $outlet->users_count) }} Users</span>
                        </div>
                        <div class="outlet-mobile-card__stat">
                            <i class="fas fa-calendar"></i>
                            <span>{{ $outlet->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>

                    <div class="outlet-mobile-card__actions">
                        @canany(['manage-outlets', 'edit-outlets'])
                            <a href="{{ route('outlet.edit', $outlet) }}" class="outlet-mobile-card__edit">
                                <i class="fas fa-pen"></i>
                                <span>Edit</span>
                            </a>
                        @else
                            <div class="outlet-mobile-card__edit">
                                <i class="fas fa-circle-info"></i>
                                <span>View</span>
                            </div>
                        @endcanany

                        @canany(['manage-outlets', 'delete-outlets'])
                            <form
                                action="{{ route('outlet.destroy', $outlet) }}"
                                method="POST"
                                onsubmit="return confirm('Delete this outlet?');"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="outlet-mobile-card__delete" aria-label="Delete {{ $outlet->name }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        @else
                            <div class="outlet-mobile-card__delete" aria-hidden="true">
                                <i class="fas fa-lock"></i>
                            </div>
                        @endcanany
                    </div>
                </article>
            @empty
                <div class="table-card">
                    <div class="empty">No outlets found.</div>
                </div>
            @endforelse
        </section>

        @if ($outlets->hasPages())
            <div class="outlet-mobile-pagination pagination">
                {{ $outlets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
