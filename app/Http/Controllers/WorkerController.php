<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreRequest;
use App\Http\Requests\User\UpdatePermissionsRequest;
use App\Http\Requests\User\UpdateRequest;
use App\Models\Outlet;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WorkerController extends Controller
{
    private function workerRole(): ?Role
    {
        return Role::query()->where('name', 'Worker')->first();
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
            $workersQuery->where(function ($query) use ($q): void {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%');
            });
        }

        $reporting = [
            'total' => (clone $workersQuery)->count(),
            'added_this_week' => (clone $workersQuery)->where('created_at', '>=', now()->startOfWeek())->count(),
            'added_this_month' => (clone $workersQuery)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'added_last_30_days' => (clone $workersQuery)->where('created_at', '>=', now()->subDays(30))->count(),
        ];

        $workers = $workersQuery
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('modules.worker.index', compact('workers', 'reporting'));
    }

    public function create()
    {
        $outlets = Outlet::query()->orderBy('name')->get(['id', 'name', 'address']);

        return view('modules.worker.create', compact('outlets'));
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
