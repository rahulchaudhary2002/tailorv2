@extends('layouts.app')

@section('title', 'Worker Management')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Worker Management</h1>
        <p>Manage worker accounts with the fixed worker role assigned automatically.</p>
    </div>
    @canany(['manage-users', 'create-users'])
        <div class="page-actions">
            <a href="{{ route('worker.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Worker
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
            <div class="listing-filter-form__fields listing-filter-form__fields--worker">
                <div class="outlet-form-group listing-filter-form__field listing-filter-form__field--search">
                    <label for="q_filter">Search</label>
                    <input id="q_filter" type="text" name="q" class="outlet-input" value="{{ $query }}" placeholder="Search by worker name or email...">
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
        <div class="table-title">Workers</div>
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
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($workers as $worker)
                    <tr>
                        <td>
                            @if ($worker->avatar)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($worker->avatar) }}" alt="{{ $worker->name }}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            @else
                                <span>-</span>
                            @endif
                        </td>
                        <td>{{ $worker->name }}</td>
                        <td>{{ $worker->email }}</td>
                        <td>{{ $worker->outlets_count }}</td>
                        <td>{{ $worker->currentOutlet?->name ?? '-' }}</td>
                        <td>Worker</td>
                        <td>
                            <div class="actions">
                                @canany(['view-task-management', 'manage-task-management', 'manage-orders'])
                                    <a href="{{ route('worker.tasks', $worker) }}" class="btn btn-sm btn-light">Tasks &amp; Report</a>
                                @endcanany

                                @canany(['manage-users', 'edit-users'])
                                    <a href="{{ route('worker.edit', $worker) }}" class="btn btn-sm btn-secondary">Edit</a>
                                @endcanany

                                @canany(['manage-users', 'delete-users'])
                                    <form
                                        action="{{ route('worker.destroy', $worker) }}"
                                        method="POST"
                                        class="inline-form"
                                        onsubmit="return confirm('Delete this worker?');"
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
                        <td colspan="7" class="empty">No workers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($workers->hasPages())
        <div class="pagination">
            {{ $workers->links() }}
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

    .listing-filter-form__fields--worker {
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
        .listing-filter-form__fields--worker {
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
