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

        DB::table('permissions')->upsert([
            [
                'key' => 'view-settings',
                'name' => 'View Settings',
                'group' => 'Settings',
                'description' => 'Permission to view application settings',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'manage-settings',
                'name' => 'Manage Settings',
                'group' => 'Settings',
                'description' => 'Permission to update application settings',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['key'], ['name', 'group', 'description', 'updated_at']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')->whereIn('key', [
            'view-settings',
            'manage-settings',
        ])->delete();
    }
};
