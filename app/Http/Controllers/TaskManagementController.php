<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\OrderTask;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\OrderTaskService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskManagementController extends Controller
{
    public function __construct(private readonly OrderTaskService $taskService)
    {
    }

    private function workerRoleId(): int
    {
        return (int) (\App\Models\Role::query()->where('name', 'Worker')->value('id') ?? 0);
    }

    public function index(Request $request)
    {
        $outletId = (int) (auth()->user()?->current_outlet_id ?? 0);
        $workerRoleId = $this->workerRoleId();
        $this->taskService->syncForOutlet($outletId);

        $q = trim((string) $request->query('q', ''));
        $qLower = mb_strtolower($q);
        $status = trim((string) $request->query('status', ''));
        $selectedWorkerId = (int) $request->query('worker_id', 0);
        $deadlineFrom = trim((string) $request->query('deadline_from', ''));
        $deadlineTo = trim((string) $request->query('deadline_to', ''));

        $workers = User::query()
            ->where('is_super_admin', false)
            ->when($workerRoleId > 0, function ($query) use ($workerRoleId, $outletId): void {
                $query->whereHas('roles', function ($roleQuery) use ($workerRoleId, $outletId): void {
                    $roleQuery->where('roles.id', $workerRoleId);
                    if ($outletId > 0) {
                        $roleQuery->where('user_role.outlet_id', $outletId);
                    }
                });
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $validWorkerIds = $workers->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($selectedWorkerId > 0 && !in_array($selectedWorkerId, $validWorkerIds, true)) {
            $selectedWorkerId = 0;
        }

        $tasksQuery = OrderTask::query()
            ->with([
                'order:id,order_number,customer_id,delivery_due_at,outlet_id',
                'order.customer:id,name,phone',
                'worker:id,name',
            ])
            ->whereHas('order', function ($query) use ($outletId): void {
                $query->where('outlet_id', $outletId);
            })
            ->latest('id');

        if ($q !== '') {
            $tasksQuery->where(function ($query) use ($qLower): void {
                $query->whereRaw('LOWER(task_number) LIKE ?', ['%' . $qLower . '%'])
                    ->orWhereRaw('LOWER(task_title) LIKE ?', ['%' . $qLower . '%'])
                    ->orWhereHas('order', function ($orderQuery) use ($qLower): void {
                        $orderQuery->whereRaw('LOWER(order_number) LIKE ?', ['%' . $qLower . '%'])
                            ->orWhereHas('customer', function ($customerQuery) use ($qLower): void {
                                $customerQuery->whereRaw('LOWER(name) LIKE ?', ['%' . $qLower . '%'])
                                    ->orWhereRaw('LOWER(phone) LIKE ?', ['%' . $qLower . '%']);
                            });
                    });
            });
        }

        if ($status !== '' && array_key_exists($status, OrderTask::statusLabels())) {
            $tasksQuery->where('status', $status);
        }

        if ($selectedWorkerId > 0) {
            $tasksQuery->where('worker_id', $selectedWorkerId);
        }

        if ($deadlineFrom !== '') {
            $tasksQuery->whereDate('worker_deadline_at', '>=', $deadlineFrom);
        }

        if ($deadlineTo !== '') {
            $tasksQuery->whereDate('worker_deadline_at', '<=', $deadlineTo);
        }

        $reporting = [
            'total_tasks' => (clone $tasksQuery)->count(),
            'active_tasks' => (clone $tasksQuery)
                ->whereIn('status', [OrderTask::STATUS_ASSIGNED, OrderTask::STATUS_IN_PROGRESS])
                ->count(),
            'completed_tasks' => (clone $tasksQuery)
                ->where('status', OrderTask::STATUS_COMPLETED)
                ->count(),
            'total_payable' => (float) ((clone $tasksQuery)->sum('payable_amount') ?: 0),
        ];

        $tasks = $tasksQuery->paginate(15)->withQueryString();

        return view('modules.task_management.index', [
            'tasks' => $tasks,
            'workers' => $workers,
            'reporting' => $reporting,
            'statusLabels' => OrderTask::statusLabels(),
            'selectedStatus' => $status,
            'selectedWorkerId' => $selectedWorkerId,
            'selectedDeadlineFrom' => $deadlineFrom,
            'selectedDeadlineTo' => $deadlineTo,
        ]);
    }

    public function update(Request $request, OrderTask $task)
    {
        $this->ensureTaskBelongsToCurrentOutlet($task);
        $outletId = (int) (auth()->user()?->current_outlet_id ?? 0);
        $workerRoleId = $this->workerRoleId();
        $previousWorkerId = (int) ($task->worker_id ?? 0);

        $validated = $request->validate([
            'worker_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($outletId, $workerRoleId) {
                    $query->where('is_super_admin', false)
                        ->whereExists(function ($subQuery) use ($workerRoleId): void {
                            $subQuery->selectRaw('1')
                                ->from('user_role')
                                ->whereColumn('user_role.user_id', 'users.id')
                                ->where('user_role.role_id', $workerRoleId);
                        });

                    if ($outletId > 0) {
                        $query->whereExists(function ($subQuery) use ($outletId): void {
                            $subQuery->selectRaw('1')
                                ->from('user_role')
                                ->whereColumn('user_role.user_id', 'users.id')
                                ->where('user_role.outlet_id', $outletId);
                        });
                    }
                }),
            ],
            'worker_deadline_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'slip_received' => ['nullable', 'boolean'],
        ]);

        $workerId = (int) ($validated['worker_id'] ?? 0);

        $task->worker_id = $workerId > 0 ? $workerId : null;
        $task->worker_deadline_at = $validated['worker_deadline_at'] ?? null;
        $task->notes = $validated['notes'] ?? null;

        if ($task->worker_id) {
            if (in_array($task->status, [OrderTask::STATUS_PENDING, OrderTask::STATUS_CANCELLED], true)) {
                $task->status = OrderTask::STATUS_ASSIGNED;
            }

            $task->assigned_at = $task->assigned_at ?? now();
        } else {
            if ($task->status === OrderTask::STATUS_ASSIGNED) {
                $task->status = OrderTask::STATUS_PENDING;
            }

            if ($task->status === OrderTask::STATUS_PENDING) {
                $task->assigned_at = null;
            }
        }

        $task->slip_received_at = $request->boolean('slip_received')
            ? ($task->slip_received_at ?? now())
            : null;
        $task->save();
        $order = $task->order()->firstOrFail();
        $this->taskService->syncOrderStatus($order);

        $task->loadMissing(['order.customer:id,name', 'worker:id,name']);
        $this->notifyTaskRecipients(
            $task,
            'Task assignment updated',
            'Task ' . ($task->task_number ?: $task->task_title) . ' for order ' . ($task->order?->order_number ?: '-') . ' was updated.',
            $task->worker_id && (int) $task->worker_id !== $previousWorkerId ? $task->worker : null
        );

        return back()->with('success', 'Task assignment updated successfully.');
    }

    public function workerUpdate(Request $request, OrderTask $task)
    {
        $this->ensureTaskAccessibleToWorker($task);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', [
                OrderTask::STATUS_ASSIGNED,
                OrderTask::STATUS_IN_PROGRESS,
                OrderTask::STATUS_COMPLETED,
            ])],
        ]);

        $allowedTransitions = [
            OrderTask::STATUS_ASSIGNED => [OrderTask::STATUS_IN_PROGRESS, OrderTask::STATUS_COMPLETED],
            OrderTask::STATUS_IN_PROGRESS => [OrderTask::STATUS_COMPLETED],
            OrderTask::STATUS_COMPLETED => [],
        ];

        $currentStatus = (string) $task->status;
        $targetStatus = (string) $validated['status'];
        $nextStatuses = $allowedTransitions[$currentStatus] ?? [];

        if (!in_array($targetStatus, $nextStatuses, true)) {
            return back()->with('error', 'Invalid task status update.');
        }

        $task->status = $targetStatus;
        if ($targetStatus === OrderTask::STATUS_COMPLETED) {
            $task->completed_at = $task->completed_at ?? now();
        }
        $task->save();
        $order = $task->order()->firstOrFail();
        $this->taskService->syncOrderStatus($order);

        $task->loadMissing(['order.customer:id,name', 'worker:id,name']);
        $this->notifyTaskRecipients(
            $task,
            'Task status updated',
            'Task ' . ($task->task_number ?: $task->task_title) . ' is now ' . $task->statusLabel() . '.'
        );

        return back()->with('success', 'Task status updated successfully.');
    }

    private function notifyTaskRecipients(OrderTask $task, string $title, string $message, ?User $directWorker = null): void
    {
        $notificationService = app(NotificationService::class);
        $taskUrl = route('taskManagement.index', ['q' => $task->task_number ?: $task->task_title]);
        $actorName = (string) (auth()->user()?->name ?: 'System');

        $notificationService->notifyPermission(
            'receive-task-notifications',
            (int) ($task->order?->outlet_id ?? auth()->user()?->current_outlet_id ?? 0),
            [
                'title' => $title,
                'message' => $actorName . ': ' . $message,
                'url' => $taskUrl,
                'module' => 'Task',
            ],
            array_filter([(int) auth()->id(), (int) ($directWorker?->id ?? 0)])
        );

        if ($directWorker) {
            $notificationService->notifyUsers([
                $directWorker,
            ], [
                'title' => 'New task assigned',
                'message' => $actorName . ': You have been assigned task ' . ($task->task_number ?: $task->task_title) . ' for order ' . ($task->order?->order_number ?: '-') . '.',
                'url' => route('order.assignedJobs', ['q' => $task->task_number ?: $task->task_title]),
                'module' => 'Task',
            ]);
        }
    }

    public function slip(OrderTask $task)
    {
        $user = auth()->user();
        $canManageTasks = (bool) ($user?->hasPermission('manage-task-management') || $user?->hasPermission('manage-orders'));

        if ($canManageTasks) {
            $this->ensureTaskBelongsToCurrentOutlet($task);
        } else {
            $this->ensureTaskAccessibleToWorker($task);
        }

        $task->load([
            'order:id,order_number,ordered_at,delivery_due_at,customer_id,outlet_id',
            'order.customer:id,name,phone',
            'order.outlet:id,name',
            'worker:id,name',
            'orderItem:id,order_id,custom_details',
        ]);

        $customDetails = (array) ($task->orderItem?->custom_details ?? []);
        $garment = collect((array) ($customDetails['garments'] ?? []))
            ->get((int) $task->source_garment_index, []);
        $fabricProduct = Product::query()
            ->whereKey((int) data_get($customDetails, 'fabric_product_id', 0))
            ->first(['id', 'name', 'code']);

        return view('modules.task_management.slip', [
            'task' => $task,
            'garment' => (array) $garment,
            'customDetails' => $customDetails,
            'fabricProduct' => $fabricProduct,
        ]);
    }

    private function ensureTaskBelongsToCurrentOutlet(OrderTask $task): void
    {
        $outletId = (int) (auth()->user()?->current_outlet_id ?? 0);

        $belongs = $task->order()
            ->where('outlet_id', $outletId)
            ->exists();

        if (!$belongs) {
            abort(404);
        }
    }

    private function ensureTaskAccessibleToWorker(OrderTask $task): void
    {
        $user = auth()->user();
        $userId = (int) ($user?->id ?? 0);
        $accessibleOutletIds = $user?->outlets()
            ->pluck('outlets.id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values() ?? collect();

        $belongs = $task->order()
            ->whereIn('outlet_id', $accessibleOutletIds)
            ->exists();

        if (!$belongs || (int) ($task->worker_id ?? 0) !== $userId) {
            abort(404);
        }
    }
}
