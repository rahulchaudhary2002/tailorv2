<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            [
                'name' => 'Inch',
                'code' => 'INCH',
                'symbol' => 'in',
                'description' => 'Imperial length unit.',
            ],
            [
                'name' => 'Centimeter',
                'code' => 'CM',
                'symbol' => 'cm',
                'description' => 'Metric length unit.',
            ],
            [
                'name' => 'Meter',
                'code' => 'METER',
                'symbol' => 'm',
                'description' => 'Metric base length unit.',
            ],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['code' => $unit['code']],
                $unit
            );
        }
    }
}
