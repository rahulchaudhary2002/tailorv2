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
            [
                'key' => 'receive-order-notifications',
                'name' => 'Receive Order Notifications',
                'group' => 'Notifications',
                'description' => 'Receive notifications for order creation, updates, status changes, and payments',
            ],
            [
                'key' => 'receive-purchase-notifications',
                'name' => 'Receive Purchase Notifications',
                'group' => 'Notifications',
                'description' => 'Receive notifications for raw material purchase creation and updates',
            ],
            [
                'key' => 'receive-task-notifications',
                'name' => 'Receive Task Notifications',
                'group' => 'Notifications',
                'description' => 'Receive notifications for task assignment and task progress updates',
            ],
            [
                'key' => 'receive-inventory-notifications',
                'name' => 'Receive Inventory Notifications',
                'group' => 'Notifications',
                'description' => 'Receive notifications for inventory adjustments and transfers',
            ],
            [
                'key' => 'receive-customer-notifications',
                'name' => 'Receive Customer Notifications',
                'group' => 'Notifications',
                'description' => 'Receive notifications for customer create, update, and delete actions',
            ],
            [
                'key' => 'receive-product-notifications',
                'name' => 'Receive Product Notifications',
                'group' => 'Notifications',
                'description' => 'Receive notifications for product create, update, and delete actions',
            ],
            [
                'key' => 'receive-vendor-notifications',
                'name' => 'Receive Vendor Notifications',
                'group' => 'Notifications',
                'description' => 'Receive notifications for vendor create, update, and delete actions',
            ],
            [
                'key' => 'receive-user-notifications',
                'name' => 'Receive User Notifications',
                'group' => 'Notifications',
                'description' => 'Receive notifications for user and worker management actions',
            ],
        ];

        DB::table('permissions')->upsert(
            array_map(fn (array $permission) => $permission + [
                'created_at' => $now,
                'updated_at' => $now,
            ], $permissions),
            ['key'],
            ['name', 'group', 'description', 'updated_at']
        );

        $workerRoleId = (int) (DB::table('roles')->where('name', 'Worker')->value('id') ?? 0);
        $taskPermissionId = (int) (DB::table('permissions')->where('key', 'receive-task-notifications')->value('id') ?? 0);

        if ($workerRoleId > 0 && $taskPermissionId > 0) {
            DB::table('role_permission')->updateOrInsert([
                'role_id' => $workerRoleId,
                'permission_id' => $taskPermissionId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('key', [
                'receive-order-notifications',
                'receive-purchase-notifications',
                'receive-task-notifications',
                'receive-inventory-notifications',
                'receive-customer-notifications',
                'receive-product-notifications',
                'receive-vendor-notifications',
                'receive-user-notifications',
            ])
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('role_permission')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('user_permission')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('permissions')->whereIn('key', [
            'receive-order-notifications',
            'receive-purchase-notifications',
            'receive-task-notifications',
            'receive-inventory-notifications',
            'receive-customer-notifications',
            'receive-product-notifications',
            'receive-vendor-notifications',
            'receive-user-notifications',
        ])->delete();
    }
};
