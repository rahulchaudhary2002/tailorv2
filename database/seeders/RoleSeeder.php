<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Admin',
                'description' => 'Administrator role with full permissions'
            ],
            [
                'name' => 'Outlet Manager',
                'description' => 'Outlet manager role for outlet-level operations and dashboard access'
            ],
            [
                'name' => 'Worker',
                'description' => 'Worker role with limited permissions'
            ]
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
