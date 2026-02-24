<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryIds = ProductCategory::query()->pluck('id', 'slug');
        $unitIds = Unit::query()->pluck('id', 'code');

        $products = [
            [
                'name' => 'Cotton Fabric Roll',
                'sku' => 'FAB-COT-001',
                'category_slug' => 'fabrics',
                'unit_code' => 'METER',
                'description' => 'Breathable cotton fabric for shirts and kurtas.',
                'is_active' => true,
            ],
            [
                'name' => 'Linen Fabric Roll',
                'sku' => 'FAB-LIN-001',
                'category_slug' => 'fabrics',
                'unit_code' => 'METER',
                'description' => 'Premium linen fabric for summer wear.',
                'is_active' => true,
            ],
            [
                'name' => 'Men Shirt Classic',
                'sku' => 'RM-SHIRT-001',
                'category_slug' => 'ready-made',
                'unit_code' => 'INCH',
                'description' => 'Classic fit ready-made men shirt.',
                'is_active' => true,
            ],
            [
                'name' => 'Designer Buttons Set',
                'sku' => 'ACC-BTN-001',
                'category_slug' => 'accessories',
                'unit_code' => 'CM',
                'description' => 'Mixed designer button set for garments.',
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            $categoryId = $categoryIds->get($product['category_slug']);
            $unitId = $unitIds->get($product['unit_code']);

            if (!$categoryId || !$unitId) {
                continue;
            }

            Product::query()->updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'product_category_id' => $categoryId,
                    'unit_id' => $unitId,
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'is_active' => $product['is_active'],
                ]
            );
        }
    }
}
