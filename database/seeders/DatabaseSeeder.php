<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            OutletSeeder::class,
            InventoryLocationSeeder::class,
            InventoryTypeSeeder::class,
            UnitSeeder::class,
            ProductSeeder::class,
            GarmentTypeSeeder::class,
            CustomerSeeder::class,
            VendorTypeSeeder::class,
            VendorSeeder::class,
        ]);
    }
}
