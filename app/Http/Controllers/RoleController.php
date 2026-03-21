<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\StoreRequest;
use App\Http\Requests\Role\UpdatePermissionsRequest;
use App\Http\Requests\Role\UpdateRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    private function ensureRoleEditable(Role $role)
    {
        if ($role->isFixed()) {
            return redirect()
                ->route('role.index')
                ->with('error', sprintf('%s is a fixed system role and cannot be modified.', $role->name));
        }

        return null;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $currentOutletId = auth()->user()?->current_outlet_id;
        $q = trim((string) $request->query('q', ''));
        $qLower = mb_strtolower($q);

        $rolesQuery = Role::query()
            ->with(['permissions:id,name'])
            ->withCount([
                'users as users_count' => function ($query) use ($currentOutletId) {
                    if (!$currentOutletId) {
                        $query->whereRaw('1 = 0');

                        return;
                    }

                    $query->where('user_role.outlet_id', $currentOutletId);
                    $query->where('users.is_super_admin', false);
                },
            ]);

        if ($q !== '') {
            $rolesQuery->where(function ($query) use ($qLower): void {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . $qLower . '%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%' . $qLower . '%']);
            });
        }

        $roles = $rolesQuery
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('modules.role.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissionPreview = Permission::query()
            ->orderBy('group')
            ->orderBy('name')
            ->limit(3)
            ->get(['id', 'name', 'group']);

        return view('modules.role.create', compact('permissionPreview'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $validated = $request->validated();

        $role = Role::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('role.edit', ['role' => $role, 'tab' => 'permissions'])
            ->with('success', 'Role created successfully. You can now assign permissions.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        return redirect()->route('role.edit', $role);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $role->load('permissions:id');

        $permissionsByGroup = Permission::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get(['id', 'group', 'name', 'description'])
            ->groupBy('group');

        return view('modules.role.edit', compact('role', 'permissionsByGroup'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Role $role)
    {
        if ($response = $this->ensureRoleEditable($role)) {
            return $response;
        }

        $validated = $request->validated();

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('role.edit', ['role' => $role, 'tab' => 'details'])
            ->with('success', 'Role details updated successfully.');
    }

    /**
     * Update role permissions.
     */
    public function updatePermissions(UpdatePermissionsRequest $request, Role $role)
    {
        if ($response = $this->ensureRoleEditable($role)) {
            return $response;
        }

        $validated = $request->validated();

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()
            ->route('role.edit', ['role' => $role, 'tab' => 'permissions'])
            ->with('success', 'Role permissions updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        if ($response = $this->ensureRoleEditable($role)) {
            return $response;
        }

        if ($role->users()->exists()) {
            return redirect()
                ->route('role.index')
                ->with('error', 'Role cannot be deleted while it is assigned to users.');
        }

        $role->delete();

        return redirect()
            ->route('role.index')
            ->with('success', 'Role deleted successfully.');
    }
}
