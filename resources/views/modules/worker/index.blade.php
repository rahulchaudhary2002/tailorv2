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

@include('includes.reporting-filter', ['paginator' => $workers, 'placeholder' => 'Search by worker name or email...', 'reporting' => $reporting])

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
                                <img src="{{ asset('storage/' . $worker->avatar) }}" alt="{{ $worker->name }}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
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
