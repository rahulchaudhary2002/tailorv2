<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreRequest;
use App\Http\Requests\User\UpdatePermissionsRequest;
use App\Http\Requests\User\UpdateRequest;
use App\Http\Requests\User\UpdateRolesRequest;
use App\Models\Outlet;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    private function workerRoleId(): int
    {
        return (int) (Role::query()->where('name', 'Worker')->value('id') ?? 0);
    }

    private function ensureUserEditable(User $user)
    {
        if ($user->is_super_admin) {
            return redirect()
                ->route('user.index')
                ->with('error', 'Super admin users are not editable.');
        }

        return null;
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $qLower = mb_strtolower($q);
        $outletId = (int) $request->query('outlet_id', 0);

        $usersQuery = User::query()
            ->where('is_super_admin', false)
            ->with('currentOutlet:id,name')
            ->withCount('outlets');

        if ($q !== '') {
            $usersQuery->where(function ($query) use ($qLower): void {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . $qLower . '%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%' . $qLower . '%']);
            });
        }

        if ($outletId > 0) {
            $usersQuery->whereHas('outlets', function ($query) use ($outletId): void {
                $query->where('outlets.id', $outletId);
            });
        }

        $users = $usersQuery
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $outlets = Outlet::query()
            ->orderBy('name')
            ->get(['id', 'name', 'address']);

        return view('modules.user.index', compact('users', 'outlets'));
    }

    /**
     * Show user creation form.
     */
    public function create()
    {
        $outlets = Outlet::query()->orderBy('name')->get(['id', 'name', 'address']);

        return view('modules.user.create', compact('outlets'));
    }

    /**
     * Store a new user.
     */
    public function store(StoreRequest $request)
    {
        $validated = $request->validated();
        $selectedOutletIds = collect($validated['outlet_ids'])->map(fn($id) => (int) $id)->values();
        $currentOutletId = (int) $selectedOutletIds->first();
        $canManageSuperAdmin = (bool) optional($request->user())->is_super_admin;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'avatar' => $request->hasFile('avatar')
                ? $request->file('avatar')->store('avatars', 'public')
                : null,
            'password' => $validated['password'],
            'is_super_admin' => $canManageSuperAdmin ? (bool) ($validated['is_super_admin'] ?? false) : false,
            'current_outlet_id' => $currentOutletId,
        ]);

        $user->outlets()->sync($selectedOutletIds->all());

        $this->notifyUserRecipients(
            'User created',
            'User ' . $user->name . ' was created.',
            route('user.edit', ['user' => $user, 'tab' => 'user'])
        );

        return redirect()
            ->route('user.edit', ['user' => $user, 'tab' => 'roles'])
            ->with('success', 'User created successfully. You can now assign roles and permissions.');
    }

    /**
     * Show user edit form.
     */
    public function edit(User $user)
    {
        if ($response = $this->ensureUserEditable($user)) {
            return $response;
        }

        $user->load(['outlets:id,name,address']);

        $outlets = Outlet::query()->orderBy('name')->get(['id', 'name', 'address']);
        $roles = Role::query()
            ->orderBy('name')
            ->get(['id', 'name', 'description']);
        $permissionsByGroup = Permission::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get(['id', 'group', 'name', 'description'])
            ->groupBy('group');

        $assignmentOutlets = $user->outlets->sortBy('name')->values();
        $requestedAssignmentOutletId = (int) request('assignment_outlet_id', 0);
        $managedOutletId = $requestedAssignmentOutletId > 0
            ? $requestedAssignmentOutletId
            : (int) ($user->current_outlet_id ?? 0);

        if (
            $managedOutletId < 1
            || !$assignmentOutlets->pluck('id')->map(fn ($id) => (int) $id)->contains($managedOutletId)
        ) {
            $managedOutletId = (int) ($assignmentOutlets->first()?->id ?? 0);
        }

        $assignedRoleIds = $managedOutletId
            ? $user->roles()
                ->wherePivot('outlet_id', $managedOutletId)
                ->pluck('roles.id')
                ->map(fn($id) => (int) $id)
                ->all()
            : [];

        $assignedPermissionOverrides = $managedOutletId
            ? $user->permissionOverrides()
                ->wherePivot('outlet_id', $managedOutletId)
                ->pluck('user_permission.type', 'permissions.id')
                ->mapWithKeys(fn($type, $id) => [(int) $id => $type])
                ->all()
            : [];

        return view('modules.user.edit', compact(
            'user',
            'outlets',
            'roles',
            'permissionsByGroup',
            'managedOutletId',
            'assignmentOutlets',
            'assignedRoleIds',
            'assignedPermissionOverrides'
        ));
    }

    /**
     * Update user details.
     */
    public function update(UpdateRequest $request, User $user)
    {
        if ($response = $this->ensureUserEditable($user)) {
            return $response;
        }

        $validated = $request->validated();
        $selectedOutletIds = collect($validated['outlet_ids'])->map(fn($id) => (int) $id)->values()->all();
        $canManageSuperAdmin = (bool) optional($request->user())->is_super_admin;

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($request->hasFile('avatar')) {
            if (!empty($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        if ($canManageSuperAdmin) {
            $user->is_super_admin = (bool) ($validated['is_super_admin'] ?? false);
        }

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $currentOutletId = in_array((int) $user->current_outlet_id, $selectedOutletIds, true)
            ? (int) $user->current_outlet_id
            : (int) ($selectedOutletIds[0] ?? null);

        $user->current_outlet_id = $currentOutletId;
        $user->save();

        $user->outlets()->sync($selectedOutletIds);

        DB::table('user_role')
            ->where('user_id', $user->id)
            ->whereNotIn('outlet_id', $selectedOutletIds)
            ->delete();

        DB::table('user_permission')
            ->where('user_id', $user->id)
            ->whereNotIn('outlet_id', $selectedOutletIds)
            ->delete();

        $this->notifyUserRecipients(
            'User updated',
            'User ' . $user->name . ' details were updated.',
            route('user.edit', ['user' => $user, 'tab' => 'user'])
        );

        return redirect()
            ->route('user.edit', ['user' => $user, 'tab' => 'user'])
            ->with('success', 'User details updated successfully.');
    }

    /**
     * Update user roles for the authenticated user's current outlet.
     */
    public function updateRoles(UpdateRolesRequest $request, User $user)
    {
        if ($response = $this->ensureUserEditable($user)) {
            return $response;
        }

        $validated = $request->validated();
        $outletId = (int) ($validated['assignment_outlet_id'] ?? $user->current_outlet_id ?? 0);
        if (!$outletId) {
            return redirect()
                ->route('user.edit', ['user' => $user, 'tab' => 'roles', 'assignment_outlet_id' => $outletId])
                ->with('error', 'Select a valid assignment outlet before assigning roles.');
        }

        $userOutletIds = $user->outlets()->pluck('outlets.id')->map(fn ($id) => (int) $id)->all();
        if (!in_array($outletId, $userOutletIds, true)) {
            return redirect()
                ->route('user.edit', ['user' => $user, 'tab' => 'roles'])
                ->with('error', 'Selected assignment outlet is not in the user allowed outlets.');
        }

        $user->outlets()->syncWithoutDetaching([$outletId]);
        $roleIds = collect($validated['role_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        DB::table('user_role')
            ->where('user_id', $user->id)
            ->where('outlet_id', $outletId)
            ->delete();

        foreach ($roleIds as $roleId) {
            DB::table('user_role')->insert([
                'user_id' => $user->id,
                'outlet_id' => $outletId,
                'role_id' => $roleId,
            ]);
        }

        $this->notifyUserRecipients(
            'User roles updated',
            'Roles for user ' . $user->name . ' were updated.',
            route('user.edit', ['user' => $user, 'tab' => 'roles', 'assignment_outlet_id' => $outletId])
        );

        return redirect()
            ->route('user.edit', ['user' => $user, 'tab' => 'roles', 'assignment_outlet_id' => $outletId])
            ->with('success', 'User roles updated successfully.');
    }

    /**
     * Update user permission overrides for the authenticated user's current outlet.
     */
    public function updatePermissions(UpdatePermissionsRequest $request, User $user)
    {
        if ($response = $this->ensureUserEditable($user)) {
            return $response;
        }

        $validated = $request->validated();

        $outletId = (int) ($validated['assignment_outlet_id'] ?? $user->current_outlet_id ?? 0);
        if (!$outletId) {
            return redirect()
                ->route('user.edit', ['user' => $user, 'tab' => 'permissions', 'assignment_outlet_id' => $outletId])
                ->with('error', 'Select a valid assignment outlet before assigning permissions.');
        }

        $userOutletIds = $user->outlets()->pluck('outlets.id')->map(fn ($id) => (int) $id)->all();
        if (!in_array($outletId, $userOutletIds, true)) {
            return redirect()
                ->route('user.edit', ['user' => $user, 'tab' => 'permissions'])
                ->with('error', 'Selected assignment outlet is not in the user allowed outlets.');
        }

        $user->outlets()->syncWithoutDetaching([$outletId]);
        $permissionOverrides = collect($validated['permission_overrides'] ?? [])
            ->mapWithKeys(fn($type, $permissionId) => [(int) $permissionId => $type])
            ->filter(fn($type) => in_array($type, ['allow', 'deny'], true));

        $validPermissionIds = Permission::query()
            ->whereIn('id', $permissionOverrides->keys()->all())
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        $permissionOverrides = $permissionOverrides->only($validPermissionIds);

        DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('outlet_id', $outletId)
            ->delete();

        foreach ($permissionOverrides as $permissionId => $type) {
            DB::table('user_permission')->insert([
                'user_id' => $user->id,
                'outlet_id' => $outletId,
                'permission_id' => $permissionId,
                'type' => $type,
            ]);
        }

        $this->notifyUserRecipients(
            'User permissions updated',
            'Permissions for user ' . $user->name . ' were updated.',
            route('user.edit', ['user' => $user, 'tab' => 'permissions', 'assignment_outlet_id' => $outletId])
        );

        return redirect()
            ->route('user.edit', ['user' => $user, 'tab' => 'permissions', 'assignment_outlet_id' => $outletId])
            ->with('success', 'User permissions updated successfully.');
    }

    /**
     * Delete a user.
     */
    public function destroy(User $user)
    {
        if ($response = $this->ensureUserEditable($user)) {
            return $response;
        }

        if (Auth::id() === $user->id) {
            return redirect()
                ->route('user.index')
                ->with('error', 'You cannot delete your own user account.');
        }

        $userName = $user->name;
        $user->delete();

        $this->notifyUserRecipients(
            'User deleted',
            'User ' . $userName . ' was deleted.',
            route('user.index')
        );

        return redirect()
            ->route('user.index')
            ->with('success', 'User deleted successfully.');
    }

    private function notifyUserRecipients(string $title, string $message, string $url): void
    {
        $actorName = (string) (auth()->user()?->name ?: 'System');

        app(NotificationService::class)->notifyPermission(
            'receive-user-notifications',
            (int) (auth()->user()?->current_outlet_id ?? 0),
            [
                'title' => $title,
                'message' => $actorName . ': ' . $message,
                'url' => $url,
                'module' => 'User',
            ],
            array_filter([(int) auth()->id()])
        );
    }
}
