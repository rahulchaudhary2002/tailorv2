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
use App\Services\NotificationService;
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
        $categoryId = (int) $request->query('category_id', 0);

        $productsQuery = $this->filteredProductsQuery($request)
            ->with(['category:id,name,slug'])
            ->withSum('inventoryStocks as inventory_total_quantity', 'on_hand_qty');

        $products = $productsQuery
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $categories = ProductCategory::query()
            ->creatableForProducts()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('modules.product.index', compact('products', 'categories'));
    }

    public function barcodesPdf(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $categoryId = (int) $request->query('category_id', 0);

        $products = $this->filteredProductsQuery($request)
            ->with(['category:id,name,slug'])
            ->latest()
            ->get();

        return view('modules.product.barcodes_pdf', compact('products', 'q', 'categoryId'));
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
        $savedProduct = null;

        DB::transaction(function () use ($data, &$savedProduct): void {
            $product = Product::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'product_category_id' => $data['product_category_id'],
                'amount' => $data['amount'],
            ]);
            $product->ensureBarcode();
            $savedProduct = $product;

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
            if (! $inventoryTypeId) {
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

        if ($savedProduct) {
            $this->notifyProductRecipients(
                'Product created',
                'Product '.$savedProduct->name.' was created.',
                route('product.index', ['q' => $savedProduct->code ?: $savedProduct->name])
            );
        }

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
        $product->ensureBarcode();

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

        $this->notifyProductRecipients(
            'Product updated',
            'Product '.$product->name.' was updated.',
            route('product.index', ['q' => $product->code ?: $product->name])
        );

        return redirect()
            ->route('product.index')
            ->with('success', 'Product updated successfully.');
    }

    public function adjustInventory(AdjustInventoryRequest $request, Product $product)
    {
        if (! (bool) auth()->user()?->hasPermission('manage-inventory')) {
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
            (! $isTransfer && ! $this->isValidSingleLocationForUser($locationId, $outletId)) ||
            ($isTransfer && ! $this->isValidActiveLocation($fromLocationId)) ||
            ($isTransfer && ! $this->isValidActiveLocation($toLocationId))
        ) {
            return redirect()
                ->to(route('product.edit', $product).'#inventory')
                ->withInput()
                ->with('inventory_error', 'Selected location is not valid for this transaction.');
        }

        if (in_array($trxType, [InventoryTransaction::TYPE_IN, InventoryTransaction::TYPE_OUT, InventoryTransaction::TYPE_ADJUSTMENT], true) && ! $locationId) {
            return redirect()
                ->to(route('product.edit', $product).'#inventory')
                ->withInput()
                ->with('inventory_error', 'Location is required for selected transaction type.');
        }

        if ($trxType === InventoryTransaction::TYPE_TRANSFER && (! $fromLocationId || ! $toLocationId || $fromLocationId === $toLocationId)) {
            return redirect()
                ->to(route('product.edit', $product).'#inventory')
                ->withInput()
                ->with('inventory_error', 'Valid source and destination locations are required for transfer.');
        }

        $inventoryTypeId = $this->resolveInventoryTypeId($trxType, $locationId, $fromLocationId);
        if (! $inventoryTypeId) {
            return redirect()
                ->to(route('product.edit', $product).'#inventory')
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
                    $updatedTargetStocks = $this->transferStockBetweenLocations(
                        productId: (int) $product->id,
                        fromLocationId: (int) $fromLocationId,
                        toLocationId: (int) $toLocationId,
                        unitId: $unitId,
                        quantity: $quantity,
                        fallbackUnitCost: $unitCost
                    );

                    foreach ($updatedTargetStocks as $targetStock) {
                        $this->syncLowStockAlert($targetStock);
                    }

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
                ->to(route('product.edit', $product).'#inventory')
                ->withInput()
                ->with('inventory_error', $exception->getMessage());
        }

        $this->notifyInventoryRecipients(
            $product,
            $trxType,
            $quantity,
            $outletId,
            route('product.edit', $product).'#inventory'
        );

        return redirect()
            ->to(route('product.edit', $product).'#inventory')
            ->with('inventory_success', 'Inventory transaction recorded successfully.');
    }

    private function notifyInventoryRecipients(Product $product, string $trxType, float $quantity, int $outletId, string $url): void
    {
        app(NotificationService::class)->notifyPermission(
            'receive-inventory-notifications',
            $outletId,
            [
                'title' => 'Inventory updated',
                'message' => ucfirst($trxType).' transaction of '.number_format($quantity, 2).' '.$product->defaultUnitLabel().' recorded for '.$product->name.'.',
                'url' => $url,
                'module' => 'Inventory',
            ],
            array_filter([(int) auth()->id()])
        );
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        $productName = $product->name;
        $product->delete();

        $this->notifyProductRecipients(
            'Product deleted',
            'Product '.$productName.' was deleted.',
            route('product.index')
        );

        return redirect()
            ->route('product.index')
            ->with('success', 'Product deleted successfully.');
    }

    private function notifyProductRecipients(string $title, string $message, string $url): void
    {
        $actorName = (string) (auth()->user()?->name ?: 'System');

        app(NotificationService::class)->notifyPermission(
            'receive-product-notifications',
            (int) (auth()->user()?->current_outlet_id ?? 0),
            [
                'title' => $title,
                'message' => $actorName.': '.$message,
                'url' => $url,
                'module' => 'Product',
            ],
            array_filter([(int) auth()->id()])
        );
    }

    private function filteredProductsQuery(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $qLower = mb_strtolower($q);
        $categoryId = (int) $request->query('category_id', 0);

        $productsQuery = Product::query();

        if ($q !== '') {
            $barcodeQuery = preg_replace('/\D+/', '', $q) ?? '';

            $productsQuery->where(function ($query) use ($q, $qLower, $barcodeQuery): void {
                $query->whereRaw('LOWER(name) LIKE ?', ['%'.$qLower.'%'])
                    ->orWhereRaw('LOWER(code) LIKE ?', ['%'.$qLower.'%'])
                    ->orWhereRaw('CAST(amount AS CHAR) LIKE ?', ['%'.$q.'%']);

                if ($barcodeQuery !== '') {
                    $query->orWhere('barcode', 'like', '%'.$barcodeQuery.'%');
                }
            });
        }

        if ($categoryId > 0) {
            $productsQuery->where('product_category_id', $categoryId);
        }

        return $productsQuery;
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
        if (! $locationId) {
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
        if (! $locationId) {
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
        if ($delta < 0) {
            return $this->consumeStockFifo(
                productId: $productId,
                locationId: $locationId,
                quantity: abs($delta)
            );
        }

        $stock = $this->getOrCreateStock($productId, $locationId, $unitId);

        $currentQty = (float) $stock->on_hand_qty;
        $newQty = $currentQty + $delta;

        if ($delta > 0) {
            $currentValue = $currentQty * (float) $stock->unit_cost;
            $incomingValue = $delta * $unitCost;
            $stock->unit_cost = $newQty > 0 ? (($currentValue + $incomingValue) / $newQty) : 0;
        }

        $stock->on_hand_qty = $newQty;
        $stock->save();

        return $stock;
    }

    private function consumeStockFifo(
        int $productId,
        int $locationId,
        float $quantity
    ): InventoryStock {
        $sourceStocks = InventoryStock::query()
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $availableQty = (float) $sourceStocks->sum(function (InventoryStock $stock) {
            return max(0, (float) $stock->on_hand_qty - (float) $stock->reserved_qty);
        });

        if ($availableQty + 0.000001 < $quantity) {
            throw new \RuntimeException('Insufficient stock for this transaction.');
        }

        $remainingQty = $quantity;
        $lastTouchedStock = null;

        foreach ($sourceStocks as $sourceStock) {
            if ($remainingQty <= 0) {
                break;
            }

            $movableQty = max(0, (float) $sourceStock->on_hand_qty - (float) $sourceStock->reserved_qty);
            if ($movableQty <= 0) {
                continue;
            }

            $deductQty = min($remainingQty, $movableQty);
            $sourceStock->on_hand_qty = (float) $sourceStock->on_hand_qty - $deductQty;
            $sourceStock->save();

            $lastTouchedStock = $sourceStock;
            $remainingQty -= $deductQty;
        }

        return $lastTouchedStock ?: $this->getOrCreateStock($productId, $locationId, null);
    }

    /**
     * @return array<int, InventoryStock>
     */
    private function transferStockBetweenLocations(
        int $productId,
        int $fromLocationId,
        int $toLocationId,
        ?int $unitId,
        float $quantity,
        float $fallbackUnitCost
    ): array {
        if ($quantity <= 0) {
            return [];
        }

        $sourceStocks = InventoryStock::query()
            ->where('location_id', $fromLocationId)
            ->where('product_id', $productId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $availableQty = (float) $sourceStocks->sum(function (InventoryStock $stock) {
            return max(0, (float) $stock->on_hand_qty - (float) $stock->reserved_qty);
        });

        if ($availableQty + 0.000001 < $quantity) {
            throw new \RuntimeException('Insufficient stock for this transfer.');
        }

        $remainingQty = $quantity;
        $updatedTargets = [];

        foreach ($sourceStocks as $sourceStock) {
            if ($remainingQty <= 0) {
                break;
            }

            $movableQty = max(0, (float) $sourceStock->on_hand_qty - (float) $sourceStock->reserved_qty);
            if ($movableQty <= 0) {
                continue;
            }

            $transferQty = min($remainingQty, $movableQty);
            $sourceStock->on_hand_qty = (float) $sourceStock->on_hand_qty - $transferQty;
            $sourceStock->save();

            $transferUnitCost = (float) $sourceStock->unit_cost;
            if ($transferUnitCost <= 0) {
                $transferUnitCost = $fallbackUnitCost;
            }

            $targetVendorId = (int) ($sourceStock->vendor_id ?? 0);
            $targetStock = InventoryStock::query()->firstOrCreate(
                [
                    'product_id' => $productId,
                    'location_id' => $toLocationId,
                    'vendor_id' => $targetVendorId > 0 ? $targetVendorId : null,
                ],
                [
                    'unit_id' => $unitId,
                    'on_hand_qty' => 0,
                    'reserved_qty' => 0,
                    'unit_cost' => 0,
                ]
            );

            $targetStock->unit_id = $unitId;
            $currentQty = (float) $targetStock->on_hand_qty;
            $newQty = $currentQty + $transferQty;
            $currentValue = $currentQty * (float) $targetStock->unit_cost;
            $incomingValue = $transferQty * $transferUnitCost;

            $targetStock->unit_cost = $newQty > 0 ? (($currentValue + $incomingValue) / $newQty) : 0;
            $targetStock->on_hand_qty = $newQty;
            $targetStock->save();

            $updatedTargets[(string) $targetStock->id] = $targetStock;
            $remainingQty -= $transferQty;
        }

        return array_values($updatedTargets);
    }

    private function syncLowStockAlert(InventoryStock $stock): void
    {
        $reorder = InventoryReorderLevel::query()
            ->where('product_id', $stock->product_id)
            ->where('location_id', $stock->location_id)
            ->where('is_active', true)
            ->first();

        if (! $reorder) {
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
        return Product::query()
            ->with('category:id,slug')
            ->find($productId)
            ?->resolveDefaultUnitId();
    }
}
