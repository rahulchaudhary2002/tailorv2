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

        $coatMeasurements = [
            'Length',
            'Sleeve',
            'Chest',
            'Waist',
            'Hip',
            'Shoulder',
            'Back',
        ];

        $pantMeasurements = [
            'Length',
            'Waist',
            'Hip',
            'Calf',
            'Bottom',
        ];

        $shirtMeasurements = [
            'Length',
            'Sleeve',
            'Chest',
            'Waist',
            'Hip',
            'Shoulder',
            'Neck',
        ];

        $waistcoatMeasurements = [
            'Length',
            'Chest',
            'Waist',
            'Hip',
            'Shoulder',
        ];

        $jwaricoatMeasurements = [
            'Length',
            'Chest',
            'Waist',
            'Hip',
            'Shoulder',
            'Neck',
        ];

        $traditionalSetMeasurements = [
            'Length',
            'Sleeve',
            'Chest',
            'Waist',
            'Hip',
            'Shoulder',
            'Neck',
            'Round',
            'S.Length',
            'Calf',
            'Bottom',
        ];

        $types = [
            [
                'title' => 'Coat',
                'tailoring_packages' => [
                    ['name' => 'Set regular', 'amount' => 6000, 'order' => 1],
                    ['name' => 'Stylish', 'amount' => 6400, 'order' => 2],
                ],
                'measurements' => $coatMeasurements,
            ],
            [
                'title' => 'Pant',
                'tailoring_packages' => [
                    ['name' => 'Set regular', 'amount' => 600, 'order' => 1],
                    ['name' => 'Premium', 'amount' => 850, 'order' => 2],
                    ['name' => 'Gurkha Pant', 'amount' => 1000, 'order' => 3],
                ],
                'measurements' => $pantMeasurements,
            ],
            [
                'title' => 'Shirt',
                'tailoring_packages' => [
                    ['name' => 'Set regular', 'amount' => 650, 'order' => 1],
                    ['name' => 'Premium', 'amount' => 800, 'order' => 2],
                ],
                'measurements' => $shirtMeasurements,
            ],
            [
                'title' => 'Waistcoat',
                'tailoring_packages' => [
                    ['name' => 'Set Regular', 'amount' => 1800, 'order' => 1],
                    ['name' => 'Premium', 'amount' => 2000, 'order' => 2],
                ],
                'measurements' => $waistcoatMeasurements,
            ],
            [
                'title' => 'Jwaricoat',
                'tailoring_packages' => [
                    ['name' => 'Regular', 'amount' => 2500, 'order' => 1],
                ],
                'measurements' => $jwaricoatMeasurements,
            ],
            [
                'title' => 'Daura Surwal',
                'tailoring_packages' => [
                    ['name' => 'Regular', 'amount' => 1800, 'order' => 1],
                    ['name' => 'Groom Dress', 'amount' => 2500, 'order' => 2],
                ],
                'measurements' => $traditionalSetMeasurements,
            ],
            [
                'title' => 'Kurta Surwal',
                'tailoring_packages' => [
                    ['name' => 'Regular', 'amount' => 1800, 'order' => 1],
                ],
                'measurements' => $traditionalSetMeasurements,
            ],
            [
                'title' => 'Kamij Surwal',
                'tailoring_packages' => [
                    ['name' => 'Regular', 'amount' => 1800, 'order' => 1],
                ],
                'measurements' => $traditionalSetMeasurements,
            ],
            [
                'title' => 'Long Coat',
                'tailoring_packages' => [
                    ['name' => 'Regular', 'amount' => 7500, 'order' => 1],
                ],
                'measurements' => $coatMeasurements,
            ],
        ];

        foreach ($types as $data) {
            $garmentType = GarmentType::query()->updateOrCreate(
                ['title' => $data['title']],
                ['title' => $data['title']]
            );

            $garmentType->measurements()->delete();
            $garmentType->tailoringPackages()->delete();

            foreach ($data['measurements'] as $index => $measurementTitle) {
                $garmentType->measurements()->create([
                    'title' => $measurementTitle,
                    'unit_id' => $defaultUnitId,
                    'order' => $index + 1,
                ]);
            }

            foreach ($data['tailoring_packages'] as $package) {
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
