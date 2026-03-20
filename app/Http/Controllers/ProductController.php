<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\AdjustInventoryRequest;
use App\Http\Requests\Product\StoreRequest;
use App\Http\Requests\Product\UpdateRequest;
use App\Models\InventoryAlert;
use App\Models\InventoryLocation;
use App\Models\InventoryReorderLevel;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\InventoryType;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $productsQuery = Product::query()
            ->with(['category:id,name,slug'])
            ->withSum('inventoryStocks as inventory_total_quantity', 'on_hand_qty');

        if ($q !== '') {
            $productsQuery->where(function ($query) use ($q): void {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('code', 'like', '%' . $q . '%')
                    ->orWhere('amount', 'like', '%' . $q . '%');
            });
        }

        $reporting = [
            'total' => (clone $productsQuery)->count(),
            'added_this_week' => (clone $productsQuery)->where('created_at', '>=', now()->startOfWeek())->count(),
            'added_this_month' => (clone $productsQuery)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'added_last_30_days' => (clone $productsQuery)->where('created_at', '>=', now()->subDays(30))->count(),
        ];

        $products = $productsQuery
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('modules.product.index', compact('products', 'reporting'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        $categories = ProductCategory::query()
            ->creatableForProducts()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $locations = $this->availableInventoryLocations();

        return view('modules.product.create', compact('categories', 'locations'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $data['code'] = trim((string) ($data['code'] ?? ''));

        DB::transaction(function () use ($data): void {
            $product = Product::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'product_category_id' => $data['product_category_id'],
                'amount' => $data['amount'],
            ]);

            $quantity = (float) ($data['opening_quantity'] ?? 0);
            $unitCost = (float) ($data['opening_unit_cost'] ?? 0);
            $locationId = (int) ($data['inventory_location_id'] ?? 0);

            if ($quantity <= 0 || $locationId < 1) {
                return;
            }

            $stock = InventoryStock::query()->firstOrCreate(
                [
                    'location_id' => $locationId,
                    'product_id' => (int) $product->id,
                    'vendor_id' => null,
                ],
                [
                    'unit_id' => null,
                    'on_hand_qty' => 0,
                    'reserved_qty' => 0,
                    'unit_cost' => 0,
                ]
            );

            $existingQty = (float) $stock->on_hand_qty;
            $newQty = $existingQty + $quantity;
            $existingValue = $existingQty * (float) $stock->unit_cost;
            $incomingValue = $quantity * $unitCost;

            $stock->unit_cost = $newQty > 0 ? (($existingValue + $incomingValue) / $newQty) : 0;
            $stock->on_hand_qty = $newQty;
            $stock->save();

            $inventoryTypeId = $this->resolveInventoryTypeIdForLocation($locationId);
            if (!$inventoryTypeId) {
                throw new \RuntimeException('Unable to resolve inventory type for selected location.');
            }

            $transaction = InventoryTransaction::query()->create([
                'inventory_type_id' => $inventoryTypeId,
                'trx_type' => InventoryTransaction::TYPE_IN,
                'reference_type' => 'product',
                'reference_id' => $product->id,
                'to_location_id' => $locationId,
                'trx_date' => now(),
                'notes' => trim((string) ($data['opening_notes'] ?? 'Opening inventory during product creation')),
                'created_by' => (int) auth()->id(),
            ]);

            $transaction->items()->create([
                'product_id' => $product->id,
                'qty' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
            ]);
        });

        return redirect()
            ->route('product.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return redirect()->route('product.edit', $product);
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        $categories = ProductCategory::query()
            ->creatableForProducts()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $locations = $this->availableInventoryLocations();
        $product->load([
            'inventoryStocks.location:id,name,type,outlet_id',
            'inventoryStocks.vendor:id,name',
            'inventoryStocks.unit:id,name,symbol',
        ]);

        $inventoryTransactions = InventoryTransaction::query()
            ->with([
                'inventoryType:id,name,code',
                'fromLocation:id,name,type',
                'toLocation:id,name,type',
                'creator:id,name',
                'items' => function ($query) use ($product) {
                    $query->where('product_id', $product->id);
                },
            ])
            ->whereHas('items', function ($query) use ($product): void {
                $query->where('product_id', $product->id);
            })
            ->latest('trx_date')
            ->latest('id')
            ->limit(10)
            ->get();

        $canManageInventory = (bool) auth()->user()?->hasPermission('manage-inventory');

        return view('modules.product.edit', compact(
            'product',
            'categories',
            'locations',
            'inventoryTransactions',
            'canManageInventory'
        ));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateRequest $request, Product $product)
    {
        $data = $request->validated();
        $data['code'] = trim((string) ($data['code'] ?? ''));
        $product->update($data);

        return redirect()
            ->route('product.index')
            ->with('success', 'Product updated successfully.');
    }

    public function adjustInventory(AdjustInventoryRequest $request, Product $product)
    {
        if (!(bool) auth()->user()?->hasPermission('manage-inventory')) {
            abort(403);
        }

        $validated = $request->validated();
        $quantity = (float) $validated['quantity'];
        $trxType = (string) $validated['trx_type'];
        $unitId = $this->resolveInventoryUnitIdForProduct((int) $product->id);
        $unitCost = (float) $validated['unit_cost'];
        $adjustmentType = (string) ($validated['adjustment_type'] ?? 'add');
        $outletId = $this->currentOutletId();

        $locationId = isset($validated['location_id']) ? (int) $validated['location_id'] : null;
        $fromLocationId = isset($validated['from_location_id']) ? (int) $validated['from_location_id'] : null;
        $toLocationId = isset($validated['to_location_id']) ? (int) $validated['to_location_id'] : null;

        $isTransfer = $trxType === InventoryTransaction::TYPE_TRANSFER;
        if (
            (!$isTransfer && !$this->isValidSingleLocationForUser($locationId, $outletId)) ||
            ($isTransfer && !$this->isValidActiveLocation($fromLocationId)) ||
            ($isTransfer && !$this->isValidActiveLocation($toLocationId))
        ) {
            return redirect()
                ->to(route('product.edit', $product) . '#inventory')
                ->withInput()
                ->with('inventory_error', 'Selected location is not valid for this transaction.');
        }

        if (in_array($trxType, [InventoryTransaction::TYPE_IN, InventoryTransaction::TYPE_OUT, InventoryTransaction::TYPE_ADJUSTMENT], true) && !$locationId) {
            return redirect()
                ->to(route('product.edit', $product) . '#inventory')
                ->withInput()
                ->with('inventory_error', 'Location is required for selected transaction type.');
        }

        if ($trxType === InventoryTransaction::TYPE_TRANSFER && (!$fromLocationId || !$toLocationId || $fromLocationId === $toLocationId)) {
            return redirect()
                ->to(route('product.edit', $product) . '#inventory')
                ->withInput()
                ->with('inventory_error', 'Valid source and destination locations are required for transfer.');
        }

        $inventoryTypeId = $this->resolveInventoryTypeId($trxType, $locationId, $fromLocationId);
        if (!$inventoryTypeId) {
            return redirect()
                ->to(route('product.edit', $product) . '#inventory')
                ->withInput()
                ->with('inventory_error', 'Unable to resolve inventory type from selected location.');
        }

        try {
            DB::transaction(function () use (
                $validated,
                $product,
                $trxType,
                $quantity,
                $locationId,
                $fromLocationId,
                $toLocationId,
                $unitId,
                $unitCost,
                $adjustmentType,
                $inventoryTypeId
            ): void {
                $transaction = InventoryTransaction::query()->create([
                    'inventory_type_id' => $inventoryTypeId,
                    'trx_type' => $trxType,
                    'from_location_id' => $trxType === InventoryTransaction::TYPE_TRANSFER
                        ? $fromLocationId
                        : ($trxType === InventoryTransaction::TYPE_OUT ? $locationId : null),
                    'to_location_id' => $trxType === InventoryTransaction::TYPE_TRANSFER
                        ? $toLocationId
                        : ($trxType === InventoryTransaction::TYPE_IN ? $locationId : null),
                    'trx_date' => now(),
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => (int) auth()->id(),
                ]);

                $transaction->items()->create([
                    'product_id' => $product->id,
                    'qty' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $unitCost * $quantity,
                ]);

                if ($trxType === InventoryTransaction::TYPE_TRANSFER) {
                    $this->applyDeltaToStock(
                        productId: (int) $product->id,
                        locationId: (int) $fromLocationId,
                        unitId: $unitId,
                        delta: -$quantity,
                        unitCost: $unitCost
                    );

                    $toStock = $this->applyDeltaToStock(
                        productId: (int) $product->id,
                        locationId: (int) $toLocationId,
                        unitId: $unitId,
                        delta: $quantity,
                        unitCost: $unitCost
                    );

                    $this->syncLowStockAlert($toStock);
                    return;
                }

                if ($trxType === InventoryTransaction::TYPE_ADJUSTMENT && $adjustmentType === 'set') {
                    $stock = $this->getOrCreateStock(
                        productId: (int) $product->id,
                        locationId: (int) $locationId,
                        unitId: $unitId
                    );

                    $delta = $quantity - (float) $stock->on_hand_qty;
                    $stock = $this->applyDeltaToStock(
                        productId: (int) $product->id,
                        locationId: (int) $locationId,
                        unitId: $unitId,
                        delta: $delta,
                        unitCost: $unitCost
                    );

                    $this->syncLowStockAlert($stock);
                    return;
                }

                $signedQty = $trxType === InventoryTransaction::TYPE_IN ? $quantity : -$quantity;
                if ($trxType === InventoryTransaction::TYPE_ADJUSTMENT && $adjustmentType === 'add') {
                    $signedQty = $quantity;
                } elseif ($trxType === InventoryTransaction::TYPE_ADJUSTMENT && $adjustmentType === 'remove') {
                    $signedQty = -$quantity;
                }

                $stock = $this->applyDeltaToStock(
                    productId: (int) $product->id,
                    locationId: (int) $locationId,
                    unitId: $unitId,
                    delta: $signedQty,
                    unitCost: $unitCost
                );

                $this->syncLowStockAlert($stock);
            });
        } catch (\RuntimeException $exception) {
            return redirect()
                ->to(route('product.edit', $product) . '#inventory')
                ->withInput()
                ->with('inventory_error', $exception->getMessage());
        }

        return redirect()
            ->to(route('product.edit', $product) . '#inventory')
            ->with('inventory_success', 'Inventory transaction recorded successfully.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()
            ->route('product.index')
            ->with('success', 'Product deleted successfully.');
    }

    private function availableInventoryLocations()
    {
        $outletId = (int) (auth()->user()?->current_outlet_id ?? 0);

        return InventoryLocation::query()
            ->where('is_active', true)
            ->where(function ($query) use ($outletId) {
                $query->whereIn('type', [
                    InventoryLocation::TYPE_WAREHOUSE,
                    InventoryLocation::TYPE_FACTORY,
                ])->orWhere(function ($outletQuery) use ($outletId) {
                    $outletQuery->where('type', InventoryLocation::TYPE_OUTLET)
                        ->where('outlet_id', $outletId);
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'type']);
    }

    private function resolveInventoryTypeIdForLocation(int $locationId): ?int
    {
        $locationType = (string) (InventoryLocation::query()
            ->whereKey($locationId)
            ->value('type') ?? '');

        $inventoryTypeCode = in_array($locationType, [InventoryLocation::TYPE_WAREHOUSE, InventoryLocation::TYPE_FACTORY], true)
            ? InventoryType::MANUFACTURING
            : InventoryType::OUTLET;

        return InventoryType::query()
            ->where('code', $inventoryTypeCode)
            ->value('id');
    }

    private function currentOutletId(): int
    {
        return (int) (auth()->user()?->current_outlet_id ?? 0);
    }

    private function isValidSingleLocationForUser(?int $locationId, int $outletId): bool
    {
        if (!$locationId) {
            return true;
        }

        return InventoryLocation::query()
            ->whereKey($locationId)
            ->where('is_active', true)
            ->where(function ($nested) use ($outletId) {
                $nested->whereIn('type', [
                    InventoryLocation::TYPE_WAREHOUSE,
                    InventoryLocation::TYPE_FACTORY,
                ])->orWhere(function ($outletQuery) use ($outletId) {
                    $outletQuery->where('type', InventoryLocation::TYPE_OUTLET)
                        ->where('outlet_id', $outletId);
                });
            })
            ->exists();
    }

    private function isValidActiveLocation(?int $locationId): bool
    {
        if (!$locationId) {
            return false;
        }

        return InventoryLocation::query()
            ->whereKey($locationId)
            ->where('is_active', true)
            ->exists();
    }

    private function resolveInventoryTypeId(string $trxType, ?int $locationId, ?int $fromLocationId): ?int
    {
        $typeCode = InventoryType::OUTLET;
        $sourceLocationId = $trxType === InventoryTransaction::TYPE_TRANSFER
            ? (int) ($fromLocationId ?? 0)
            : (int) ($locationId ?? 0);

        if ($sourceLocationId > 0) {
            $locationType = (string) (InventoryLocation::query()
                ->whereKey($sourceLocationId)
                ->value('type') ?? '');

            if (in_array($locationType, [InventoryLocation::TYPE_WAREHOUSE, InventoryLocation::TYPE_FACTORY], true)) {
                $typeCode = InventoryType::MANUFACTURING;
            }
        }

        return InventoryType::query()
            ->where('code', $typeCode)
            ->value('id');
    }

    private function getOrCreateStock(int $productId, int $locationId, ?int $unitId): InventoryStock
    {
        $stock = InventoryStock::query()->firstOrCreate(
            [
                'product_id' => $productId,
                'location_id' => $locationId,
                'vendor_id' => null,
            ],
            [
                'unit_id' => $unitId,
                'on_hand_qty' => 0,
                'reserved_qty' => 0,
                'unit_cost' => 0,
            ]
        );

        $stock->unit_id = $unitId;

        return $stock;
    }

    private function applyDeltaToStock(
        int $productId,
        int $locationId,
        ?int $unitId,
        float $delta,
        float $unitCost
    ): InventoryStock {
        $stock = $this->getOrCreateStock($productId, $locationId, $unitId);

        $currentQty = (float) $stock->on_hand_qty;
        $newQty = $currentQty + $delta;

        if ($newQty < 0) {
            throw new \RuntimeException('Insufficient stock for this transaction.');
        }

        if ($delta > 0) {
            $currentValue = $currentQty * (float) $stock->unit_cost;
            $incomingValue = $delta * $unitCost;
            $stock->unit_cost = $newQty > 0 ? (($currentValue + $incomingValue) / $newQty) : 0;
        }

        $stock->on_hand_qty = $newQty;
        $stock->save();

        return $stock;
    }

    private function syncLowStockAlert(InventoryStock $stock): void
    {
        $reorder = InventoryReorderLevel::query()
            ->where('product_id', $stock->product_id)
            ->where('location_id', $stock->location_id)
            ->where('is_active', true)
            ->first();

        if (!$reorder) {
            return;
        }

        $openAlert = InventoryAlert::query()
            ->where('product_id', $stock->product_id)
            ->where('location_id', $stock->location_id)
            ->where('alert_type', InventoryAlert::TYPE_LOW_STOCK)
            ->where('status', InventoryAlert::STATUS_OPEN)
            ->latest('id')
            ->first();

        if ((float) $stock->on_hand_qty <= (float) $reorder->min_qty) {
            if ($openAlert) {
                $openAlert->update([
                    'current_qty' => $stock->on_hand_qty,
                    'min_qty' => $reorder->min_qty,
                ]);

                return;
            }

            InventoryAlert::query()->create([
                'product_id' => $stock->product_id,
                'location_id' => $stock->location_id,
                'alert_type' => InventoryAlert::TYPE_LOW_STOCK,
                'current_qty' => $stock->on_hand_qty,
                'min_qty' => $reorder->min_qty,
                'status' => InventoryAlert::STATUS_OPEN,
            ]);

            return;
        }

        if ($openAlert) {
            $openAlert->update([
                'status' => InventoryAlert::STATUS_CLOSED,
                'closed_at' => now(),
                'note' => 'Auto-closed: stock is above minimum level.',
            ]);
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
