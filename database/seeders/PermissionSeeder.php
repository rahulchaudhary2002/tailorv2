<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'key' => 'view-dashboard',
                'name' => 'View Dashboard',
                'group' => 'Dashboard',
                'description' => 'Permission to view the dashboard'
            ],
            [
                'key' => 'manage-outlets',
                'name' => 'Manage Outlets',
                'group' => 'Outlet Management',
                'description' => 'Full outlet access, including view, create, edit, and delete actions'
            ],
            [
                'key' => 'view-outlets',
                'name' => 'View Outlets',
                'group' => 'Outlet Management',
                'description' => 'Permission to view outlets'
            ],
            [
                'key' => 'create-outlets',
                'name' => 'Create Outlets',
                'group' => 'Outlet Management',
                'description' => 'Permission to create new outlets'
            ],
            [
                'key' => 'edit-outlets',
                'name' => 'Edit Outlets',
                'group' => 'Outlet Management',
                'description' => 'Permission to edit existing outlets'
            ],
            [
                'key' => 'delete-outlets',
                'name' => 'Delete Outlets',
                'group' => 'Outlet Management',
                'description' => 'Permission to delete outlets'
            ],
            [
                'key' => 'view-roles',
                'name' => 'View Roles',
                'group' => 'Role Management',
                'description' => 'Permission to view available roles'
            ],
            [
                'key' => 'create-roles',
                'name' => 'Create Roles',
                'group' => 'Role Management',
                'description' => 'Permission to create new roles'
            ],
            [
                'key' => 'edit-roles',
                'name' => 'Edit Roles',
                'group' => 'Role Management',
                'description' => 'Permission to update role details and permissions'
            ],
            [
                'key' => 'manage-roles',
                'name' => 'Manage Roles',
                'group' => 'Role Management',
                'description' => 'Full role access, including view, create, edit, and delete actions'
            ],
            [
                'key' => 'delete-roles',
                'name' => 'Delete Roles',
                'group' => 'Role Management',
                'description' => 'Permission to delete roles'
            ],
            [
                'key' => 'view-users',
                'name' => 'View Users',
                'group' => 'User Management',
                'description' => 'Permission to view users'
            ],
            [
                'key' => 'create-users',
                'name' => 'Create Users',
                'group' => 'User Management',
                'description' => 'Permission to create new users'
            ],
            [
                'key' => 'edit-users',
                'name' => 'Edit Users',
                'group' => 'User Management',
                'description' => 'Permission to update user details, roles, and permissions'
            ],
            [
                'key' => 'manage-users',
                'name' => 'Manage Users',
                'group' => 'User Management',
                'description' => 'Full user access, including view, create, edit, and delete actions'
            ],
            [
                'key' => 'delete-users',
                'name' => 'Delete Users',
                'group' => 'User Management',
                'description' => 'Permission to delete users'
            ],
            [
                'key' => 'view-units',
                'name' => 'View Units',
                'group' => 'Unit Management',
                'description' => 'Permission to view measurement units'
            ],
            [
                'key' => 'create-units',
                'name' => 'Create Units',
                'group' => 'Unit Management',
                'description' => 'Permission to create measurement units'
            ],
            [
                'key' => 'edit-units',
                'name' => 'Edit Units',
                'group' => 'Unit Management',
                'description' => 'Permission to edit measurement units'
            ],
            [
                'key' => 'manage-units',
                'name' => 'Manage Units',
                'group' => 'Unit Management',
                'description' => 'Full unit access, including view, create, edit, and delete actions'
            ],
            [
                'key' => 'delete-units',
                'name' => 'Delete Units',
                'group' => 'Unit Management',
                'description' => 'Permission to delete measurement units'
            ],
            [
                'key' => 'view-customers',
                'name' => 'View Customers',
                'group' => 'Customer Management',
                'description' => 'Permission to view customers'
            ],
            [
                'key' => 'create-customers',
                'name' => 'Create Customers',
                'group' => 'Customer Management',
                'description' => 'Permission to create customers'
            ],
            [
                'key' => 'edit-customers',
                'name' => 'Edit Customers',
                'group' => 'Customer Management',
                'description' => 'Permission to edit customers'
            ],
            [
                'key' => 'manage-customers',
                'name' => 'Manage Customers',
                'group' => 'Customer Management',
                'description' => 'Full customer access, including view, create, edit, and delete actions'
            ],
            [
                'key' => 'delete-customers',
                'name' => 'Delete Customers',
                'group' => 'Customer Management',
                'description' => 'Permission to delete customers'
            ],
            [
                'key' => 'view-products',
                'name' => 'View Products',
                'group' => 'Product Management',
                'description' => 'Permission to view products'
            ],
            [
                'key' => 'create-products',
                'name' => 'Create Products',
                'group' => 'Product Management',
                'description' => 'Permission to create products'
            ],
            [
                'key' => 'edit-products',
                'name' => 'Edit Products',
                'group' => 'Product Management',
                'description' => 'Permission to edit products'
            ],
            [
                'key' => 'manage-products',
                'name' => 'Manage Products',
                'group' => 'Product Management',
                'description' => 'Full product access, including view, create, edit, and delete actions'
            ],
            [
                'key' => 'delete-products',
                'name' => 'Delete Products',
                'group' => 'Product Management',
                'description' => 'Permission to delete products'
            ],
            [
                'key' => 'view-vendors',
                'name' => 'View Vendors',
                'group' => 'Vendor Management',
                'description' => 'Permission to view vendors'
            ],
            [
                'key' => 'create-vendors',
                'name' => 'Create Vendors',
                'group' => 'Vendor Management',
                'description' => 'Permission to create vendors'
            ],
            [
                'key' => 'edit-vendors',
                'name' => 'Edit Vendors',
                'group' => 'Vendor Management',
                'description' => 'Permission to edit vendors'
            ],
            [
                'key' => 'manage-vendors',
                'name' => 'Manage Vendors',
                'group' => 'Vendor Management',
                'description' => 'Full vendor access, including view, create, edit, and delete actions'
            ],
            [
                'key' => 'delete-vendors',
                'name' => 'Delete Vendors',
                'group' => 'Vendor Management',
                'description' => 'Permission to delete vendors'
            ],
            [
                'key' => 'view-raw-material-purchases',
                'name' => 'View Raw Material Purchases',
                'group' => 'Raw Material Purchase',
                'description' => 'Permission to view raw material purchases'
            ],
            [
                'key' => 'create-raw-material-purchases',
                'name' => 'Create Raw Material Purchases',
                'group' => 'Raw Material Purchase',
                'description' => 'Permission to create raw material purchases'
            ],
            [
                'key' => 'manage-raw-material-purchases',
                'name' => 'Manage Raw Material Purchases',
                'group' => 'Raw Material Purchase',
                'description' => 'Full raw material purchase access'
            ],
            [
                'key' => 'view-manufacture-unit',
                'name' => 'View Manufacture Unit',
                'group' => 'Manufacture Unit',
                'description' => 'Permission to view manufacture unit stock'
            ],
            [
                'key' => 'manage-manufacture-unit',
                'name' => 'Manage Manufacture Unit',
                'group' => 'Manufacture Unit',
                'description' => 'Full manufacture unit stock access'
            ],
            [
                'key' => 'view-inventory',
                'name' => 'View Inventory',
                'group' => 'Inventory Management',
                'description' => 'Permission to view inventory by location'
            ],
            [
                'key' => 'manage-inventory',
                'name' => 'Manage Inventory',
                'group' => 'Inventory Management',
                'description' => 'Permission to adjust inventory for outlets, warehouse, and manufacturing'
            ],
            [
                'key' => 'view-orders',
                'name' => 'View Orders',
                'group' => 'Order Management',
                'description' => 'Permission to view orders'
            ],
            [
                'key' => 'create-orders',
                'name' => 'Create Orders',
                'group' => 'Order Management',
                'description' => 'Permission to create orders'
            ],
            [
                'key' => 'manage-orders',
                'name' => 'Manage Orders',
                'group' => 'Order Management',
                'description' => 'Full order management access'
            ],
            [
                'key' => 'view-assigned-jobs',
                'name' => 'View Assigned Jobs',
                'group' => 'Order Management',
                'description' => 'Permission to view and access assigned tailoring jobs'
            ],
            [
                'key' => 'view-garment-types',
                'name' => 'View Garment Types',
                'group' => 'Garment Type Management',
                'description' => 'Permission to view garment types'
            ],
            [
                'key' => 'create-garment-types',
                'name' => 'Create Garment Types',
                'group' => 'Garment Type Management',
                'description' => 'Permission to create garment types'
            ],
            [
                'key' => 'edit-garment-types',
                'name' => 'Edit Garment Types',
                'group' => 'Garment Type Management',
                'description' => 'Permission to edit garment types and measurements'
            ],
            [
                'key' => 'manage-garment-types',
                'name' => 'Manage Garment Types',
                'group' => 'Garment Type Management',
                'description' => 'Full garment type access, including view, create, edit, and delete actions'
            ],
            [
                'key' => 'delete-garment-types',
                'name' => 'Delete Garment Types',
                'group' => 'Garment Type Management',
                'description' => 'Permission to delete garment types'
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['key' => $permission['key']],
                $permission
            );
        }
    }
}
