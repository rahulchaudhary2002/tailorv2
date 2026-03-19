@extends('layouts.app')

@section('title', 'Garment Type Management')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Garment Type Management</h1>
        <p>Manage garment types with tailoring package and measurement count.</p>
    </div>
    @canany(['manage-garment-types', 'create-garment-types'])
        <div class="page-actions">
            <a href="{{ route('garmentType.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Garment Type
            </a>
        </div>
    @endcanany
</div>

@include('includes.reporting-filter', ['paginator' => $garmentTypes, 'placeholder' => 'Search by garment title...', 'reporting' => $reporting])

<div class="table-card">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="table-header">
        <div class="table-title">Garment Types</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Tailoring Packages</th>
                    <th>Measurements</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($garmentTypes as $garmentType)
                    <tr>
                        <td>{{ $garmentType->title }}</td>
                        <td>{{ $garmentType->tailoring_packages_count }}</td>
                        <td>{{ $garmentType->measurements_count }}</td>
                        <td>{{ $garmentType->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="actions">
                                @canany(['manage-garment-types', 'edit-garment-types'])
                                    <a href="{{ route('garmentType.edit', $garmentType) }}" class="btn btn-sm btn-secondary">Edit</a>
                                @endcanany

                                @canany(['manage-garment-types', 'delete-garment-types'])
                                    <form
                                        action="{{ route('garmentType.destroy', $garmentType) }}"
                                        method="POST"
                                        class="inline-form"
                                        onsubmit="return confirm('Delete this garment type and all measurements?');"
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
                        <td colspan="5" class="empty">No garment types found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($garmentTypes->hasPages())
        <div class="pagination">
            {{ $garmentTypes->links() }}
        </div>
    @endif
</div>
@endsection
