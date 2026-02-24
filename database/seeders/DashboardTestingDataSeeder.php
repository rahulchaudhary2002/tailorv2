<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerGarmentType;
use App\Models\CustomerMeasurement;
use App\Models\GarmentType;
use App\Models\GarmentTypeMeasurement;
use App\Models\InventoryAlert;
use App\Models\InventoryLocation;
use App\Models\InventoryReorderLevel;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\InventoryType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorRawMaterialPurchase;
use App\Models\VendorType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DashboardTestingDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake();
        $seedTag = 'TST' . now()->format('ymdHis');

        $units = $this->ensureUnits();
        $categories = $this->ensureProductCategories();
        $vendorTypes = $this->ensureVendorTypes();
        $inventoryTypes = $this->ensureInventoryTypes();
        $garmentTypes = $this->ensureGarmentTypes($units);
        $roles = $this->ensureRoles();
        $outlets = $this->ensureOutlets();
        $users = $this->ensureUsers($outlets, $roles, $seedTag);
        $locations = $this->ensureLocations($outlets);
        $customers = $this->seedCustomers($garmentTypes, $seedTag, 120);
        $vendors = $this->seedVendors($vendorTypes, $seedTag, 40);
        [$products, $variantsByProduct] = $this->seedProductsAndVariants($categories, $units, $seedTag, 90);

        $stocks = $this->seedInventoryStocks($products, $variantsByProduct, $locations, $vendors);
        $this->seedReorderLevelsAndAlerts($stocks);
        $this->seedVendorPurchases($products, $variantsByProduct, $vendors, $locations, 220);
        $this->seedOrders($products, $variantsByProduct, $customers, $outlets, $users, 360, $seedTag);
        $this->seedInventoryTransactions(
            $products,
            $variantsByProduct,
            $locations,
            $vendors,
            $inventoryTypes,
            $users,
            260
        );

        $this->command?->info('Dashboard testing data seeded successfully.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, Unit>
     */
    private function ensureUnits()
    {
        $rows = [
            ['name' => 'Piece', 'code' => 'pcs', 'symbol' => 'pc', 'description' => 'Count based unit'],
            ['name' => 'Meter', 'code' => 'mtr', 'symbol' => 'm', 'description' => 'Length unit'],
            ['name' => 'Kilogram', 'code' => 'kg', 'symbol' => 'kg', 'description' => 'Weight unit'],
            ['name' => 'Inch', 'code' => 'inch', 'symbol' => 'in', 'description' => 'Measurement unit'],
        ];

        foreach ($rows as $row) {
            Unit::query()->firstOrCreate(['code' => $row['code']], $row);
        }

        return Unit::query()->orderBy('id')->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, ProductCategory>
     */
    private function ensureProductCategories()
    {
        $rows = [
            ['name' => 'Ready Made', 'slug' => 'ready-made', 'description' => 'Ready to sell garments'],
            ['name' => 'Accessories', 'slug' => 'accessories', 'description' => 'Accessories and add-ons'],
            ['name' => 'Fabrics', 'slug' => 'fabrics', 'description' => 'Fabric stock'],
        ];

        foreach ($rows as $row) {
            ProductCategory::query()->firstOrCreate(['slug' => $row['slug']], $row);
        }

        return ProductCategory::query()->orderBy('id')->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, VendorType>
     */
    private function ensureVendorTypes()
    {
        $rows = [
            ['name' => 'Fabric Supplier', 'description' => 'Supplies fabrics'],
            ['name' => 'Accessories Supplier', 'description' => 'Supplies accessories'],
            ['name' => 'General Supplier', 'description' => 'General purpose supplier'],
        ];

        foreach ($rows as $row) {
            VendorType::query()->firstOrCreate(['name' => $row['name']], $row);
        }

        return VendorType::query()->orderBy('id')->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, InventoryType>
     */
    private function ensureInventoryTypes()
    {
        $rows = [
            ['code' => InventoryType::OUTLET, 'name' => 'Outlet'],
            ['code' => InventoryType::MANUFACTURING, 'name' => 'Manufacturing'],
            ['code' => InventoryType::VENDOR_SUPPLIED, 'name' => 'Vendor Supplied'],
        ];

        foreach ($rows as $row) {
            InventoryType::query()->firstOrCreate(['code' => $row['code']], $row);
        }

        return InventoryType::query()->orderBy('id')->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, GarmentType>
     */
    private function ensureGarmentTypes($units)
    {
        $inchUnitId = (int) ($units->firstWhere('code', 'inch')->id ?? $units->first()->id);

        $garments = [
            [
                'title' => 'Shirt',
                'amount' => 1500,
                'tax' => 5,
                'tailoring_packages' => [
                    ['name' => 'Basic Stitching', 'amount' => 1500, 'description' => 'Standard finish', 'order' => 1, 'is_active' => true],
                    ['name' => 'Premium Stitching', 'amount' => 2200, 'description' => 'Fine finish with extra fitting', 'order' => 2, 'is_active' => true],
                    ['name' => 'Deluxe Stitching', 'amount' => 3000, 'description' => 'Luxury finish with multiple fittings', 'order' => 3, 'is_active' => true],
                ],
            ],
            [
                'title' => 'Pant',
                'amount' => 1800,
                'tax' => 5,
                'tailoring_packages' => [
                    ['name' => 'Basic Stitching', 'amount' => 1800, 'description' => 'Standard finish', 'order' => 1, 'is_active' => true],
                    ['name' => 'Premium Stitching', 'amount' => 2600, 'description' => 'Fine finish with extra fitting', 'order' => 2, 'is_active' => true],
                    ['name' => 'Deluxe Stitching', 'amount' => 3400, 'description' => 'Luxury finish with multiple fittings', 'order' => 3, 'is_active' => true],
                ],
            ],
            [
                'title' => 'Kurta',
                'amount' => 2200,
                'tax' => 5,
                'tailoring_packages' => [
                    ['name' => 'Basic Stitching', 'amount' => 2200, 'description' => 'Standard finish', 'order' => 1, 'is_active' => true],
                    ['name' => 'Premium Stitching', 'amount' => 3000, 'description' => 'Fine finish with extra fitting', 'order' => 2, 'is_active' => true],
                    ['name' => 'Deluxe Stitching', 'amount' => 4200, 'description' => 'Luxury finish with multiple fittings', 'order' => 3, 'is_active' => true],
                ],
            ],
        ];

        foreach ($garments as $garment) {
            $garmentType = GarmentType::query()->firstOrCreate(
                ['title' => $garment['title']],
                [
                    'amount' => (float) $garment['amount'],
                    'tax' => (float) $garment['tax'],
                ]
            );

            $measurementTitles = ['Chest', 'Waist', 'Length'];
            foreach ($measurementTitles as $index => $title) {
                GarmentTypeMeasurement::query()->firstOrCreate(
                    [
                        'garment_type_id' => $garmentType->id,
                        'title' => $title,
                    ],
                    [
                        'unit_id' => $inchUnitId,
                        'order' => $index + 1,
                    ]
                );
            }

            foreach (($garment['tailoring_packages'] ?? []) as $package) {
                $garmentType->tailoringPackages()->updateOrCreate(
                    ['name' => $package['name']],
                    [
                        'amount' => (float) $package['amount'],
                        'description' => $package['description'] ?? null,
                        'order' => (int) ($package['order'] ?? 1),
                        'is_active' => (bool) ($package['is_active'] ?? true),
                    ]
                );
            }
        }

        return GarmentType::query()->with('measurements')->orderBy('id')->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Role>
     */
    private function ensureRoles()
    {
        $rows = [
            ['name' => 'Admin', 'description' => 'Administrator role with full permissions'],
            ['name' => 'Outlet Manager', 'description' => 'Outlet manager role for outlet-level operations and dashboard access'],
            ['name' => 'Worker', 'description' => 'Worker role with limited permissions'],
        ];

        foreach ($rows as $row) {
            Role::query()->firstOrCreate(['name' => $row['name']], $row);
        }

        return Role::query()->orderBy('id')->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Outlet>
     */
    private function ensureOutlets()
    {
        $rows = [
            ['name' => 'Main Outlet', 'code' => 'MAIN', 'address' => 'City Center'],
            ['name' => 'North Outlet', 'code' => 'NORTH', 'address' => 'North Plaza'],
            ['name' => 'South Outlet', 'code' => 'SOUTH', 'address' => 'South Arcade'],
        ];

        foreach ($rows as $row) {
            Outlet::query()->firstOrCreate(['code' => $row['code']], $row);
        }

        return Outlet::query()->orderBy('id')->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function ensureUsers($outlets, $roles, string $seedTag)
    {
        $faker = fake();
        $managerRoleId = (int) ($roles->firstWhere('name', 'Outlet Manager')->id ?? 0);
        $workerRoleId = (int) ($roles->firstWhere('name', 'Worker')->id ?? 0);

        $users = User::query()->where('is_super_admin', false)->get();
        if ($users->count() < 8) {
            $needed = 8 - $users->count();
            for ($i = 1; $i <= $needed; $i++) {
                $firstOutletId = (int) ($outlets->random()->id ?? 0);
                User::query()->create([
                    'name' => $faker->name(),
                    'email' => Str::lower("{$seedTag}.user{$i}@tailor.test"),
                    'password' => Hash::make('password'),
                    'current_outlet_id' => $firstOutletId > 0 ? $firstOutletId : null,
                    'is_super_admin' => false,
                    'email_verified_at' => now(),
                ]);
            }
            $users = User::query()->where('is_super_admin', false)->get();
        }

        foreach ($users as $index => $user) {
            $allowedOutlets = $outlets->shuffle()->take(fake()->numberBetween(1, min(3, $outlets->count())));
            $user->outlets()->syncWithoutDetaching($allowedOutlets->pluck('id')->all());

            if (!$user->current_outlet_id || !$allowedOutlets->pluck('id')->contains((int) $user->current_outlet_id)) {
                $user->current_outlet_id = (int) $allowedOutlets->first()->id;
                $user->save();
            }

            foreach ($allowedOutlets as $outlet) {
                $roleId = $index < 3 ? $managerRoleId : $workerRoleId;
                if ($roleId > 0) {
                    DB::table('user_role')->updateOrInsert(
                        [
                            'user_id' => $user->id,
                            'outlet_id' => $outlet->id,
                            'role_id' => $roleId,
                        ],
                        []
                    );
                }
            }
        }

        return User::query()->where('is_super_admin', false)->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, InventoryLocation>
     */
    private function ensureLocations($outlets)
    {
        foreach ($outlets as $outlet) {
            InventoryLocation::query()->updateOrCreate(
                [
                    'outlet_id' => $outlet->id,
                    'type' => InventoryLocation::TYPE_OUTLET,
                ],
                [
                    'name' => "{$outlet->name} Inventory",
                    'address' => $outlet->address,
                    'code' => 'OUT-' . Str::upper($outlet->code),
                    'is_active' => true,
                ]
            );
        }

        InventoryLocation::query()->firstOrCreate(
            ['code' => 'WH-MAIN'],
            [
                'name' => 'Central Warehouse',
                'type' => InventoryLocation::TYPE_WAREHOUSE,
                'address' => 'Warehouse District',
                'is_active' => true,
            ]
        );

        InventoryLocation::query()->firstOrCreate(
            ['code' => 'FAC-MAIN'],
            [
                'name' => 'Main Factory',
                'type' => InventoryLocation::TYPE_FACTORY,
                'address' => 'Industrial Park',
                'is_active' => true,
            ]
        );

        return InventoryLocation::query()->where('is_active', true)->orderBy('id')->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Customer>
     */
    private function seedCustomers($garmentTypes, string $seedTag, int $count)
    {
        $faker = fake();
        $customerTypes = ['retail', 'wholesale', 'custom'];

        $customers = collect();
        for ($i = 1; $i <= $count; $i++) {
            $customer = Customer::query()->create([
                'name' => $faker->name(),
                'email' => Str::lower("{$seedTag}.customer{$i}@mail.test"),
                'phone' => '9' . str_pad((string) ($i + random_int(100000000, 899999999)), 9, '0', STR_PAD_LEFT),
                'customer_type' => $faker->randomElement($customerTypes),
                'address' => $faker->address(),
            ]);

            $selectedGarments = $garmentTypes->shuffle()->take(random_int(1, min(2, $garmentTypes->count())));
            foreach ($selectedGarments as $garmentType) {
                $customerGarmentType = CustomerGarmentType::query()->firstOrCreate([
                    'customer_id' => $customer->id,
                    'garment_type_id' => $garmentType->id,
                ]);

                foreach ($garmentType->measurements as $index => $measurement) {
                    CustomerMeasurement::query()->create([
                        'customer_garment_type_id' => $customerGarmentType->id,
                        'type' => $measurement->title,
                        'measurement' => (string) random_int(30, 50),
                        'unit' => $measurement->unit?->symbol ?: 'in',
                        'order' => $index + 1,
                    ]);
                }
            }

            $customers->push($customer);
        }

        return $customers;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Vendor>
     */
    private function seedVendors($vendorTypes, string $seedTag, int $count)
    {
        $faker = fake();
        $vendors = collect();

        for ($i = 1; $i <= $count; $i++) {
            $vendor = Vendor::query()->create([
                'vendor_type_id' => (int) $vendorTypes->random()->id,
                'name' => $faker->company() . " {$i}",
                'contact_person' => $faker->name(),
                'email' => Str::lower("{$seedTag}.vendor{$i}@supply.test"),
                'phone' => '8' . str_pad((string) ($i + random_int(100000000, 899999999)), 9, '0', STR_PAD_LEFT),
                'address' => $faker->address(),
                'is_active' => true,
            ]);

            $vendors->push($vendor);
        }

        return $vendors;
    }

    /**
     * @return array{0: \Illuminate\Support\Collection<int, Product>, 1: \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, ProductVariant>>}
     */
    private function seedProductsAndVariants($categories, $units, string $seedTag, int $count): array
    {
        $faker = fake();
        $products = collect();
        $variantsByProduct = collect();

        for ($i = 1; $i <= $count; $i++) {
            $category = $categories->random();
            $unit = $units->random();
            $sku = "{$seedTag}-SKU-" . str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            $product = Product::query()->create([
                'product_category_id' => $category->id,
                'unit_id' => $unit->id,
                'name' => $faker->words(3, true) . " {$i}",
                'sku' => $sku,
                'description' => $faker->sentence(10),
                'is_active' => true,
            ]);

            $products->push($product);

            $variantCount = random_int(0, 3);
            $variants = collect();
            for ($v = 1; $v <= $variantCount; $v++) {
                $variants->push(ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'sku' => "{$sku}-V{$v}",
                    'size' => $faker->randomElement(['S', 'M', 'L', 'XL']),
                    'color' => $faker->safeColorName(),
                    'material' => $faker->randomElement(['Cotton', 'Linen', 'Silk', 'Wool', 'Polyester']),
                ]));
            }

            $variantsByProduct->put($product->id, $variants);
        }

        return [$products, $variantsByProduct];
    }

    /**
     * @return \Illuminate\Support\Collection<int, InventoryStock>
     */
    private function seedInventoryStocks($products, $variantsByProduct, $locations, $vendors)
    {
        $stocks = collect();

        foreach ($products as $product) {
            $targetLocations = $locations->shuffle()->take(random_int(1, min(3, $locations->count())));
            foreach ($targetLocations as $location) {
                $variants = $variantsByProduct->get($product->id, collect());
                $variantId = null;
                if ($variants->isNotEmpty() && random_int(1, 100) <= 70) {
                    $variantId = (int) $variants->random()->id;
                }

                $vendorId = random_int(1, 100) <= 35 ? (int) $vendors->random()->id : null;
                $onHand = random_int(0, 180);
                $avgCost = random_int(150, 4500);
                $basePrice = (float) ($avgCost * random_int(120, 170) / 100);

                $stock = InventoryStock::query()->updateOrCreate(
                    [
                        'location_id' => $location->id,
                        'product_id' => $product->id,
                        'product_variant_id' => $variantId,
                        'vendor_id' => $vendorId,
                    ],
                    [
                        'unit_id' => $product->unit_id,
                        'on_hand_qty' => $onHand,
                        'reserved_qty' => random_int(0, (int) max(0, $onHand / 4)),
                        'avg_cost' => $avgCost,
                        'base_price' => round($basePrice, 2),
                        'special_price' => random_int(1, 100) <= 25 ? round($basePrice * 0.92, 2) : null,
                    ]
                );

                $stocks->push($stock);
            }
        }

        return $stocks;
    }

    private function seedReorderLevelsAndAlerts($stocks): void
    {
        foreach ($stocks as $stock) {
            if (random_int(1, 100) > 60) {
                continue;
            }

            $minQty = random_int(8, 40);
            $reorderQty = $minQty + random_int(8, 60);

            InventoryReorderLevel::query()->updateOrCreate(
                [
                    'product_id' => $stock->product_id,
                    'location_id' => $stock->location_id,
                ],
                [
                    'min_qty' => $minQty,
                    'reorder_qty' => $reorderQty,
                    'is_active' => true,
                ]
            );

            if ((float) $stock->on_hand_qty < $minQty || random_int(1, 100) <= 18) {
                InventoryAlert::query()->create([
                    'product_id' => $stock->product_id,
                    'location_id' => $stock->location_id,
                    'alert_type' => InventoryAlert::TYPE_LOW_STOCK,
                    'current_qty' => (float) $stock->on_hand_qty,
                    'min_qty' => (float) $minQty,
                    'status' => random_int(1, 100) <= 75 ? InventoryAlert::STATUS_OPEN : InventoryAlert::STATUS_CLOSED,
                    'closed_at' => random_int(1, 100) <= 25 ? now()->subDays(random_int(1, 20)) : null,
                    'note' => 'Auto-generated testing alert',
                ]);
            }
        }
    }

    private function seedVendorPurchases($products, $variantsByProduct, $vendors, $locations, int $count): void
    {
        $purchaseLocations = $locations->whereIn('type', [
            InventoryLocation::TYPE_WAREHOUSE,
            InventoryLocation::TYPE_OUTLET,
            InventoryLocation::TYPE_FACTORY,
        ])->values();

        if ($purchaseLocations->isEmpty()) {
            return;
        }

        for ($i = 1; $i <= $count; $i++) {
            $product = $products->random();
            $variants = $variantsByProduct->get($product->id, collect());
            $variantId = $variants->isNotEmpty() && random_int(1, 100) <= 50 ? (int) $variants->random()->id : null;
            $quantity = random_int(5, 150);
            $unitPrice = random_int(80, 2500);
            $total = $quantity * $unitPrice;
            $purchasedAt = now()->subDays(random_int(0, 120))->toDateString();

            VendorRawMaterialPurchase::query()->create([
                'vendor_id' => (int) $vendors->random()->id,
                'product_id' => $product->id,
                'product_variant_id' => $variantId,
                'unit_id' => $product->unit_id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $total,
                'purchased_at' => $purchasedAt,
                'notes' => 'Seeded purchase data',
                'vendor_bill_recorded_at' => now()->subDays(random_int(0, 90)),
                'vendor_bill_number' => 'BILL-' . strtoupper(Str::random(8)),
                'vendor_bill_amount' => $total,
                'inventory_location_id' => (int) $purchaseLocations->random()->id,
                'inventory_updated_at' => now()->subDays(random_int(0, 90)),
            ]);
        }
    }

    private function seedOrders($products, $variantsByProduct, $customers, $outlets, $users, int $count, string $seedTag): void
    {
        $statusPool = [
            Order::STATUS_PENDING,
            Order::STATUS_CONFIRMED,
            Order::STATUS_FABRIC_ISSUED,
            Order::STATUS_ASSIGNED,
            Order::STATUS_IN_PROGRESS,
            Order::STATUS_NEAR_COMPLETION,
            Order::STATUS_COMPLETED,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED,
        ];
        $paymentPool = [
            Order::PAYMENT_STATUS_UNPAID,
            Order::PAYMENT_STATUS_PARTIAL,
            Order::PAYMENT_STATUS_PAID,
        ];

        $workerCandidates = $users->values();
        $creatorCandidates = $users->values();

        for ($i = 1; $i <= $count; $i++) {
            $orderedAt = now()->subDays(random_int(0, 120))->subMinutes(random_int(0, 1200));
            $status = $statusPool[array_rand($statusPool)];
            $paymentStatus = $paymentPool[array_rand($paymentPool)];
            $outlet = $outlets->random();
            $customer = random_int(1, 100) <= 92 ? $customers->random() : null;
            $creator = $creatorCandidates->random();

            $workerId = null;
            $workerAssignedAt = null;
            $workerDeadlineAt = null;
            if (in_array($status, [Order::STATUS_ASSIGNED, Order::STATUS_IN_PROGRESS, Order::STATUS_NEAR_COMPLETION, Order::STATUS_COMPLETED, Order::STATUS_DELIVERED], true) && $workerCandidates->isNotEmpty()) {
                $workerId = (int) $workerCandidates->random()->id;
                $workerAssignedAt = $orderedAt->copy()->addDays(random_int(0, 2));
                $workerDeadlineAt = $workerAssignedAt->copy()->addDays(random_int(1, 7));
            }

            $deliveryDueAt = $orderedAt->copy()->addDays(random_int(2, 14));
            $completedAt = in_array($status, [Order::STATUS_COMPLETED, Order::STATUS_DELIVERED], true)
                ? $deliveryDueAt->copy()->subDays(random_int(0, 3))
                : null;
            $deliveredAt = $status === Order::STATUS_DELIVERED
                ? ($completedAt ? $completedAt->copy()->addHours(random_int(2, 36)) : $deliveryDueAt->copy()->addHours(random_int(4, 30)))
                : null;
            $closedAt = in_array($status, [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED], true)
                ? ($deliveredAt ?: now()->subDays(random_int(0, 10)))
                : null;

            $order = Order::query()->create([
                'order_number' => "{$seedTag}-ORD-" . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'outlet_id' => $outlet->id,
                'customer_id' => $customer?->id,
                'ordered_at' => $orderedAt,
                'delivery_due_at' => $deliveryDueAt,
                'status' => $status,
                'worker_id' => $workerId,
                'worker_assigned_at' => $workerAssignedAt,
                'worker_deadline_at' => $workerDeadlineAt,
                'fabric_issued_at' => in_array($status, [Order::STATUS_FABRIC_ISSUED, Order::STATUS_ASSIGNED, Order::STATUS_IN_PROGRESS, Order::STATUS_NEAR_COMPLETION, Order::STATUS_COMPLETED, Order::STATUS_DELIVERED], true)
                    ? $orderedAt->copy()->addDays(random_int(0, 2))
                    : null,
                'completed_at' => $completedAt,
                'delivered_at' => $deliveredAt,
                'closed_at' => $closedAt,
                'payment_status' => $paymentStatus,
                'payment_method' => random_int(1, 100) <= 85 ? fake()->randomElement(['cash', 'card', 'upi', 'bank_transfer']) : null,
                'advance_payment_amount' => 0,
                'discount_amount' => 0,
                'subtotal_amount' => 0,
                'notes' => 'Seeded order data',
                'created_by' => $creator->id,
            ]);

            $itemCount = random_int(1, 4);
            $subtotal = 0.0;

            for ($line = 1; $line <= $itemCount; $line++) {
                $product = $products->random();
                $variants = $variantsByProduct->get($product->id, collect());
                $variantId = $variants->isNotEmpty() && random_int(1, 100) <= 55 ? (int) $variants->random()->id : null;
                $quantity = random_int(1, 6);
                $unitPrice = random_int(250, 5500);
                $lineTotal = $quantity * $unitPrice;
                $subtotal += $lineTotal;

                $isCustom = random_int(1, 100) <= 12;

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'item_category' => $isCustom ? 'custom' : 'readymade',
                    'product_id' => $isCustom ? null : $product->id,
                    'product_variant_id' => $isCustom ? null : $variantId,
                    'unit_id' => $isCustom ? null : $product->unit_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'custom_details' => $isCustom ? [
                        'fabric_source' => random_int(1, 100) <= 60 ? 'stock' : 'customer',
                        'remarks' => 'Seeded custom order item',
                    ] : null,
                ]);
            }

            $discount = round($subtotal * (random_int(0, 15) / 100), 2);
            $netAmount = max(0, $subtotal - $discount);
            $advance = match ($paymentStatus) {
                Order::PAYMENT_STATUS_PAID => $netAmount,
                Order::PAYMENT_STATUS_PARTIAL => round($netAmount * (random_int(20, 75) / 100), 2),
                default => 0.0,
            };

            $order->update([
                'subtotal_amount' => round($subtotal, 2),
                'discount_amount' => $discount,
                'advance_payment_amount' => $advance,
            ]);
        }
    }

    private function seedInventoryTransactions($products, $variantsByProduct, $locations, $vendors, $inventoryTypes, $users, int $count): void
    {
        $typeMap = $inventoryTypes->keyBy('code');
        $creatorIds = $users->pluck('id')->values();
        if ($creatorIds->isEmpty()) {
            return;
        }

        $trxTypes = [
            InventoryTransaction::TYPE_IN,
            InventoryTransaction::TYPE_OUT,
            InventoryTransaction::TYPE_TRANSFER,
            InventoryTransaction::TYPE_ADJUSTMENT,
        ];

        $statuses = [
            InventoryTransaction::STATUS_PENDING,
            InventoryTransaction::STATUS_PROGRESS,
            InventoryTransaction::STATUS_COMPLETED,
        ];

        for ($i = 1; $i <= $count; $i++) {
            $trxType = $trxTypes[array_rand($trxTypes)];
            $status = $statuses[array_rand($statuses)];
            $trxDate = now()->subDays(random_int(0, 120))->subMinutes(random_int(0, 1440));

            $fromLocationId = null;
            $toLocationId = null;
            if ($trxType === InventoryTransaction::TYPE_TRANSFER) {
                $from = $locations->random();
                $to = $locations->where('id', '!=', $from->id)->values()->random();
                $fromLocationId = $from->id;
                $toLocationId = $to->id;
            } elseif ($trxType === InventoryTransaction::TYPE_IN) {
                $toLocationId = (int) $locations->random()->id;
            } elseif ($trxType === InventoryTransaction::TYPE_OUT) {
                $fromLocationId = (int) $locations->random()->id;
            } else {
                $toLocationId = (int) $locations->random()->id;
            }

            $inventoryTypeId = (int) (
                $typeMap->get(InventoryType::OUTLET)?->id
                ?? $inventoryTypes->first()?->id
                ?? 1
            );

            if (random_int(1, 100) <= 20 && $typeMap->has(InventoryType::MANUFACTURING)) {
                $inventoryTypeId = (int) $typeMap->get(InventoryType::MANUFACTURING)->id;
            } elseif (random_int(1, 100) <= 15 && $typeMap->has(InventoryType::VENDOR_SUPPLIED)) {
                $inventoryTypeId = (int) $typeMap->get(InventoryType::VENDOR_SUPPLIED)->id;
            }

            $referenceType = fake()->randomElement(['order', 'purchase', 'manual_adjustment', 'production_transfer']);
            $targetProductId = null;
            $targetVariantId = null;
            if ($referenceType === 'production_transfer' && random_int(1, 100) <= 60) {
                $targetProduct = $products->random();
                $targetProductId = $targetProduct->id;
                $targetVariants = $variantsByProduct->get($targetProduct->id, collect());
                $targetVariantId = $targetVariants->isNotEmpty() ? (int) $targetVariants->random()->id : null;
            }

            $transaction = InventoryTransaction::query()->create([
                'inventory_type_id' => $inventoryTypeId,
                'trx_type' => $trxType,
                'status' => $status,
                'reference_type' => $referenceType,
                'reference_id' => random_int(1, 10000),
                'target_product_id' => $targetProductId,
                'target_variant_id' => $targetVariantId,
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'vendor_id' => random_int(1, 100) <= 35 ? (int) $vendors->random()->id : null,
                'trx_date' => $trxDate,
                'material_wastage_qty' => $referenceType === 'production_transfer'
                    ? round(random_int(0, 15) / 10, 2)
                    : null,
                'notes' => 'Seeded inventory transaction',
                'created_by' => (int) $creatorIds->random(),
            ]);

            $lineCount = random_int(1, 3);
            for ($line = 1; $line <= $lineCount; $line++) {
                $product = $products->random();
                $variants = $variantsByProduct->get($product->id, collect());
                $variantId = $variants->isNotEmpty() && random_int(1, 100) <= 50 ? (int) $variants->random()->id : null;
                $qty = round(random_int(1, 80) / 2, 2);
                $unitCost = random_int(100, 3000);

                InventoryTransactionItem::query()->create([
                    'inventory_transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variantId,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => round($qty * $unitCost, 2),
                ]);
            }
        }
    }
}
