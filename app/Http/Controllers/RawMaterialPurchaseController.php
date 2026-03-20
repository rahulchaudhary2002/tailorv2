<?php

namespace App\Http\Controllers;

use App\Http\Requests\RawMaterialPurchase\StoreRequest;
use App\Http\Requests\RawMaterialPurchase\UpdateProcurementRequest;
use App\Models\InventoryLocation;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\InventoryType;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\Vendor;
use App\Models\VendorRawMaterialPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
                });
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
            ->with('category:id,name,slug')
            ->whereHas('category', function ($query) {
                $query->whereIn('slug', ['fabrics', 'accessories', 'ready-made']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'amount', 'product_category_id']);

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
        try {
            DB::transaction(function () use ($validated, $items, $warehouseLocationId): void {
                $now = now();
                $preparedItems = $items->map(fn ($item) => $this->preparePurchaseItem((array) $item));

                foreach ($preparedItems as $item) {
                    $productId = (int) $item['product_id'];
                    $quantity = (int) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];
                    $unitId = $this->resolveInventoryUnitIdForProduct($productId);

                    $purchase = VendorRawMaterialPurchase::query()->create([
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

                    $this->applyInventoryUpdate(
                        $purchase,
                        (int) $warehouseLocationId,
                        $unitPrice,
                        (string) ($validated['notes'] ?? 'Raw material purchase inventory update'),
                        $now
                    );
                }
            });
        } catch (\RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('rawMaterialPurchase.index')
            ->with('success', 'Purchase created and inventory updated successfully.');
    }

    /**
     * Show procurement process form for a purchase.
     */
    public function edit(VendorRawMaterialPurchase $purchase)
    {
        $this->ensurePurchaseBelongsToWarehouse($purchase);

        $purchase->load(['vendor:id,name', 'product:id,name,code,amount,product_category_id', 'product.category:id,slug', 'unit:id,name,symbol', 'inventoryLocation:id,name,type']);

        $vendors = Vendor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::query()
            ->with('category:id,name,slug')
            ->whereHas('category', function ($query) {
                $query->whereIn('slug', ['fabrics', 'accessories', 'ready-made']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'amount', 'product_category_id']);

        return view('modules.raw_material_purchase.edit', compact('purchase', 'vendors', 'products'));
    }

    /**
     * Update purchase notes/details.
     */
    public function updateProcurement(UpdateProcurementRequest $request, VendorRawMaterialPurchase $purchase)
    {
        $this->ensurePurchaseBelongsToWarehouse($purchase);

        $validated = $request->validated();

        try {
            DB::transaction(function () use ($purchase, $validated): void {
                $item = $this->preparePurchaseItem((array) collect($validated['items'] ?? [])->first());
                $productId = (int) ($item['product_id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $warehouseLocationId = $this->resolveWarehouseLocationIdForPurchase($purchase);

                if (!$warehouseLocationId) {
                    throw new \RuntimeException('No active warehouse location found.');
                }

                $previousState = [
                    'location_id' => (int) ($purchase->inventory_location_id ?? 0),
                    'vendor_id' => (int) $purchase->vendor_id,
                    'product_id' => (int) $purchase->product_id,
                    'unit_id' => (int) ($purchase->unit_id ?? 0),
                    'quantity' => (float) $purchase->quantity,
                    'unit_price' => (float) $purchase->unit_price,
                    'total_amount' => (float) $purchase->total_amount,
                ];

                $purchase->vendor_id = (int) $validated['vendor_id'];
                $purchase->product_id = $productId;
                $purchase->unit_id = $this->resolveInventoryUnitIdForProduct($productId);
                $purchase->quantity = $quantity;
                $purchase->unit_price = $unitPrice;
                $purchase->total_amount = (float) $purchase->quantity * (float) $purchase->unit_price;
                $purchase->purchased_at = $validated['purchased_at'];
                $purchase->notes = $validated['notes'] ?? null;
                $purchase->inventory_location_id = (int) $warehouseLocationId;
                $purchase->inventory_updated_at = null;
                $purchase->save();

                $this->syncInventoryForUpdatedPurchase(
                    $purchase,
                    $previousState,
                    (int) $warehouseLocationId,
                    $unitPrice,
                    (string) ($validated['notes'] ?? 'Raw material purchase inventory update'),
                    now()
                );
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

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, int|float|string>
     */
    private function preparePurchaseItem(array $item): array
    {
        $reference = trim((string) ($item['product_reference'] ?? ''));
        $productType = trim((string) ($item['product_type'] ?? ''));
        $productCode = trim((string) ($item['product_code'] ?? ''));
        $quantity = max(1, (int) ($item['quantity'] ?? 0));
        $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));

        if (str_starts_with($reference, 'existing:')) {
            $productId = (int) substr($reference, strlen('existing:'));
            $product = Product::query()
                ->with('category:id,slug')
                ->find($productId);

            if (!$product || !in_array((string) $product->category?->slug, ['fabrics', 'accessories', 'ready-made'], true)) {
                throw new \RuntimeException('One or more selected products is invalid.');
            }

            return [
                'product_id' => (int) $product->id,
                'product_type' => (string) $product->category?->slug,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
            ];
        }

        $productName = trim(Str::after($reference, 'new:'));
        if ($productName === '') {
            throw new \RuntimeException('Enter a vendor product name.');
        }

        $product = $this->findOrCreatePurchaseProduct($productName, $productType, $unitPrice, $productCode);

        return [
            'product_id' => (int) $product->id,
            'product_type' => $productType,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
        ];
    }

    private function findOrCreatePurchaseProduct(string $productName, string $productType, float $unitPrice, string $productCode = ''): Product
    {
        $categoryId = (int) ProductCategory::query()
            ->where('slug', $productType)
            ->value('id');

        if ($categoryId < 1) {
            throw new \RuntimeException('Select a valid product type.');
        }

        $existingProduct = Product::query()
            ->where('product_category_id', $categoryId)
            ->whereRaw('LOWER(name) = ?', [Str::lower($productName)])
            ->first();

        if ($existingProduct) {
            return $existingProduct;
        }

        $resolvedCode = $productCode !== '' ? Str::upper($productCode) : $this->generateProductCode($productType);

        return Product::query()->create([
            'product_category_id' => $categoryId,
            'name' => $productName,
            'code' => $resolvedCode,
            'amount' => $unitPrice,
        ]);
    }

    private function generateProductCode(string $productType): string
    {
        $prefix = match ($productType) {
            'accessories' => 'ACC',
            'ready-made' => 'RM',
            default => 'FAB',
        };

        $maxSequence = 0;

        Product::query()
            ->where('code', 'like', $prefix . '-%')
            ->pluck('code')
            ->each(function ($code) use (&$maxSequence, $prefix): void {
                if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', (string) $code, $matches) === 1) {
                    $maxSequence = max($maxSequence, (int) $matches[1]);
                }
            });

        do {
            $maxSequence++;
            $nextCode = sprintf('%s-%04d', $prefix, $maxSequence);
        } while (Product::query()->where('code', $nextCode)->exists());

        return $nextCode;
    }

    private function currentWarehouseLocationId(): ?int
    {
        return InventoryLocation::query()
            ->where('is_active', true)
            ->where('type', InventoryLocation::TYPE_WAREHOUSE)
            ->value('id');
    }

    private function resolveWarehouseLocationIdForPurchase(VendorRawMaterialPurchase $purchase): ?int
    {
        $existingLocationId = (int) ($purchase->inventory_location_id ?? 0);
        if ($existingLocationId > 0) {
            $isWarehouse = InventoryLocation::query()
                ->whereKey($existingLocationId)
                ->where('is_active', true)
                ->where('type', InventoryLocation::TYPE_WAREHOUSE)
                ->exists();

            if ($isWarehouse) {
                return $existingLocationId;
            }
        }

        return $this->currentWarehouseLocationId();
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

    private function resolveInventoryUnitIdForProduct(int $productId): ?int
    {
        return Unit::query()
            ->whereIn('code', ['METER', 'meter', 'MTR', 'mtr'])
            ->orWhere('symbol', 'm')
            ->value('id');
    }

    private function applyInventoryUpdate(
        VendorRawMaterialPurchase $purchase,
        int $locationId,
        float $inventoryUnitCost,
        string $notes,
        $now
    ): void {
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
                'unit_cost' => $inventoryUnitCost,
            ]
        );

        $purchaseQty = (float) $purchase->quantity;
        $currentQty = (float) $stock->on_hand_qty;
        $currentValue = $currentQty * (float) $stock->unit_cost;
        $incomingValue = $purchaseQty * $inventoryUnitCost;
        $newQty = $currentQty + $purchaseQty;

        $stock->vendor_id = $purchase->vendor_id;
        $stock->unit_id = $purchase->unit_id;
        $stock->unit_cost = $newQty > 0 ? (($currentValue + $incomingValue) / $newQty) : 0;
        $stock->on_hand_qty = $newQty;
        $stock->save();

        $purchase->inventory_location_id = $locationId;
        $purchase->inventory_updated_at = $now;
        $purchase->save();

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
            'notes' => $notes,
            'created_by' => (int) auth()->id(),
        ]);

        $transaction->items()->create([
            'product_id' => $purchase->product_id,
            'qty' => $purchase->quantity,
            'unit_cost' => $inventoryUnitCost,
            'total_cost' => (float) $purchase->total_amount,
        ]);
    }

    private function revertInventoryUpdate(VendorRawMaterialPurchase $purchase): void
    {
        $transactions = InventoryTransaction::query()
            ->with('items')
            ->where('reference_type', 'purchase')
            ->where('reference_id', $purchase->id)
            ->get();

        foreach ($transactions as $transaction) {
            $locationId = (int) ($transaction->to_location_id ?? 0);
            if ($locationId < 1) {
                continue;
            }

            foreach ($transaction->items as $item) {
                $stock = InventoryStock::query()
                    ->where('location_id', $locationId)
                    ->where('product_id', (int) $item->product_id)
                    ->where('vendor_id', (int) $transaction->vendor_id)
                    ->first();

                if (!$stock) {
                    continue;
                }

                $currentQty = (float) $stock->on_hand_qty;
                $reverseQty = (float) $item->qty;
                $newQty = max(0, $currentQty - $reverseQty);
                $currentValue = $currentQty * (float) $stock->unit_cost;
                $reverseValue = (float) $item->total_cost;

                $stock->on_hand_qty = $newQty;
                $stock->unit_cost = $newQty > 0
                    ? max(0, ($currentValue - $reverseValue) / $newQty)
                    : 0;
                $stock->save();
            }

            $transaction->items()->delete();
            $transaction->delete();
        }
    }

    /**
     * @param  array<string, int|float>  $previousState
     */
    private function syncInventoryForUpdatedPurchase(
        VendorRawMaterialPurchase $purchase,
        array $previousState,
        int $locationId,
        float $inventoryUnitCost,
        string $notes,
        $now
    ): void {
        $transaction = InventoryTransaction::query()
            ->with('items')
            ->where('reference_type', 'purchase')
            ->where('reference_id', $purchase->id)
            ->latest('id')
            ->first();

        if (!$transaction || $transaction->items->isEmpty()) {
            $this->applyInventoryUpdate($purchase, $locationId, $inventoryUnitCost, $notes, $now);
            return;
        }

        $transactionItem = $transaction->items->first();
        $previousLocationId = (int) ($transaction->to_location_id ?: ($previousState['location_id'] ?? 0));
        $previousVendorId = (int) ($transaction->vendor_id ?: ($previousState['vendor_id'] ?? 0));
        $previousProductId = (int) ($transactionItem->product_id ?: ($previousState['product_id'] ?? 0));
        $previousUnitId = (int) ($previousState['unit_id'] ?? 0);
        $previousQty = (float) ($transactionItem->qty ?: ($previousState['quantity'] ?? 0));
        $previousTotalCost = (float) ($transactionItem->total_cost ?: ($previousState['total_amount'] ?? 0));

        $this->decreasePurchaseStock(
            $previousLocationId,
            $previousVendorId,
            $previousProductId,
            $previousQty,
            $previousTotalCost
        );

        $targetStock = InventoryStock::query()->firstOrCreate(
            [
                'location_id' => $locationId,
                'product_id' => $purchase->product_id,
                'vendor_id' => $purchase->vendor_id,
            ],
            [
                'unit_id' => $purchase->unit_id,
                'on_hand_qty' => 0,
                'reserved_qty' => 0,
                'unit_cost' => $inventoryUnitCost,
            ]
        );

        $currentQty = (float) $targetStock->on_hand_qty;
        $incomingValue = (float) $purchase->total_amount;
        $currentValue = $currentQty * (float) $targetStock->unit_cost;
        $newQty = $currentQty + (float) $purchase->quantity;

        $targetStock->vendor_id = $purchase->vendor_id;
        $targetStock->unit_id = $purchase->unit_id ?: $previousUnitId;
        $targetStock->unit_cost = $newQty > 0 ? (($currentValue + $incomingValue) / $newQty) : 0;
        $targetStock->on_hand_qty = $newQty;
        $targetStock->save();

        $purchase->inventory_location_id = $locationId;
        $purchase->inventory_updated_at = $now;
        $purchase->save();

        $transaction->to_location_id = $locationId;
        $transaction->vendor_id = $purchase->vendor_id;
        $transaction->trx_date = $now;
        $transaction->notes = $notes;
        $transaction->save();

        $transactionItem->product_id = $purchase->product_id;
        $transactionItem->qty = $purchase->quantity;
        $transactionItem->unit_cost = $inventoryUnitCost;
        $transactionItem->total_cost = (float) $purchase->total_amount;
        $transactionItem->save();
    }

    private function decreasePurchaseStock(
        int $locationId,
        int $vendorId,
        int $productId,
        float $quantity,
        float $totalCost
    ): void {
        if ($locationId < 1 || $vendorId < 1 || $productId < 1 || $quantity <= 0) {
            return;
        }

        $stock = InventoryStock::query()
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->where('vendor_id', $vendorId)
            ->first();

        if (!$stock) {
            return;
        }

        $currentQty = (float) $stock->on_hand_qty;
        $newQty = max(0, $currentQty - $quantity);
        $currentValue = $currentQty * (float) $stock->unit_cost;

        $stock->on_hand_qty = $newQty;
        $stock->unit_cost = $newQty > 0 ? max(0, ($currentValue - $totalCost) / $newQty) : 0;
        $stock->save();
    }
}
