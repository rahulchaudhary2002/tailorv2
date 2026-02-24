<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productIds = Product::query()->pluck('id', 'sku');

        $variants = [
            [
                'product_sku' => 'FAB-COT-001',
                'sku' => 'FAB-COT-001-WHT',
                'size' => '100m',
                'color' => 'White',
                'material' => 'Cotton',
            ],
            [
                'product_sku' => 'FAB-COT-001',
                'sku' => 'FAB-COT-001-BLU',
                'size' => '100m',
                'color' => 'Blue',
                'material' => 'Cotton',
            ],
            [
                'product_sku' => 'FAB-LIN-001',
                'sku' => 'FAB-LIN-001-BGE',
                'size' => '80m',
                'color' => 'Beige',
                'material' => 'Linen',
            ],
            [
                'product_sku' => 'RM-SHIRT-001',
                'sku' => 'RM-SHIRT-001-M-BLK',
                'size' => 'M',
                'color' => 'Black',
                'material' => 'Cotton Blend',
            ],
            [
                'product_sku' => 'RM-SHIRT-001',
                'sku' => 'RM-SHIRT-001-L-WHT',
                'size' => 'L',
                'color' => 'White',
                'material' => 'Cotton Blend',
            ],
            [
                'product_sku' => 'ACC-BTN-001',
                'sku' => 'ACC-BTN-001-GOLD',
                'size' => null,
                'color' => 'Gold',
                'material' => 'Metal',
            ],
        ];

        foreach ($variants as $variant) {
            $productId = $productIds->get($variant['product_sku']);

            if (!$productId) {
                continue;
            }

            ProductVariant::query()->updateOrCreate(
                ['sku' => $variant['sku']],
                [
                    'product_id' => $productId,
                    'size' => $variant['size'],
                    'color' => $variant['color'],
                    'material' => $variant['material'],
                ]
            );
        }
    }
}
