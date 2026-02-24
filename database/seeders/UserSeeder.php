<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultOutletId = (int) (Outlet::query()->orderBy('id')->value('id') ?? 0);

        $users = [
            [
                'name' => 'Aisha Khan',
                'email' => 'aisha@tailorpro.com',
                'is_super_admin' => false,
            ],
            [
                'name' => 'Rohan Das',
                'email' => 'rohan@tailorpro.com',
                'is_super_admin' => false,
            ],
            [
                'name' => 'Meera Patel',
                'email' => 'meera@tailorpro.com',
                'is_super_admin' => false,
            ],
            [
                'name' => 'Irfan Ali',
                'email' => 'irfan@tailorpro.com',
                'is_super_admin' => false,
            ],
        ];

        foreach ($users as $row) {
            User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'is_super_admin' => (bool) $row['is_super_admin'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'current_outlet_id' => $defaultOutletId > 0 ? $defaultOutletId : null,
                ]
            );
        }

        $admin = User::query()->where('email', 'admin@tailorpro.com')->first();
        if ($admin && !$admin->current_outlet_id && $defaultOutletId > 0) {
            $admin->current_outlet_id = $defaultOutletId;
            $admin->save();
        }
    }
}
