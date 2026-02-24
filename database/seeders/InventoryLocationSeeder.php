<?php

namespace Database\Seeders;

use App\Models\InventoryLocation;
use App\Models\Outlet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InventoryLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        InventoryLocation::query()->updateOrCreate(
            ['code' => 'WH-MAIN'],
            [
                'name' => 'Main Warehouse',
                'type' => InventoryLocation::TYPE_WAREHOUSE,
                'outlet_id' => null,
                'address' => null,
                'is_active' => true,
            ]
        );

        InventoryLocation::query()->updateOrCreate(
            ['code' => 'MFG-UNIT'],
            [
                'name' => 'Factory Store',
                'type' => InventoryLocation::TYPE_FACTORY,
                'outlet_id' => null,
                'address' => null,
                'is_active' => true,
            ]
        );

        $outlets = Outlet::query()->get(['id', 'name', 'code']);

        foreach ($outlets as $outlet) {
            InventoryLocation::query()->updateOrCreate(
                ['code' => 'OUT-' . Str::upper($outlet->code)],
                [
                    'name' => $outlet->name . ' Inventory',
                    'type' => InventoryLocation::TYPE_OUTLET,
                    'outlet_id' => $outlet->id,
                    'address' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
