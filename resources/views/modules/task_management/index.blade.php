@extends('layouts.app')

@section('title', 'Task Management')

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Task Management</h1>
        <p>Assign custom dress tasks to workers, track slips, and manage task completion.</p>
    </div>
</div>

@include('includes.reporting-filter', ['paginator' => $tasks, 'placeholder' => 'Search by task no, order no, garment, customer...', 'reporting' => $reporting])

<div class="table-card">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="table-header">
        <div class="table-title">Task Assignments</div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Task No</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Garment</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Payable</th>
                    <th>Assignment</th>
                    <th>Slip</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tasks as $task)
                    <tr>
                        <td>{{ $task->task_number ?: '-' }}</td>
                        <td>
                            <div>{{ $task->order?->order_number ?: '-' }}</div>
                            <small>Due: {{ $task->order?->delivery_due_at?->format('M d, Y h:i A') ?: '-' }}</small>
                        </td>
                        <td>
                            {{ $task->order?->customer?->name ?: '-' }}
                            @if ($task->order?->customer?->phone)
                                <div><small>{{ $task->order->customer->phone }}</small></div>
                            @endif
                        </td>
                        <td>{{ $task->task_title }}</td>
                        <td>{{ number_format((float) $task->quantity, 2) }}</td>
                        <td>{{ number_format((float) $task->rate_amount, 2) }}</td>
                        <td>{{ number_format((float) $task->payable_amount, 2) }}</td>
                        <td>
                            <div>{{ $task->worker?->name ?: '-' }}</div>
                            <div><small>Status: {{ $task->statusLabel() }}</small></div>
                            <div><small>Deadline: {{ $task->worker_deadline_at?->format('M d, Y h:i A') ?: '-' }}</small></div>
                        </td>
                        <td>
                            <div><small>Slip: {{ $task->slip_received_at ? 'Received' : 'Pending' }}</small></div>
                            <div style="margin-top: 8px;">
                                <a href="{{ route('taskManagement.slip', $task) }}" class="btn btn-sm btn-light" target="_blank">Print Slip</a>
                            </div>
                        </td>
                        <td>
                            <button
                                type="button"
                                class="btn btn-sm btn-secondary js-open-assign-modal"
                                data-task-id="{{ $task->id }}"
                                data-task-number="{{ $task->task_number ?: '-' }}"
                                data-order-number="{{ $task->order?->order_number ?: '-' }}"
                                data-customer-name="{{ $task->order?->customer?->name ?: '-' }}"
                                data-task-title="{{ $task->task_title }}"
                                data-worker-id="{{ (int) ($task->worker_id ?? 0) }}"
                                data-worker-deadline="{{ $task->worker_deadline_at?->format('Y-m-d\TH:i') }}"
                                data-notes="{{ $task->notes }}"
                                data-slip-received="{{ $task->slip_received_at ? '1' : '0' }}"
                            >
                                Assign
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="empty">No custom task assignments found.</td>
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

<div id="taskAssignModal" class="app-modal" aria-hidden="true">
    <div class="app-modal__backdrop js-task-modal-close"></div>
    <div class="app-modal__panel task-assign-modal__panel" role="dialog" aria-modal="true" aria-labelledby="taskAssignModalTitle">
        <div class="app-modal__header">
            <div>
                <h3 id="taskAssignModalTitle">Assign Task</h3>
                <p class="app-modal__meta js-task-modal-meta">-</p>
            </div>
            <button type="button" class="task-assign-close js-task-modal-close" aria-label="Close assign modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="taskAssignForm" method="POST">
            @csrf
            @method('PUT')

            <div class="task-assign-grid">
                <div class="outlet-form-group">
                    <label for="taskAssignWorker">Worker</label>
                    <select id="taskAssignWorker" name="worker_id" class="outlet-input">
                        <option value="">Select Worker</option>
                        @foreach ($workers as $worker)
                            <option value="{{ $worker->id }}">{{ $worker->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="outlet-form-group">
                    <label for="taskAssignDeadline">Deadline</label>
                    <input id="taskAssignDeadline" type="datetime-local" name="worker_deadline_at" class="outlet-input">
                </div>

                <div class="outlet-form-group outlet-form-group-full">
                    <label for="taskAssignNotes">Notes</label>
                    <input id="taskAssignNotes" type="text" name="notes" class="outlet-input" placeholder="Notes">
                </div>

                <div class="outlet-form-group outlet-form-group-full">
                    <label class="task-assign-check">
                        <input id="taskAssignSlip" type="checkbox" name="slip_received" value="1">
                        <span>Slip Received</span>
                    </label>
                </div>
            </div>

            <div class="task-assign-actions">
                <button type="button" class="btn btn-sm btn-light js-task-modal-close">Cancel</button>
                <button type="submit" class="btn btn-sm btn-secondary">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('page-specific-style')
<style>
    .app-modal {
        position: fixed;
        inset: 0;
        display: none;
        z-index: 1300;
    }

    .app-modal.is-open {
        display: block;
    }

    .app-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
    }

    .app-modal__panel {
        position: relative;
        width: min(640px, calc(100vw - 32px));
        margin: 48px auto;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
        overflow: hidden;
    }

    .app-modal__header {
        padding: 18px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px solid #e2e8f0;
    }

    .app-modal__header h3 {
        margin: 0;
    }

    .app-modal__meta {
        margin: 6px 0 0;
        color: #64748b;
        font-size: 13px;
    }

    .task-assign-close {
        width: 36px;
        height: 36px;
        border: 1px solid #d7dfeb;
        border-radius: 10px;
        background: #fff;
        color: #334155;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .task-assign-close:hover {
        background: #f8fafc;
    }

    .task-assign-grid {
        padding: 20px;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .outlet-form-group-full {
        grid-column: 1 / -1;
    }

    .task-assign-check {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }

    .task-assign-actions {
        padding: 0 20px 20px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .task-assign-modal__panel .select2-container {
        width: 100% !important;
    }

    .task-assign-modal__panel .select2-container--default .select2-selection--single {
        height: 46px;
        border-radius: 10px;
        border: 1px solid #d7dfeb;
        background: #fff;
    }

    .task-assign-modal__panel .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 44px;
        padding-left: 12px;
        color: #0f172a;
        font-size: 14px;
    }

    .task-assign-modal__panel .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 44px;
        right: 8px;
    }

    body.app-modal-open {
        overflow: hidden;
    }

    @media (max-width: 640px) {
        .task-assign-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('page-specific-script')
<script>
    (() => {
        const body = document.body;
        const modal = document.getElementById('taskAssignModal');
        const form = document.getElementById('taskAssignForm');
        const meta = modal?.querySelector('.js-task-modal-meta');
        const workerInput = document.getElementById('taskAssignWorker');
        const deadlineInput = document.getElementById('taskAssignDeadline');
        const notesInput = document.getElementById('taskAssignNotes');
        const slipInput = document.getElementById('taskAssignSlip');
        const modalPanel = modal.querySelector('.task-assign-modal__panel');

        if (!modal || !form || !workerInput || !deadlineInput || !notesInput || !slipInput) {
            return;
        }

        const initWorkerSelect2 = () => {
            if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                return;
            }

            const $worker = window.jQuery(workerInput);
            if ($worker.hasClass('select2-hidden-accessible')) {
                $worker.off('.taskAssignSelect2');
                $worker.select2('destroy');
            }

            $worker.select2({
                width: '100%',
                placeholder: 'Select Worker',
                allowClear: true,
                dropdownParent: window.jQuery(modalPanel || modal),
            });
        };

        const closeModal = () => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            body.classList.remove('app-modal-open');
        };

        const openModal = () => {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            body.classList.add('app-modal-open');
        };

        modal.querySelectorAll('.js-task-modal-close').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        document.querySelectorAll('.js-open-assign-modal').forEach((button) => {
            button.addEventListener('click', () => {
                form.action = "{{ url('/task-management/update') }}/" + button.dataset.taskId;
                workerInput.value = button.dataset.workerId || '';
                deadlineInput.value = button.dataset.workerDeadline || '';
                notesInput.value = button.dataset.notes || '';
                slipInput.checked = button.dataset.slipReceived === '1';

                if (meta) {
                    meta.textContent = `${button.dataset.taskNumber} | Order ${button.dataset.orderNumber} | ${button.dataset.customerName} | ${button.dataset.taskTitle}`;
                }

                openModal();
                initWorkerSelect2();
                if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                    window.jQuery(workerInput).trigger('change.select2');
                }
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });
    })();
</script>
@endsection
