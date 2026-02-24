<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::query()->where('name', 'Admin')->first();
        $workerRole = Role::query()->where('name', 'Worker')->first();
        $users = User::query()->get(['id', 'email', 'is_super_admin']);

        if ($users->isEmpty() || (!$adminRole && !$workerRole)) {
            return;
        }

        foreach ($users as $user) {
            $outletIds = $user->outlets()->pluck('outlets.id');
            if ($outletIds->isEmpty()) {
                continue;
            }

            $roleId = null;
            if ((bool) $user->is_super_admin && $adminRole) {
                $roleId = $adminRole->id;
            } elseif ($workerRole) {
                $roleId = $workerRole->id;
            }

            if (!$roleId) {
                continue;
            }

            foreach ($outletIds as $outletId) {
                DB::table('user_role')->updateOrInsert([
                    'user_id' => $user->id,
                    'outlet_id' => $outletId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }
}
