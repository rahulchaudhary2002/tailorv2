<?php

namespace App\Http\Controllers;

use App\Http\Requests\RawMaterialPurchase\StoreRequest;
use App\Http\Requests\RawMaterialPurchase\UpdateProcurementRequest;
use App\Models\InventoryLocation;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\InventoryType;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Vendor;
use App\Models\VendorRawMaterialPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RawMaterialPurchaseController extends Controller
{
    /**
     * Display raw material purchases.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $purchasesQuery = VendorRawMaterialPurchase::query()
            ->with(['vendor:id,name', 'product:id,name,code', 'unit:id,name,symbol', 'inventoryLocation:id,name,type'])
            ->whereHas('inventoryLocation', function ($query) {
                $query->where('type', InventoryLocation::TYPE_WAREHOUSE);
            })
            ->latest('purchased_at');

        if ($q !== '') {
            $purchasesQuery->where(function ($query) use ($q): void {
                $query->whereHas('vendor', function ($vendorQuery) use ($q): void {
                    $vendorQuery->where('name', 'like', '%' . $q . '%');
                })->orWhereHas('product', function ($productQuery) use ($q): void {
                    $productQuery->where('name', 'like', '%' . $q . '%')
                        ->orWhere('code', 'like', '%' . $q . '%');
                })->orWhere('vendor_bill_number', 'like', '%' . $q . '%');
            });
        }

        $reporting = [
            'total' => (clone $purchasesQuery)->count(),
            'added_this_week' => (clone $purchasesQuery)->where('purchased_at', '>=', now()->startOfWeek()->toDateString())->count(),
            'added_this_month' => (clone $purchasesQuery)->whereBetween('purchased_at', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->count(),
            'added_last_30_days' => (clone $purchasesQuery)->where('purchased_at', '>=', now()->subDays(30)->toDateString())->count(),
        ];

        $purchases = $purchasesQuery
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('modules.raw_material_purchase.index', compact('purchases', 'reporting'));
    }

    /**
     * Show the purchase creation form.
     */
    public function create(Request $request)
    {
        $vendors = Vendor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::query()
            ->whereHas('category', function ($query) {
                $query->where('slug', 'fabrics');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $selectedVendorId = (int) ($request->query('vendor_id') ?? 0);

        return view('modules.raw_material_purchase.create', compact('vendors', 'products', 'selectedVendorId'));
    }

    /**
     * Store a raw material purchase (Step 1: PO created).
     */
    public function store(StoreRequest $request)
    {
        $validated = $request->validated();
        $warehouseLocationId = $this->currentWarehouseLocationId();
        if (!$warehouseLocationId) {
            return redirect()
                ->route('rawMaterialPurchase.index')
                ->with('error', 'No active warehouse location found.');
        }

        $items = collect($validated['items'] ?? [])->values();
        $productIds = $items
            ->pluck('product_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get(['id'])
            ->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get((int) ($item['product_id'] ?? 0));

            if (!$product) {
                return back()
                    ->withInput()
                    ->with('error', 'One or more selected products is invalid.');
            }
        }

        DB::transaction(function () use ($validated, $items, $products, $warehouseLocationId): void {
            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $quantity = (int) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $unitId = $this->resolveInventoryUnitIdForProduct($productId);

                VendorRawMaterialPurchase::query()->create([
                    'vendor_id' => (int) $validated['vendor_id'],
                    'product_id' => $productId,
                    'unit_id' => $unitId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_amount' => $quantity * $unitPrice,
                    'purchased_at' => $validated['purchased_at'],
                    'notes' => $validated['notes'] ?? null,
                    'inventory_location_id' => $warehouseLocationId,
                ]);
            }
        });

        return redirect()
            ->route('rawMaterialPurchase.index')
            ->with('success', 'Purchase order created successfully.');
    }

    /**
     * Show procurement process form for a purchase.
     */
    public function edit(VendorRawMaterialPurchase $purchase)
    {
        $this->ensurePurchaseBelongsToWarehouse($purchase);

        $purchase->load(['vendor:id,name', 'product:id,name,code', 'unit:id,name,symbol', 'inventoryLocation:id,name,type']);

        $inventoryLocations = InventoryLocation::query()
            ->where('is_active', true)
            ->where('type', InventoryLocation::TYPE_WAREHOUSE)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'outlet_id']);

        return view('modules.raw_material_purchase.edit', compact('purchase', 'inventoryLocations'));
    }

    /**
     * Update procurement details:
     * upload bill with amount and optionally update inventory.
     */
    public function updateProcurement(UpdateProcurementRequest $request, VendorRawMaterialPurchase $purchase)
    {
        $this->ensurePurchaseBelongsToWarehouse($purchase);

        $validated = $request->validated();
        $this->validateWarehouseLocationInPayload($validated);

        try {
            DB::transaction(function () use ($purchase, $request, $validated): void {
                $now = now();

                $purchase->unit_id = $this->resolveInventoryUnitIdForProduct((int) $purchase->product_id);

                $purchase->notes = $validated['notes'] ?? $purchase->notes;
                $purchase->vendor_bill_number = trim((string) ($validated['vendor_bill_number'] ?? $purchase->vendor_bill_number));

                if (array_key_exists('vendor_bill_amount', $validated) && $validated['vendor_bill_amount'] !== null) {
                    $purchase->vendor_bill_amount = (float) $validated['vendor_bill_amount'];
                }

                if ($request->hasFile('bill_file')) {
                    if ($purchase->bill_file_path) {
                        Storage::disk('public')->delete($purchase->bill_file_path);
                    }

                    $purchase->bill_file_path = $request->file('bill_file')->store('vendor-bills', 'public');
                    $purchase->vendor_bill_recorded_at = $now;
                }

                if ($request->boolean('update_inventory') && $purchase->inventory_updated_at === null) {
                    $locationId = (int) ($validated['inventory_location_id'] ?? 0);
                    $purchaseQty = (float) $purchase->quantity;
                    $unitPrice = (float) $purchase->unit_price;
                    $inventoryBasePrice = (float) ($validated['inventory_base_price'] ?? $unitPrice);
                    $inventorySpecialPrice = array_key_exists('inventory_special_price', $validated) && $validated['inventory_special_price'] !== null
                        ? (float) $validated['inventory_special_price']
                        : null;

                    $stock = InventoryStock::query()->firstOrCreate(
                        [
                            'location_id' => $locationId,
                            'product_id' => $purchase->product_id,
                            'vendor_id' => $purchase->vendor_id,
                        ],
                        [
                            'unit_id' => $purchase->unit_id,
                            'on_hand_qty' => 0,
                            'reserved_qty' => 0,
                            'avg_cost' => $unitPrice,
                            'base_price' => $inventoryBasePrice,
                            'special_price' => $inventorySpecialPrice,
                        ]
                    );

                    $currentQty = (float) $stock->on_hand_qty;
                    $currentValue = $currentQty * (float) $stock->avg_cost;
                    $incomingValue = $purchaseQty * $unitPrice;
                    $newQty = $currentQty + $purchaseQty;

                    $stock->vendor_id = $purchase->vendor_id;
                    $stock->unit_id = $purchase->unit_id;
                    $stock->avg_cost = $newQty > 0 ? (($currentValue + $incomingValue) / $newQty) : 0;
                    $stock->on_hand_qty = $newQty;
                    $stock->base_price = $inventoryBasePrice;
                    $stock->special_price = $inventorySpecialPrice;
                    $stock->save();

                    $purchase->inventory_location_id = $locationId;
                    $purchase->inventory_updated_at = $now;

                    $inventoryTypeId = InventoryType::query()
                        ->where('code', InventoryType::VENDOR_SUPPLIED)
                        ->value('id');

                    if (!$inventoryTypeId) {
                        throw new \RuntimeException('Inventory type vendor_supplied is missing. Run inventory type seeder.');
                    }

                    $transaction = InventoryTransaction::query()->create([
                        'inventory_type_id' => $inventoryTypeId,
                        'trx_type' => InventoryTransaction::TYPE_IN,
                        'reference_type' => 'purchase',
                        'reference_id' => $purchase->id,
                        'to_location_id' => $locationId,
                        'vendor_id' => $purchase->vendor_id,
                        'trx_date' => $now,
                        'notes' => 'Raw material purchase inventory update',
                        'created_by' => (int) auth()->id(),
                    ]);

                    $transaction->items()->create([
                        'product_id' => $purchase->product_id,
                        'qty' => $purchase->quantity,
                        'unit_cost' => (float) $purchase->unit_price,
                        'total_cost' => (float) $purchase->total_amount,
                    ]);

                    // Keep legacy manufacture unit stock in sync only if this flow ever targets factory.
                    // Raw material purchase flow now accepts warehouse locations only.
                }

                $purchase->save();
            });
        } catch (\RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('rawMaterialPurchase.edit', $purchase)
            ->with('success', 'Purchase updated successfully.');
    }

    private function currentWarehouseLocationId(): ?int
    {
        return InventoryLocation::query()
            ->where('is_active', true)
            ->where('type', InventoryLocation::TYPE_WAREHOUSE)
            ->value('id');
    }

    private function ensurePurchaseBelongsToWarehouse(VendorRawMaterialPurchase $purchase): void
    {
        $locationId = (int) ($purchase->inventory_location_id ?? 0);
        if ($locationId < 1) {
            abort(404);
        }

        $belongsToWarehouse = InventoryLocation::query()
            ->whereKey($locationId)
            ->where('is_active', true)
            ->where('type', InventoryLocation::TYPE_WAREHOUSE)
            ->exists();

        if (!$belongsToWarehouse) {
            abort(404);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function validateWarehouseLocationInPayload(array $validated): void
    {
        $locationId = (int) ($validated['inventory_location_id'] ?? 0);
        if ($locationId < 1) {
            return;
        }

        $isValid = InventoryLocation::query()
            ->whereKey($locationId)
            ->where('is_active', true)
            ->where('type', InventoryLocation::TYPE_WAREHOUSE)
            ->exists();

        if (!$isValid) {
            abort(404);
        }
    }

    private function resolveInventoryUnitIdForProduct(int $productId): ?int
    {
        $isFabric = Product::query()
            ->whereKey($productId)
            ->whereHas('category', function ($query) {
                $query->where('slug', 'fabrics');
            })
            ->exists();

        if (!$isFabric) {
            return null;
        }

        return Unit::query()
            ->whereIn('code', ['METER', 'meter', 'MTR', 'mtr'])
            ->orWhere('symbol', 'm')
            ->value('id');
    }
}
