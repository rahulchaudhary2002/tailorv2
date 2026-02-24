<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allPermissionIds = Permission::query()->pluck('id')->all();
        $permissionIdsByKey = Permission::query()->pluck('id', 'key');
        $dashboardPermissionIds = Permission::query()
            ->where('group', 'Dashboard')
            ->pluck('id')
            ->all();
        $assignedJobsPermissionId = Permission::query()
            ->where('key', 'view-assigned-jobs')
            ->value('id');

        foreach (Role::all() as $role) {
            if ($role->name === 'Admin') {
                $role->permissions()->sync($allPermissionIds);
            } elseif ($role->name === 'Outlet Manager') {
                $outletManagerKeys = [
                    'view-dashboard',
                    'view-customers',
                    'create-customers',
                    'edit-customers',
                    'view-products',
                    'create-products',
                    'edit-products',
                    'view-vendors',
                    'create-vendors',
                    'edit-vendors',
                    'view-raw-material-purchases',
                    'create-raw-material-purchases',
                    'manage-raw-material-purchases',
                    'view-manufacture-unit',
                    'manage-manufacture-unit',
                    'view-inventory',
                    'manage-inventory',
                    'view-orders',
                    'create-orders',
                    'manage-orders',
                    'view-assigned-jobs',
                    'view-units',
                    'view-garment-types',
                ];

                $outletManagerPermissionIds = collect($outletManagerKeys)
                    ->map(fn (string $key) => (int) ($permissionIdsByKey[$key] ?? 0))
                    ->filter(fn (int $id) => $id > 0)
                    ->values()
                    ->all();

                $role->permissions()->sync($outletManagerPermissionIds);
            } elseif ($role->name === 'Worker') {
                $workerPermissionIds = collect($dashboardPermissionIds)
                    ->when($assignedJobsPermissionId, function ($ids) use ($assignedJobsPermissionId) {
                        return $ids->push((int) $assignedJobsPermissionId);
                    })
                    ->unique()
                    ->values()
                    ->all();

                $role->permissions()->sync($workerPermissionIds);
            }
        }
    }
}
