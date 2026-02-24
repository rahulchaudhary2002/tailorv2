<?php

namespace Database\Seeders;

use App\Models\InventoryType;
use Illuminate\Database\Seeder;

class InventoryTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['code' => InventoryType::OUTLET, 'name' => 'Outlet Inventory'],
            ['code' => InventoryType::MANUFACTURING, 'name' => 'Manufacturing Inventory'],
            ['code' => InventoryType::VENDOR_SUPPLIED, 'name' => 'Vendor Supplied Inventory'],
        ];

        foreach ($types as $type) {
            InventoryType::query()->updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
