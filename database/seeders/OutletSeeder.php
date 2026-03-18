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
            'name' => 'Branch Outlet',
            'code' => 'BRANCH001',
            'address' => '456 Branch Avenue, Townsville',
        ]);
    }
}
