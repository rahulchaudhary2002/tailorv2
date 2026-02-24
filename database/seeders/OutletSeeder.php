<?php

namespace Database\Seeders;

use App\Models\Outlet;
use Illuminate\Database\Seeder;

class OutletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Outlet::create([
            'name' => 'Main Outlet',
            'code' => 'MAIN001',
            'address' => '123 Main Street, Cityville',
        ]);

        Outlet::create([
            'name' => 'Branch Outlet',
            'code' => 'BRANCH001',
            'address' => '456 Branch Avenue, Townsville',
        ]);
    }
}
