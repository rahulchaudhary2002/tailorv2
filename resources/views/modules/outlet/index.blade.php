@extends('layouts.app')

@section('title', 'Outlet Management')


@section('content')
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

@include('includes.reporting-filter', ['paginator' => $outlets, 'placeholder' => 'Search by outlet name, code, or address...', 'reporting' => $reporting])

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
@endsection
