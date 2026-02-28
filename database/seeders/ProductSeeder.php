<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryIds = ProductCategory::query()->pluck('id', 'slug');
        $products = [
            [
                'name' => 'Cotton Fabric Roll',
                'code' => 'FAB-COT-001',
                'category_slug' => 'fabrics',
                'amount' => 850,
            ],
            [
                'name' => 'Linen Fabric Roll',
                'code' => 'FAB-LIN-001',
                'category_slug' => 'fabrics',
                'amount' => 1200,
            ],
            [
                'name' => 'Men Shirt Classic',
                'code' => 'RM-SHIRT-001',
                'category_slug' => 'ready-made',
                'amount' => 1600,
            ],
            [
                'name' => 'Designer Buttons Set',
                'code' => 'ACC-BTN-001',
                'category_slug' => 'accessories',
                'amount' => 250,
            ],
        ];

        foreach ($products as $product) {
            $categoryId = $categoryIds->get($product['category_slug']);

            if (!$categoryId) {
                continue;
            }

            Product::query()->updateOrCreate(
                ['code' => $product['code']],
                [
                    'product_category_id' => $categoryId,
                    'name' => $product['name'],
                    'code' => $product['code'],
                    'amount' => $product['amount'],
                ]
            );
        }
    }
}
