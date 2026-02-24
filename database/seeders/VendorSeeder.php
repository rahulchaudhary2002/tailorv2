<?php

namespace Database\Seeders;

use App\Models\Vendor;
use App\Models\VendorType;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $typesByName = VendorType::query()
            ->pluck('id', 'name');

        $vendors = [
            [
                'name' => 'Shenzhen Textile House',
                'vendor_type' => 'China Vendors',
                'contact_person' => 'Li Wei',
                'email' => 'shenzhen.textile@example.com',
                'phone' => '+86-755-2000-1100',
                'address' => 'Nanshan District, Shenzhen, China',
            ],
            [
                'name' => 'Surat Fabric Traders',
                'vendor_type' => 'India Vendors',
                'contact_person' => 'Amit Patel',
                'email' => 'surat.fabric@example.com',
                'phone' => '+91-261-3000-2200',
                'address' => 'Ring Road, Surat, Gujarat, India',
            ],
            [
                'name' => 'City Local Cloth Supply',
                'vendor_type' => 'Local Vendors',
                'contact_person' => 'Ravi Kumar',
                'email' => 'city.local.supply@example.com',
                'phone' => '+1-555-010-4422',
                'address' => 'Market Street, Downtown',
            ],
        ];

        foreach ($vendors as $vendor) {
            $vendorTypeId = $typesByName->get($vendor['vendor_type']);

            Vendor::query()->updateOrCreate(
                ['email' => $vendor['email']],
                [
                    'vendor_type_id' => $vendorTypeId,
                    'name' => $vendor['name'],
                    'contact_person' => $vendor['contact_person'],
                    'phone' => $vendor['phone'],
                    'address' => $vendor['address'],
                ]
            );
        }
    }
}
