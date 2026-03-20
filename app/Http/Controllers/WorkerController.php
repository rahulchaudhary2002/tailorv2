<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreRequest;
use App\Http\Requests\User\UpdatePermissionsRequest;
use App\Http\Requests\User\UpdateRequest;
use App\Models\Outlet;
use App\Models\OrderTask;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderTaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WorkerController extends Controller
{
    public function __construct(private readonly OrderTaskService $taskService)
    {
    }

    private function workerRole(): ?Role
    {
        return Role::query()->where('name', 'Worker')->first();
    }

    private function ensureWorkerTaskAccessible(User $worker): void
    {
        $outletId = (int) (auth()->user()?->current_outlet_id ?? 0);
        $workerRoleId = (int) optional($this->workerRole())->id;

        $accessible = User::query()
            ->whereKey($worker->id)
            ->where('is_super_admin', false)
            ->whereExists(function ($query) use ($workerRoleId, $outletId): void {
                $query->selectRaw('1')
                    ->from('user_role')
                    ->whereColumn('user_role.user_id', 'users.id')
                    ->where('user_role.role_id', $workerRoleId);

                if ($outletId > 0) {
                    $query->where('user_role.outlet_id', $outletId);
                }
            })
            ->exists();

        if (!$accessible) {
            abort(404);
        }
    }

    private function ensureWorkerEditable(User $user)
    {
        if ($user->is_super_admin) {
            return redirect()
                ->route('worker.index')
                ->with('error', 'Super admin users cannot be managed from workers.');
        }

        if (!$user->roles()->where('roles.name', 'Worker')->exists()) {
            return redirect()
                ->route('worker.index')
                ->with('error', 'Selected user is not a worker.');
        }

        return null;
    }

    private function ensureWorkerRoleExists()
    {
        if ($this->workerRole()) {
            return null;
        }

        return redirect()
            ->route('worker.index')
            ->with('error', 'Worker role is not available.');
    }

    private function syncWorkerRole(User $user, array $outletIds): void
    {
        $workerRoleId = (int) optional($this->workerRole())->id;

        if ($workerRoleId < 1) {
            return;
        }

        DB::table('user_role')
            ->where('user_id', $user->id)
            ->whereIn('outlet_id', $outletIds)
            ->delete();

        foreach ($outletIds as $outletId) {
            DB::table('user_role')->insert([
                'user_id' => $user->id,
                'outlet_id' => $outletId,
                'role_id' => $workerRoleId,
            ]);
        }
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $qLower = mb_strtolower($q);
        $outletId = (int) $request->query('outlet_id', 0);
        $workerRoleId = (int) optional($this->workerRole())->id;

        $workersQuery = User::query()
            ->where('is_super_admin', false)
            ->whereExists(function ($query) use ($workerRoleId): void {
                $query->selectRaw('1')
                    ->from('user_role')
                    ->whereColumn('user_role.user_id', 'users.id')
                    ->where('user_role.role_id', $workerRoleId);
            })
            ->with('currentOutlet:id,name')
            ->withCount('outlets');

        if ($q !== '') {
            $workersQuery->where(function ($query) use ($qLower): void {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . $qLower . '%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%' . $qLower . '%']);
            });
        }

        if ($outletId > 0) {
            $workersQuery->whereHas('outlets', function ($query) use ($outletId): void {
                $query->where('outlets.id', $outletId);
            });
        }

        $workers = $workersQuery
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $outlets = Outlet::query()
            ->orderBy('name')
            ->get(['id', 'name', 'address']);

        return view('modules.worker.index', compact('workers', 'outlets'));
    }

    public function create()
    {
        $outlets = Outlet::query()->orderBy('name')->get(['id', 'name', 'address']);

        return view('modules.worker.create', compact('outlets'));
    }

    public function tasks(Request $request, User $worker)
    {
        $this->ensureWorkerTaskAccessible($worker);

        $outletId = (int) (auth()->user()?->current_outlet_id ?? 0);
        $this->taskService->syncForOutlet($outletId);

        $q = trim((string) $request->query('q', ''));
        $qLower = mb_strtolower($q);
        $status = trim((string) $request->query('status', ''));
        $deadlineFrom = trim((string) $request->query('deadline_from', ''));
        $deadlineTo = trim((string) $request->query('deadline_to', ''));

        $tasksQuery = OrderTask::query()
            ->with([
                'order:id,order_number,customer_id,delivery_due_at,outlet_id',
                'order.customer:id,name,phone',
            ])
            ->where('worker_id', $worker->id)
            ->where('status', '!=', OrderTask::STATUS_PENDING)
            ->whereHas('order', function ($query) use ($outletId): void {
                $query->where('outlet_id', $outletId);
            });

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

        if ($deadlineFrom !== '') {
            $tasksQuery->whereDate('worker_deadline_at', '>=', $deadlineFrom);
        }

        if ($deadlineTo !== '') {
            $tasksQuery->whereDate('worker_deadline_at', '<=', $deadlineTo);
        }

        $reporting = [
            'assigned' => (clone $tasksQuery)->where('status', OrderTask::STATUS_ASSIGNED)->count(),
            'in_progress' => (clone $tasksQuery)->where('status', OrderTask::STATUS_IN_PROGRESS)->count(),
            'completed' => (clone $tasksQuery)->where('status', OrderTask::STATUS_COMPLETED)->count(),
            'cancelled' => (clone $tasksQuery)->where('status', OrderTask::STATUS_CANCELLED)->count(),
            'total_payable' => (float) ((clone $tasksQuery)->sum('payable_amount') ?: 0),
        ];

        $tasks = $tasksQuery
            ->orderByRaw("CASE status
                WHEN ? THEN 1
                WHEN ? THEN 2
                WHEN ? THEN 3
                WHEN ? THEN 4
                ELSE 5
            END", [
                OrderTask::STATUS_ASSIGNED,
                OrderTask::STATUS_IN_PROGRESS,
                OrderTask::STATUS_COMPLETED,
                OrderTask::STATUS_CANCELLED,
            ])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('modules.worker.tasks', [
            'worker' => $worker,
            'tasks' => $tasks,
            'statusLabels' => OrderTask::statusLabels(),
            'selectedStatus' => $status,
            'selectedDeadlineFrom' => $deadlineFrom,
            'selectedDeadlineTo' => $deadlineTo,
            'reporting' => $reporting,
        ]);
    }

    public function store(StoreRequest $request)
    {
        if ($response = $this->ensureWorkerRoleExists()) {
            return $response;
        }

        $validated = $request->validated();
        $selectedOutletIds = collect($validated['outlet_ids'])->map(fn ($id) => (int) $id)->values()->all();
        $currentOutletId = (int) ($selectedOutletIds[0] ?? 0);

        $worker = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'avatar' => $request->hasFile('avatar')
                ? $request->file('avatar')->store('avatars', 'public')
                : null,
            'password' => $validated['password'],
            'is_super_admin' => false,
            'current_outlet_id' => $currentOutletId,
        ]);

        $worker->outlets()->sync($selectedOutletIds);
        $this->syncWorkerRole($worker, $selectedOutletIds);

        return redirect()
            ->route('worker.edit', ['worker' => $worker, 'tab' => 'permissions'])
            ->with('success', 'Worker created successfully. Worker role has been assigned automatically.');
    }

    public function edit(User $worker)
    {
        if ($response = $this->ensureWorkerEditable($worker)) {
            return $response;
        }

        $worker->load(['outlets:id,name,address']);

        $outlets = Outlet::query()->orderBy('name')->get(['id', 'name', 'address']);
        $permissionsByGroup = Permission::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get(['id', 'group', 'name', 'description'])
            ->groupBy('group');

        $assignmentOutlets = $worker->outlets->sortBy('name')->values();
        $requestedAssignmentOutletId = (int) request('assignment_outlet_id', 0);
        $managedOutletId = $requestedAssignmentOutletId > 0
            ? $requestedAssignmentOutletId
            : (int) ($worker->current_outlet_id ?? 0);

        if (
            $managedOutletId < 1
            || !$assignmentOutlets->pluck('id')->map(fn ($id) => (int) $id)->contains($managedOutletId)
        ) {
            $managedOutletId = (int) ($assignmentOutlets->first()?->id ?? 0);
        }

        $assignedPermissionOverrides = $managedOutletId
            ? $worker->permissionOverrides()
                ->wherePivot('outlet_id', $managedOutletId)
                ->pluck('user_permission.type', 'permissions.id')
                ->mapWithKeys(fn ($type, $id) => [(int) $id => $type])
                ->all()
            : [];

        return view('modules.worker.edit', compact(
            'worker',
            'outlets',
            'permissionsByGroup',
            'managedOutletId',
            'assignmentOutlets',
            'assignedPermissionOverrides'
        ));
    }

    public function update(UpdateRequest $request, User $worker)
    {
        if ($response = $this->ensureWorkerEditable($worker)) {
            return $response;
        }

        if ($response = $this->ensureWorkerRoleExists()) {
            return $response;
        }

        $validated = $request->validated();
        $selectedOutletIds = collect($validated['outlet_ids'])->map(fn ($id) => (int) $id)->values()->all();

        $worker->name = $validated['name'];
        $worker->email = $validated['email'];
        $worker->is_super_admin = false;

        if ($request->hasFile('avatar')) {
            if (!empty($worker->avatar)) {
                Storage::disk('public')->delete($worker->avatar);
            }

            $worker->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        if (!empty($validated['password'])) {
            $worker->password = $validated['password'];
        }

        $currentOutletId = in_array((int) $worker->current_outlet_id, $selectedOutletIds, true)
            ? (int) $worker->current_outlet_id
            : (int) ($selectedOutletIds[0] ?? null);

        $worker->current_outlet_id = $currentOutletId;
        $worker->save();

        $worker->outlets()->sync($selectedOutletIds);

        DB::table('user_role')
            ->where('user_id', $worker->id)
            ->whereNotIn('outlet_id', $selectedOutletIds)
            ->delete();

        DB::table('user_permission')
            ->where('user_id', $worker->id)
            ->whereNotIn('outlet_id', $selectedOutletIds)
            ->delete();

        $this->syncWorkerRole($worker, $selectedOutletIds);

        return redirect()
            ->route('worker.edit', ['worker' => $worker, 'tab' => 'worker'])
            ->with('success', 'Worker details updated successfully.');
    }

    public function updatePermissions(UpdatePermissionsRequest $request, User $worker)
    {
        if ($response = $this->ensureWorkerEditable($worker)) {
            return $response;
        }

        $validated = $request->validated();

        $outletId = (int) ($validated['assignment_outlet_id'] ?? $worker->current_outlet_id ?? 0);
        if (!$outletId) {
            return redirect()
                ->route('worker.edit', ['worker' => $worker, 'tab' => 'permissions', 'assignment_outlet_id' => $outletId])
                ->with('error', 'Select a valid assignment outlet before assigning permissions.');
        }

        $workerOutletIds = $worker->outlets()->pluck('outlets.id')->map(fn ($id) => (int) $id)->all();
        if (!in_array($outletId, $workerOutletIds, true)) {
            return redirect()
                ->route('worker.edit', ['worker' => $worker, 'tab' => 'permissions'])
                ->with('error', 'Selected assignment outlet is not in the worker allowed outlets.');
        }

        $worker->outlets()->syncWithoutDetaching([$outletId]);
        $permissionOverrides = collect($validated['permission_overrides'] ?? [])
            ->mapWithKeys(fn ($type, $permissionId) => [(int) $permissionId => $type])
            ->filter(fn ($type) => in_array($type, ['allow', 'deny'], true));

        $validPermissionIds = Permission::query()
            ->whereIn('id', $permissionOverrides->keys()->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $permissionOverrides = $permissionOverrides->only($validPermissionIds);

        DB::table('user_permission')
            ->where('user_id', $worker->id)
            ->where('outlet_id', $outletId)
            ->delete();

        foreach ($permissionOverrides as $permissionId => $type) {
            DB::table('user_permission')->insert([
                'user_id' => $worker->id,
                'outlet_id' => $outletId,
                'permission_id' => $permissionId,
                'type' => $type,
            ]);
        }

        return redirect()
            ->route('worker.edit', ['worker' => $worker, 'tab' => 'permissions', 'assignment_outlet_id' => $outletId])
            ->with('success', 'Worker permissions updated successfully.');
    }

    public function destroy(User $worker)
    {
        if ($response = $this->ensureWorkerEditable($worker)) {
            return $response;
        }

        if (Auth::id() === $worker->id) {
            return redirect()
                ->route('worker.index')
                ->with('error', 'You cannot delete your own worker account.');
        }

        if (!empty($worker->avatar)) {
            Storage::disk('public')->delete($worker->avatar);
        }

        $worker->delete();

        return redirect()
            ->route('worker.index')
            ->with('success', 'Worker deleted successfully.');
    }
}
