@extends('layouts.app')

@section('title', 'Unit Management')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Unit Management</h1>
        <p>Manage measurement units used across the system.</p>
    </div>
    @canany(['manage-units', 'create-units'])
        <div class="page-actions">
            <a href="{{ route('unit.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Unit
            </a>
        </div>
    @endcanany
</div>

@include('includes.reporting-filter', ['paginator' => $units, 'placeholder' => 'Search by unit name or symbol...', 'reporting' => $reporting])

<div class="table-card">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-header">
        <div class="table-title">Units</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Symbol</th>
                    <th>Description</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($units as $unit)
                    <tr>
                        <td>{{ $unit->name }}</td>
                        <td>{{ $unit->code }}</td>
                        <td>{{ $unit->symbol ?: '-' }}</td>
                        <td>{{ $unit->description ?: '-' }}</td>
                        <td>{{ $unit->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="actions">
                                @canany(['manage-units', 'edit-units'])
                                    <a href="{{ route('unit.edit', $unit) }}" class="btn btn-sm btn-secondary">Edit</a>
                                @endcanany

                                @canany(['manage-units', 'delete-units'])
                                    <form
                                        action="{{ route('unit.destroy', $unit) }}"
                                        method="POST"
                                        class="inline-form"
                                        onsubmit="return confirm('Delete this unit?');"
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
                        <td colspan="6" class="empty">No units found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($units->hasPages())
        <div class="pagination">
            {{ $units->links() }}
        </div>
    @endif
</div>
@endsection
