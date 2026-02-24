<?php

namespace Database\Seeders;

use App\Models\VendorType;
use Illuminate\Database\Seeder;

class VendorTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'China Vendors', 'description' => 'Vendors operating from China'],
            ['name' => 'India Vendors', 'description' => 'Vendors operating from India'],
            ['name' => 'Local Vendors', 'description' => 'Local area vendors'],
        ];

        foreach ($types as $type) {
            VendorType::query()->updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
