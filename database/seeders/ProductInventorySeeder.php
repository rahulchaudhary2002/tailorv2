<?php

namespace Database\Seeders;

use App\Models\InventoryLocation;
use App\Models\InventoryReorderLevel;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::query()
            ->with('category:id,slug')
            ->get();

        $locations = InventoryLocation::query()
            ->where('is_active', true)
            ->get(['id', 'code', 'type']);

        if ($products->isEmpty() || $locations->isEmpty()) {
            return;
        }

        $meterUnitId = Unit::query()
            ->whereIn('code', ['METER', 'meter', 'MTR', 'mtr'])
            ->value('id');

        foreach ($products as $product) {
            $categorySlug = (string) ($product->category?->slug ?? '');

            foreach ($locations as $location) {
                $seed = $this->seedProfileFor($product->code, $location->type);
                if ($seed === null) {
                    continue;
                }

                $unitId = $categorySlug === 'fabrics' ? $meterUnitId : null;

                InventoryStock::query()->updateOrCreate(
                    [
                        'location_id' => $location->id,
                        'product_id' => $product->id,
                        'vendor_id' => null,
                    ],
                    [
                        'unit_id' => $unitId,
                        'on_hand_qty' => $seed['on_hand_qty'],
                        'reserved_qty' => $seed['reserved_qty'],
                        'unit_cost' => $seed['unit_cost'],
                    ]
                );

                InventoryReorderLevel::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'location_id' => $location->id,
                    ],
                    [
                        'min_qty' => $seed['min_qty'],
                        'reorder_qty' => $seed['reorder_qty'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function seedProfileFor(string $productCode, string $locationType): ?array
    {
        $profiles = [
            'FAB-COT-001' => [
                InventoryLocation::TYPE_WAREHOUSE => [120, 12, 580, 25, 60],
                InventoryLocation::TYPE_FACTORY => [48, 6, 610, 18, 40],
                InventoryLocation::TYPE_OUTLET => [24, 3, 640, 10, 24],
            ],
            'FAB-LIN-001' => [
                InventoryLocation::TYPE_WAREHOUSE => [90, 8, 860, 18, 42],
                InventoryLocation::TYPE_FACTORY => [32, 4, 890, 12, 28],
                InventoryLocation::TYPE_OUTLET => [14, 2, 930, 8, 18],
            ],
            'RM-SHIRT-001' => [
                InventoryLocation::TYPE_WAREHOUSE => [36, 4, 980, 10, 24],
                InventoryLocation::TYPE_FACTORY => [10, 1, 1020, 4, 10],
                InventoryLocation::TYPE_OUTLET => [8, 1, 1080, 3, 8],
            ],
            'ACC-BTN-001' => [
                InventoryLocation::TYPE_WAREHOUSE => [220, 20, 120, 40, 120],
                InventoryLocation::TYPE_FACTORY => [80, 8, 135, 20, 60],
                InventoryLocation::TYPE_OUTLET => [45, 5, 145, 12, 36],
            ],
        ];

        if (!isset($profiles[$productCode][$locationType])) {
            return null;
        }

        [$onHandQty, $reservedQty, $unitCost, $minQty, $reorderQty] = $profiles[$productCode][$locationType];

        return [
            'on_hand_qty' => $onHandQty,
            'reserved_qty' => $reservedQty,
            'unit_cost' => $unitCost,
            'min_qty' => $minQty,
            'reorder_qty' => $reorderQty,
        ];
    }
}
