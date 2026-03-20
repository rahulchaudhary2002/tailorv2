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
                'design_note' => ['Two button', 'Side vent', 'Notch lapel'],
                'tailoring_packages' => [
                    ['name' => 'Set regular', 'amount' => 6000, 'order' => 1],
                    ['name' => 'Stylish', 'amount' => 6400, 'order' => 2],
                ],
                'measurements' => $coatMeasurements,
            ],
            [
                'title' => 'Pant',
                'design_note' => ['Single pleat', 'Turn-up hem', 'Cross pocket'],
                'tailoring_packages' => [
                    ['name' => 'Set regular', 'amount' => 600, 'order' => 1],
                    ['name' => 'Premium', 'amount' => 850, 'order' => 2],
                    ['name' => 'Gurkha Pant', 'amount' => 1000, 'order' => 3],
                ],
                'measurements' => $pantMeasurements,
            ],
            [
                'title' => 'Shirt',
                'design_note' => ['Spread collar', 'Round cuff', 'Front pocket'],
                'tailoring_packages' => [
                    ['name' => 'Set regular', 'amount' => 650, 'order' => 1],
                    ['name' => 'Premium', 'amount' => 800, 'order' => 2],
                ],
                'measurements' => $shirtMeasurements,
            ],
            [
                'title' => 'Waistcoat',
                'design_note' => ['V neck', 'Five button', 'Double welt pocket'],
                'tailoring_packages' => [
                    ['name' => 'Set Regular', 'amount' => 1800, 'order' => 1],
                    ['name' => 'Premium', 'amount' => 2000, 'order' => 2],
                ],
                'measurements' => $waistcoatMeasurements,
            ],
            [
                'title' => 'Jwaricoat',
                'design_note' => ['Mandarin collar', 'Hidden placket'],
                'tailoring_packages' => [
                    ['name' => 'Regular', 'amount' => 2500, 'order' => 1],
                ],
                'measurements' => $jwaricoatMeasurements,
            ],
            [
                'title' => 'Daura Surwal',
                'design_note' => ['Traditional tie closure', 'Side tassel'],
                'tailoring_packages' => [
                    ['name' => 'Regular', 'amount' => 1800, 'order' => 1],
                    ['name' => 'Groom Dress', 'amount' => 2500, 'order' => 2],
                ],
                'measurements' => $traditionalSetMeasurements,
            ],
            [
                'title' => 'Kurta Surwal',
                'design_note' => ['Band collar', 'Side slit'],
                'tailoring_packages' => [
                    ['name' => 'Regular', 'amount' => 1800, 'order' => 1],
                ],
                'measurements' => $traditionalSetMeasurements,
            ],
            [
                'title' => 'Kamij Surwal',
                'design_note' => ['Straight cut', 'Simple cuff'],
                'tailoring_packages' => [
                    ['name' => 'Regular', 'amount' => 1800, 'order' => 1],
                ],
                'measurements' => $traditionalSetMeasurements,
            ],
            [
                'title' => 'Long Coat',
                'design_note' => ['Peak lapel', 'Back slit', 'Long silhouette'],
                'tailoring_packages' => [
                    ['name' => 'Regular', 'amount' => 7500, 'order' => 1],
                ],
                'measurements' => $coatMeasurements,
            ],
        ];

        foreach ($types as $data) {
            $garmentType = GarmentType::query()->updateOrCreate(
                ['title' => $data['title']],
                [
                    'title' => $data['title'],
                    'design_note' => $data['design_note'] ?? null,
                ]
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
