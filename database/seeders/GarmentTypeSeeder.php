<?php

namespace Database\Seeders;

use App\Models\GarmentType;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class GarmentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unitsByCode = Unit::query()
            ->whereIn('code', ['CM', 'INCH', 'METER'])
            ->get(['id', 'code'])
            ->pluck('id', 'code');

        $defaultUnitId = (int) ($unitsByCode->get('CM') ?? Unit::query()->value('id'));

        if (!$defaultUnitId) {
            return;
        }

        $types = [
            [
                'title' => 'Shirt Stitching',
                'amount' => 500,
                'tax' => 18,
                'tailoring_packages' => [
                    ['name' => 'Basic Stitching', 'amount' => 500, 'description' => 'Standard finish', 'order' => 1, 'is_active' => true],
                    ['name' => 'Premium Stitching', 'amount' => 800, 'description' => 'Fine finish with extra fitting', 'order' => 2, 'is_active' => true],
                    ['name' => 'Deluxe Stitching', 'amount' => 1200, 'description' => 'Luxury finish with multiple fittings', 'order' => 3, 'is_active' => true],
                ],
                'measurements' => [
                    ['title' => 'Chest', 'unit_code' => 'CM', 'order' => 1],
                    ['title' => 'Waist', 'unit_code' => 'CM', 'order' => 2],
                    ['title' => 'Shoulder', 'unit_code' => 'CM', 'order' => 3],
                    ['title' => 'Sleeve Length', 'unit_code' => 'CM', 'order' => 4],
                    ['title' => 'Shirt Length', 'unit_code' => 'CM', 'order' => 5],
                ],
            ],
            [
                'title' => 'Pant Stitching',
                'amount' => 600,
                'tax' => 18,
                'tailoring_packages' => [
                    ['name' => 'Basic Stitching', 'amount' => 600, 'description' => 'Standard finish', 'order' => 1, 'is_active' => true],
                    ['name' => 'Premium Stitching', 'amount' => 950, 'description' => 'Fine finish with extra fitting', 'order' => 2, 'is_active' => true],
                    ['name' => 'Deluxe Stitching', 'amount' => 1400, 'description' => 'Luxury finish with multiple fittings', 'order' => 3, 'is_active' => true],
                ],
                'measurements' => [
                    ['title' => 'Waist', 'unit_code' => 'CM', 'order' => 1],
                    ['title' => 'Hip', 'unit_code' => 'CM', 'order' => 2],
                    ['title' => 'Thigh', 'unit_code' => 'CM', 'order' => 3],
                    ['title' => 'Inseam', 'unit_code' => 'CM', 'order' => 4],
                    ['title' => 'Outseam', 'unit_code' => 'CM', 'order' => 5],
                    ['title' => 'Bottom', 'unit_code' => 'CM', 'order' => 6],
                ],
            ],
            [
                'title' => 'Blazer Stitching',
                'amount' => 1800,
                'tax' => 18,
                'tailoring_packages' => [
                    ['name' => 'Basic Stitching', 'amount' => 1800, 'description' => 'Standard finish', 'order' => 1, 'is_active' => true],
                    ['name' => 'Premium Stitching', 'amount' => 2500, 'description' => 'Fine finish with extra fitting', 'order' => 2, 'is_active' => true],
                    ['name' => 'Deluxe Stitching', 'amount' => 3500, 'description' => 'Luxury finish with multiple fittings', 'order' => 3, 'is_active' => true],
                ],
                'measurements' => [
                    ['title' => 'Chest', 'unit_code' => 'CM', 'order' => 1],
                    ['title' => 'Waist', 'unit_code' => 'CM', 'order' => 2],
                    ['title' => 'Shoulder', 'unit_code' => 'CM', 'order' => 3],
                    ['title' => 'Sleeve Length', 'unit_code' => 'CM', 'order' => 4],
                    ['title' => 'Blazer Length', 'unit_code' => 'CM', 'order' => 5],
                    ['title' => 'Bicep', 'unit_code' => 'CM', 'order' => 6],
                ],
            ],
        ];

        foreach ($types as $data) {
            $garmentType = GarmentType::updateOrCreate(
                ['title' => $data['title']],
                [
                    'amount' => $data['amount'],
                    'tax' => $data['tax'],
                ]
            );

            $garmentType->measurements()->delete();
            $garmentType->tailoringPackages()->delete();

            foreach ($data['measurements'] as $measurement) {
                $garmentType->measurements()->create([
                    'title' => $measurement['title'],
                    'unit_id' => (int) ($unitsByCode->get($measurement['unit_code']) ?? $defaultUnitId),
                    'order' => $measurement['order'],
                ]);
            }

            foreach (($data['tailoring_packages'] ?? []) as $package) {
                $garmentType->tailoringPackages()->create([
                    'name' => $package['name'],
                    'amount' => (float) $package['amount'],
                    'description' => $package['description'] ?? null,
                    'order' => (int) ($package['order'] ?? 1),
                    'is_active' => (bool) ($package['is_active'] ?? true),
                ]);
            }
        }
    }
}
