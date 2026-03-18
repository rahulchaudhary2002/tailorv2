<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $permissions = [
            ['key' => 'view-dashboard', 'name' => 'View Dashboard', 'group' => 'Dashboard', 'description' => 'Permission to view the dashboard'],
            ['key' => 'manage-outlets', 'name' => 'Manage Outlets', 'group' => 'Outlet Management', 'description' => 'Full outlet access, including view, create, edit, and delete actions'],
            ['key' => 'view-outlets', 'name' => 'View Outlets', 'group' => 'Outlet Management', 'description' => 'Permission to view outlets'],
            ['key' => 'create-outlets', 'name' => 'Create Outlets', 'group' => 'Outlet Management', 'description' => 'Permission to create new outlets'],
            ['key' => 'edit-outlets', 'name' => 'Edit Outlets', 'group' => 'Outlet Management', 'description' => 'Permission to edit existing outlets'],
            ['key' => 'delete-outlets', 'name' => 'Delete Outlets', 'group' => 'Outlet Management', 'description' => 'Permission to delete outlets'],
            ['key' => 'view-roles', 'name' => 'View Roles', 'group' => 'Role Management', 'description' => 'Permission to view available roles'],
            ['key' => 'create-roles', 'name' => 'Create Roles', 'group' => 'Role Management', 'description' => 'Permission to create new roles'],
            ['key' => 'edit-roles', 'name' => 'Edit Roles', 'group' => 'Role Management', 'description' => 'Permission to update role details and permissions'],
            ['key' => 'manage-roles', 'name' => 'Manage Roles', 'group' => 'Role Management', 'description' => 'Full role access, including view, create, edit, and delete actions'],
            ['key' => 'delete-roles', 'name' => 'Delete Roles', 'group' => 'Role Management', 'description' => 'Permission to delete roles'],
            ['key' => 'view-users', 'name' => 'View Users', 'group' => 'User Management', 'description' => 'Permission to view users'],
            ['key' => 'create-users', 'name' => 'Create Users', 'group' => 'User Management', 'description' => 'Permission to create new users'],
            ['key' => 'edit-users', 'name' => 'Edit Users', 'group' => 'User Management', 'description' => 'Permission to update user details, roles, and permissions'],
            ['key' => 'manage-users', 'name' => 'Manage Users', 'group' => 'User Management', 'description' => 'Full user access, including view, create, edit, and delete actions'],
            ['key' => 'delete-users', 'name' => 'Delete Users', 'group' => 'User Management', 'description' => 'Permission to delete users'],
            ['key' => 'view-units', 'name' => 'View Units', 'group' => 'Unit Management', 'description' => 'Permission to view measurement units'],
            ['key' => 'create-units', 'name' => 'Create Units', 'group' => 'Unit Management', 'description' => 'Permission to create measurement units'],
            ['key' => 'edit-units', 'name' => 'Edit Units', 'group' => 'Unit Management', 'description' => 'Permission to edit measurement units'],
            ['key' => 'manage-units', 'name' => 'Manage Units', 'group' => 'Unit Management', 'description' => 'Full unit access, including view, create, edit, and delete actions'],
            ['key' => 'delete-units', 'name' => 'Delete Units', 'group' => 'Unit Management', 'description' => 'Permission to delete measurement units'],
            ['key' => 'view-customers', 'name' => 'View Customers', 'group' => 'Customer Management', 'description' => 'Permission to view customers'],
            ['key' => 'create-customers', 'name' => 'Create Customers', 'group' => 'Customer Management', 'description' => 'Permission to create customers'],
            ['key' => 'edit-customers', 'name' => 'Edit Customers', 'group' => 'Customer Management', 'description' => 'Permission to edit customers'],
            ['key' => 'manage-customers', 'name' => 'Manage Customers', 'group' => 'Customer Management', 'description' => 'Full customer access, including view, create, edit, and delete actions'],
            ['key' => 'delete-customers', 'name' => 'Delete Customers', 'group' => 'Customer Management', 'description' => 'Permission to delete customers'],
            ['key' => 'view-products', 'name' => 'View Products', 'group' => 'Product Management', 'description' => 'Permission to view products'],
            ['key' => 'create-products', 'name' => 'Create Products', 'group' => 'Product Management', 'description' => 'Permission to create products'],
            ['key' => 'edit-products', 'name' => 'Edit Products', 'group' => 'Product Management', 'description' => 'Permission to edit products'],
            ['key' => 'manage-products', 'name' => 'Manage Products', 'group' => 'Product Management', 'description' => 'Full product access, including view, create, edit, and delete actions'],
            ['key' => 'delete-products', 'name' => 'Delete Products', 'group' => 'Product Management', 'description' => 'Permission to delete products'],
            ['key' => 'view-vendors', 'name' => 'View Vendors', 'group' => 'Vendor Management', 'description' => 'Permission to view vendors'],
            ['key' => 'create-vendors', 'name' => 'Create Vendors', 'group' => 'Vendor Management', 'description' => 'Permission to create vendors'],
            ['key' => 'edit-vendors', 'name' => 'Edit Vendors', 'group' => 'Vendor Management', 'description' => 'Permission to edit vendors'],
            ['key' => 'manage-vendors', 'name' => 'Manage Vendors', 'group' => 'Vendor Management', 'description' => 'Full vendor access, including view, create, edit, and delete actions'],
            ['key' => 'delete-vendors', 'name' => 'Delete Vendors', 'group' => 'Vendor Management', 'description' => 'Permission to delete vendors'],
            ['key' => 'view-raw-material-purchases', 'name' => 'View Raw Material Purchases', 'group' => 'Raw Material Purchase', 'description' => 'Permission to view raw material purchases'],
            ['key' => 'create-raw-material-purchases', 'name' => 'Create Raw Material Purchases', 'group' => 'Raw Material Purchase', 'description' => 'Permission to create raw material purchases'],
            ['key' => 'manage-raw-material-purchases', 'name' => 'Manage Raw Material Purchases', 'group' => 'Raw Material Purchase', 'description' => 'Full raw material purchase access'],
            ['key' => 'view-manufacture-unit', 'name' => 'View Manufacture Unit', 'group' => 'Manufacture Unit', 'description' => 'Permission to view manufacture unit stock'],
            ['key' => 'manage-manufacture-unit', 'name' => 'Manage Manufacture Unit', 'group' => 'Manufacture Unit', 'description' => 'Full manufacture unit stock access'],
            ['key' => 'view-inventory', 'name' => 'View Inventory', 'group' => 'Inventory Management', 'description' => 'Permission to view inventory by location'],
            ['key' => 'manage-inventory', 'name' => 'Manage Inventory', 'group' => 'Inventory Management', 'description' => 'Permission to adjust inventory for outlets, warehouse, and manufacturing'],
            ['key' => 'view-orders', 'name' => 'View Orders', 'group' => 'Order Management', 'description' => 'Permission to view orders'],
            ['key' => 'create-orders', 'name' => 'Create Orders', 'group' => 'Order Management', 'description' => 'Permission to create orders'],
            ['key' => 'manage-orders', 'name' => 'Manage Orders', 'group' => 'Order Management', 'description' => 'Full order management access'],
            ['key' => 'view-assigned-jobs', 'name' => 'View Assigned Jobs', 'group' => 'Order Management', 'description' => 'Permission to view and access assigned tailoring jobs'],
            ['key' => 'view-garment-types', 'name' => 'View Garment Types', 'group' => 'Garment Type Management', 'description' => 'Permission to view garment types'],
            ['key' => 'create-garment-types', 'name' => 'Create Garment Types', 'group' => 'Garment Type Management', 'description' => 'Permission to create garment types'],
            ['key' => 'edit-garment-types', 'name' => 'Edit Garment Types', 'group' => 'Garment Type Management', 'description' => 'Permission to edit garment types and measurements'],
            ['key' => 'manage-garment-types', 'name' => 'Manage Garment Types', 'group' => 'Garment Type Management', 'description' => 'Full garment type access, including view, create, edit, and delete actions'],
            ['key' => 'delete-garment-types', 'name' => 'Delete Garment Types', 'group' => 'Garment Type Management', 'description' => 'Permission to delete garment types'],
        ];

        DB::table('permissions')->upsert(
            array_map(fn(array $permission) => $permission + [
                'created_at' => $now,
                'updated_at' => $now,
            ], $permissions),
            ['key'],
            ['name', 'group', 'description', 'updated_at']
        );

        $roles = [
            ['name' => 'Worker', 'description' => 'Worker role with limited permissions'],
        ];

        DB::table('roles')->upsert(
            array_map(fn(array $role) => $role + [
                'created_at' => $now,
                'updated_at' => $now,
            ], $roles),
            ['name'],
            ['description', 'updated_at']
        );

        $roleIdsByName = DB::table('roles')->whereIn('name', ['Worker'])->pluck('id', 'name');
        $permissionIdsByKey = DB::table('permissions')->pluck('id', 'key');

        $workerRoleId = (int) ($roleIdsByName['Worker'] ?? 0);

        if ($workerRoleId > 0) {
            $workerPermissionRows = collect([
                'view-dashboard',
                'view-assigned-jobs',
            ])->map(fn(string $permissionKey) => (int) ($permissionIdsByKey[$permissionKey] ?? 0))
                ->filter(fn(int $permissionId) => $permissionId > 0)
                ->map(fn(int $permissionId) => [
                    'role_id' => $workerRoleId,
                    'permission_id' => $permissionId,
                ])
                ->values()
                ->all();

            DB::table('role_permission')->where('role_id', $workerRoleId)->delete();

            if ($workerPermissionRows !== []) {
                DB::table('role_permission')->insert($workerPermissionRows);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $fixedRoleIds = DB::table('roles')->where('name', 'Worker')->pluck('id');

        if ($fixedRoleIds->isNotEmpty()) {
            DB::table('role_permission')->whereIn('role_id', $fixedRoleIds)->delete();
            DB::table('roles')->whereIn('id', $fixedRoleIds)->delete();
        }

        DB::table('permissions')->whereIn('key', [
            'view-dashboard',
            'manage-outlets',
            'view-outlets',
            'create-outlets',
            'edit-outlets',
            'delete-outlets',
            'view-roles',
            'create-roles',
            'edit-roles',
            'manage-roles',
            'delete-roles',
            'view-users',
            'create-users',
            'edit-users',
            'manage-users',
            'delete-users',
            'view-units',
            'create-units',
            'edit-units',
            'manage-units',
            'delete-units',
            'view-customers',
            'create-customers',
            'edit-customers',
            'manage-customers',
            'delete-customers',
            'view-products',
            'create-products',
            'edit-products',
            'manage-products',
            'delete-products',
            'view-vendors',
            'create-vendors',
            'edit-vendors',
            'manage-vendors',
            'delete-vendors',
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
            'view-garment-types',
            'create-garment-types',
            'edit-garment-types',
            'manage-garment-types',
            'delete-garment-types',
        ])->delete();
    }
};
