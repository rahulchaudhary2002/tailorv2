@extends('layouts.app')

@section('title', 'Assigned Jobs')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Assigned Jobs</h1>
        <p>Update the status of your assigned tasks here.</p>
    </div>
</div>

@php
    $query = trim((string) request('q', ''));
@endphp

<div class="directory-reporting" style="margin-bottom: 16px;">
    <div class="directory-reporting__filter-bar">
        <div class="directory-reporting__filter-head">
            <h3 class="directory-reporting__filter-title">Filter Records</h3>
            @if ($query !== '' || $selectedStatus !== '')
                <a href="{{ url()->current() }}" class="btn btn-light btn-sm">Clear Filters</a>
            @endif
        </div>

        <form method="GET" class="listing-filter-form">
            <div class="listing-filter-form__fields listing-filter-form__fields--assigned-jobs">
                <div class="outlet-form-group listing-filter-form__field listing-filter-form__field--search">
                    <label for="q_filter">Search</label>
                    <input id="q_filter" type="text" name="q" class="outlet-input" value="{{ $query }}" placeholder="Search by task no, order no, garment, customer...">
                </div>

                <div class="outlet-form-group listing-filter-form__field">
                    <label for="status_filter">Status</label>
                    <select id="status_filter" name="status" class="outlet-input">
                        <option value="">All Statuses</option>
                        @foreach ($statusLabels as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" @selected($selectedStatus === $statusKey)>{{ $statusLabel }}</option>
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
        <div class="table-title">My Tasks</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Task No</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Garment</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th>Slip</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tasks as $task)
                    @php
                        $canSetDeadline = $task->worker_deadline_at === null;
                    @endphp
                    <tr>
                        <td>{{ $task->task_number ?: '-' }}</td>
                        <td>
                            <div>{{ $task->order?->order_number ?: '-' }}</div>
                            <small>{{ $task->order?->outlet?->name ?: '-' }}</small>
                        </td>
                        <td>
                            {{ $task->order?->customer?->name ?: '-' }}
                            @if ($task->order?->customer?->phone)
                                <div><small>{{ $task->order->customer->phone }}</small></div>
                            @endif
                        </td>
                        <td>{{ $task->task_title }}</td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <span>{{ $task->worker_deadline_at?->format('M d, Y h:i A') ?: ($task->order?->delivery_due_at?->format('M d, Y h:i A') ?: '-') }}</span>
                                @if ($canSetDeadline)
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-light"
                                        data-assigned-job-deadline-toggle
                                        aria-label="Set task deadline"
                                        title="Set task deadline"
                                        style="width:26px; height:26px; border-radius:999px; padding:0; display:inline-flex; align-items:center; justify-content:center;"
                                    >
                                        <i class="fas fa-pen"></i>
                                    </button>
                                @endif
                            </div>
                            @if ($canSetDeadline)
                                <form action="{{ route('taskManagement.workerUpdate', $task) }}" method="POST" class="assigned-job-deadline-form" style="display:none; margin-top:8px; align-items:flex-end; gap:8px; flex-wrap:wrap;">
                                    @csrf
                                    @method('PUT')
                                    <input
                                        type="datetime-local"
                                        name="worker_deadline_at"
                                        class="outlet-input"
                                        value="{{ $task->worker_deadline_at?->format('Y-m-d\\TH:i') }}"
                                        style="min-width:220px;"
                                    >
                                    <button type="submit" class="btn btn-sm btn-secondary">Save</button>
                                    <button type="button" class="btn btn-sm btn-light" data-assigned-job-deadline-cancel>Cancel</button>
                                </form>
                            @endif
                        </td>
                        <td>
                            <span class="app-badge {{ \App\Models\OrderTask::statusBadgeClass((string) $task->status) }}">
                                {{ $task->statusLabel() }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('taskManagement.slip', $task) }}" class="btn btn-sm btn-light" target="_blank">Print Slip</a>
                        </td>
                        <td>
                            <form action="{{ route('taskManagement.workerUpdate', $task) }}" method="POST" style="display:flex; gap:8px; align-items:center;">
                                @csrf
                                @method('PUT')
                                <select name="status" class="outlet-input" style="min-width: 180px;" required>
                                    @foreach ($statusLabels as $statusKey => $statusLabel)
                                        <option value="{{ $statusKey }}" @selected((string) $task->status === (string) $statusKey)>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-secondary">Update Status</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty">No assigned tasks found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($tasks->hasPages())
        <div class="pagination">
            {{ $tasks->links() }}
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

    .listing-filter-form__fields--assigned-jobs {
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
        .listing-filter-form__fields--assigned-jobs {
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
<script>
    (() => {
        document.querySelectorAll('[data-assigned-job-deadline-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const cell = button.closest('td');
                const form = cell?.querySelector('.assigned-job-deadline-form');

                document.querySelectorAll('.assigned-job-deadline-form').forEach((otherForm) => {
                    if (otherForm !== form) {
                        otherForm.style.display = 'none';
                    }
                });

                if (form) {
                    form.style.display = form.style.display === 'none' || form.style.display === '' ? 'inline-flex' : 'none';
                }
            });
        });

        document.querySelectorAll('[data-assigned-job-deadline-cancel]').forEach((button) => {
            button.addEventListener('click', () => {
                const form = button.closest('.assigned-job-deadline-form');
                if (form) {
                    form.style.display = 'none';
                }
            });
        });
    })();
</script>
@endsection
