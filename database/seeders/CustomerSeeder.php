<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\GarmentType;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'profile' => [
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                    'phone' => '+1-555-123-4567',
                    'address' => '123 Main Street, Springfield',
                ],
                'measurements' => [
                    'Shirt Stitching' => [
                        ['type' => 'Chest', 'measurement' => '102', 'unit' => 'cm'],
                        ['type' => 'Waist', 'measurement' => '92', 'unit' => 'cm'],
                        ['type' => 'Shoulder', 'measurement' => '46', 'unit' => 'cm'],
                        ['type' => 'Sleeve Length', 'measurement' => '62', 'unit' => 'cm'],
                        ['type' => 'Shirt Length', 'measurement' => '74', 'unit' => 'cm'],
                    ],
                    'Pant Stitching' => [
                        ['type' => 'Waist', 'measurement' => '90', 'unit' => 'cm'],
                        ['type' => 'Hip', 'measurement' => '104', 'unit' => 'cm'],
                        ['type' => 'Thigh', 'measurement' => '61', 'unit' => 'cm'],
                        ['type' => 'Inseam', 'measurement' => '80', 'unit' => 'cm'],
                        ['type' => 'Outseam', 'measurement' => '105', 'unit' => 'cm'],
                        ['type' => 'Bottom', 'measurement' => '39', 'unit' => 'cm'],
                    ],
                ],
            ],
            [
                'profile' => [
                    'name' => 'Aarav Sharma',
                    'email' => 'aarav.sharma@example.com',
                    'phone' => '+1-555-987-2211',
                    'address' => '45 Lakeview Avenue, Austin',
                ],
                'measurements' => [
                    'Blazer Stitching' => [
                        ['type' => 'Chest', 'measurement' => '106', 'unit' => 'cm'],
                        ['type' => 'Waist', 'measurement' => '94', 'unit' => 'cm'],
                        ['type' => 'Shoulder', 'measurement' => '47', 'unit' => 'cm'],
                        ['type' => 'Sleeve Length', 'measurement' => '63', 'unit' => 'cm'],
                        ['type' => 'Blazer Length', 'measurement' => '76', 'unit' => 'cm'],
                        ['type' => 'Bicep', 'measurement' => '34', 'unit' => 'cm'],
                    ],
                    'Pant Stitching' => [
                        ['type' => 'Waist', 'measurement' => '88', 'unit' => 'cm'],
                        ['type' => 'Hip', 'measurement' => '100', 'unit' => 'cm'],
                        ['type' => 'Thigh', 'measurement' => '59', 'unit' => 'cm'],
                        ['type' => 'Inseam', 'measurement' => '79', 'unit' => 'cm'],
                        ['type' => 'Outseam', 'measurement' => '103', 'unit' => 'cm'],
                        ['type' => 'Bottom', 'measurement' => '38', 'unit' => 'cm'],
                    ],
                ],
            ],
            [
                'profile' => [
                    'name' => 'Maya Patel',
                    'email' => 'maya.patel@example.com',
                    'phone' => '+1-555-673-4400',
                    'address' => '210 Maple Street, San Jose',
                ],
                'measurements' => [
                    'Shirt Stitching' => [
                        ['type' => 'Chest', 'measurement' => '94', 'unit' => 'cm'],
                        ['type' => 'Waist', 'measurement' => '80', 'unit' => 'cm'],
                        ['type' => 'Shoulder', 'measurement' => '41', 'unit' => 'cm'],
                        ['type' => 'Sleeve Length', 'measurement' => '58', 'unit' => 'cm'],
                        ['type' => 'Shirt Length', 'measurement' => '68', 'unit' => 'cm'],
                    ],
                    'Blazer Stitching' => [
                        ['type' => 'Chest', 'measurement' => '96', 'unit' => 'cm'],
                        ['type' => 'Waist', 'measurement' => '82', 'unit' => 'cm'],
                        ['type' => 'Shoulder', 'measurement' => '42', 'unit' => 'cm'],
                        ['type' => 'Sleeve Length', 'measurement' => '59', 'unit' => 'cm'],
                        ['type' => 'Blazer Length', 'measurement' => '70', 'unit' => 'cm'],
                        ['type' => 'Bicep', 'measurement' => '30', 'unit' => 'cm'],
                    ],
                ],
            ],
        ];

        foreach ($customers as $customerSeed) {
            $profile = $customerSeed['profile'];
            $seedData = $customerSeed['measurements'];

            $customer = Customer::updateOrCreate(
                ['email' => $profile['email']],
                [
                    'name' => $profile['name'],
                    'phone' => $profile['phone'],
                    'address' => $profile['address'],
                ]
            );

            $customer->customerGarmentTypes()->delete();

            $garmentTypes = GarmentType::query()
                ->whereIn('title', array_keys($seedData))
                ->get()
                ->keyBy('title');

            foreach ($seedData as $title => $measurements) {
                $garmentType = $garmentTypes->get($title);

                if (!$garmentType) {
                    continue;
                }

                $customerGarmentType = $customer->customerGarmentTypes()->create([
                    'garment_type_id' => $garmentType->id,
                ]);

                foreach ($measurements as $index => $measurement) {
                    $customerGarmentType->measurements()->create([
                        'type' => $measurement['type'],
                        'measurement' => $measurement['measurement'],
                        'unit' => $measurement['unit'],
                        'order' => $index + 1,
                    ]);
                }
            }
        }
    }
}
