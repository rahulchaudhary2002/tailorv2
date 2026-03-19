<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $permissions = [
            [
                'key' => 'view-task-management',
                'name' => 'View Task Management',
                'group' => 'Order Management',
                'description' => 'Permission to view task assignments and task slips',
            ],
            [
                'key' => 'manage-task-management',
                'name' => 'Manage Task Management',
                'group' => 'Order Management',
                'description' => 'Permission to assign workers, update task status, and manage task slips',
            ],
            [
                'key' => 'view-payment-management',
                'name' => 'View Payment Management',
                'group' => 'Order Management',
                'description' => 'Permission to view customer receipts and worker payables',
            ],
            [
                'key' => 'manage-payment-management',
                'name' => 'Manage Payment Management',
                'group' => 'Order Management',
                'description' => 'Permission to manage worker payouts and payment tracking',
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
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('key', [
            'view-task-management',
            'manage-task-management',
            'view-payment-management',
            'manage-payment-management',
        ])->delete();
    }
};
