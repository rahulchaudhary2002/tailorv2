<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManufactureUnit\StoreTransferRequest;
use App\Http\Requests\ManufactureUnit\TransferFinalGoodsRequest;
use App\Http\Requests\ManufactureUnit\StoreWorkflowRequest;
use App\Http\Requests\ManufactureUnit\TransferProductionOutputRequest;
use App\Http\Requests\ManufactureUnit\UpdateTransferStatusRequest;
use App\Models\InventoryLocation;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\InventoryType;
use App\Models\ManufactureUnitStock;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManufactureUnitController extends Controller
{
    /**
     * Display manufacturing stock records.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $qLower = mb_strtolower($q);
        $productFilterId = (int) $request->query('product_id', 0);
        $locationFilterId = (int) $request->query('location_id', 0);

        $stocksQuery = InventoryStock::query()
            ->whereHas('location', function ($query) {
                $query->whereIn('type', ['warehouse', 'factory']);
            })
            ->with(['product:id,name,code,product_category_id', 'product.category:id,slug', 'location:id,name,type', 'unit:id,name,symbol']);

        if ($q !== '') {
            $stocksQuery->where(function ($query) use ($qLower): void {
                $query->whereHas('product', function ($productQuery) use ($qLower): void {
                    $productQuery->whereRaw('LOWER(name) LIKE ?', ['%' . $qLower . '%'])
                        ->orWhereRaw('LOWER(code) LIKE ?', ['%' . $qLower . '%']);
                })->orWhereHas('location', function ($locationQuery) use ($qLower): void {
                    $locationQuery->whereRaw('LOWER(name) LIKE ?', ['%' . $qLower . '%']);
                });
            });
        }

        if ($productFilterId > 0) {
            $stocksQuery->where('product_id', $productFilterId);
        }

        if ($locationFilterId > 0) {
            $stocksQuery->where('location_id', $locationFilterId);
        }

        $stocks = $stocksQuery
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $finalGoodsTransferLocations = InventoryLocation::query()
            ->where('is_active', true)
            ->whereIn('type', [InventoryLocation::TYPE_WAREHOUSE, InventoryLocation::TYPE_OUTLET])
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $stockFilterLocations = InventoryLocation::query()
            ->where('is_active', true)
            ->whereIn('type', [InventoryLocation::TYPE_WAREHOUSE, InventoryLocation::TYPE_FACTORY])
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $stats = [
            'materials_count' => InventoryStock::query()
                ->whereHas('location', function ($query) {
                    $query->whereIn('type', ['warehouse', 'factory']);
                })
                ->count(),
            'total_quantity' => (float) InventoryStock::query()
                ->whereHas('location', function ($query) {
                    $query->whereIn('type', ['warehouse', 'factory']);
                })
                ->sum('on_hand_qty'),
        ];

        $productionProducts = Product::query()
            ->with(['category:id,slug'])
            ->whereHas('category', function ($query) {
                $query->whereIn('slug', ['ready-made', 'accessories']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'barcode', 'product_category_id']);

        $productionLogs = InventoryTransaction::query()
            ->whereHas('inventoryType', function ($query) {
                $query->where('code', InventoryType::MANUFACTURING);
            })
            ->where('reference_type', 'production_output')
            ->with([
                'toLocation:id,name,type',
                'creator:id,name',
                'items.product:id,name,code',
            ])
            ->latest('trx_date')
            ->latest()
            ->limit(20)
            ->get();

        $productionOutputIds = $productionLogs->pluck('id')->all();
        $transferredByOutputId = collect();

        if (!empty($productionOutputIds)) {
            $transferredByOutputId = DB::table('inventory_transactions as trx')
                ->join('inventory_transaction_items as item', 'item.inventory_transaction_id', '=', 'trx.id')
                ->where('trx.reference_type', 'production_output_distribution')
                ->whereIn('trx.reference_id', $productionOutputIds)
                ->groupBy('trx.reference_id')
                ->selectRaw('trx.reference_id, COALESCE(SUM(item.qty), 0) as transferred_qty')
                ->pluck('transferred_qty', 'trx.reference_id');
        }

        foreach ($productionLogs as $productionLog) {
            $logItem = $productionLog->items->first();
            $producedQty = (float) ($logItem?->qty ?? 0);
            $transferredQty = (float) ($transferredByOutputId->get($productionLog->id, 0));
            $remainingQty = max(0, $producedQty - $transferredQty);

            $productionLog->setAttribute('produced_qty', $producedQty);
            $productionLog->setAttribute('transferred_qty', $transferredQty);
            $productionLog->setAttribute('remaining_qty', $remainingQty);
        }

        $productionTransfers = InventoryTransaction::query()
            ->whereHas('inventoryType', function ($query) {
                $query->where('code', InventoryType::MANUFACTURING);
            })
            ->where('reference_type', 'production_transfer')
            ->with([
                'fromLocation:id,name,type',
                'creator:id,name',
                'items.product:id,name,code',
                'targetProduct:id,name,code',
            ])
            ->latest('trx_date')
            ->latest()
            ->limit(20)
            ->get();

        $availableProductionTransfers = InventoryTransaction::query()
            ->whereHas('inventoryType', function ($query) {
                $query->where('code', InventoryType::MANUFACTURING);
            })
            ->where('reference_type', 'production_transfer')
            ->where('status', InventoryTransaction::STATUS_PROGRESS)
            ->whereNotNull('target_product_id')
            ->with(['targetProduct:id,name,code'])
            ->latest('trx_date')
            ->latest()
            ->limit(100)
            ->get(['id', 'trx_date', 'status', 'target_product_id']);

        return view('modules.manufacture_unit.index', compact('stocks', 'stats', 'productionProducts', 'productionLogs', 'productionTransfers', 'availableProductionTransfers', 'finalGoodsTransferLocations', 'stockFilterLocations'));
    }

    /**
     * Transfer stock for production by deducting source location quantity.
     */
    public function transferForProduction(StoreTransferRequest $request, InventoryStock $stock)
    {
        $tab = (string) $request->input('tab', '');
        $inventoryTypeId = InventoryType::query()
            ->where('code', InventoryType::MANUFACTURING)
            ->value('id');

        if (!$inventoryTypeId) {
            return $this->redirectToIndex($tab)
                ->with('error', 'Inventory type manufacturing is missing. Run inventory type seeder.');
        }

        $validated = $request->validated();
        $quantity = (float) $validated['quantity'];
        $targetProduct = Product::query()
            ->with('category:id,slug')
            ->findOrFail((int) $validated['target_product_id']);

        if (!in_array($targetProduct->category?->slug, ['ready-made', 'accessories'], true)) {
            return $this->redirectToIndex($tab)
                ->with('error', 'Transfer for production is allowed only to ready-made or accessories goods.');
        }

        try {
            DB::transaction(function () use ($stock, $validated, $quantity, $inventoryTypeId, $targetProduct): void {
                $lockedStock = InventoryStock::query()
                    ->with('location:id,type')
                    ->lockForUpdate()
                    ->findOrFail($stock->id);

                if (!$lockedStock->location || !in_array($lockedStock->location->type, [InventoryLocation::TYPE_WAREHOUSE, InventoryLocation::TYPE_FACTORY], true)) {
                    throw new \RuntimeException('Transfer is allowed only from warehouse or factory locations.');
                }

                $availableQty = (float) $lockedStock->on_hand_qty;
                if ($availableQty < $quantity) {
                    throw new \RuntimeException('Insufficient stock in selected location for this transfer.');
                }

                $lockedStock->on_hand_qty = $availableQty - $quantity;
                $lockedStock->save();

                $targetLabel = $targetProduct->name . ' (' . $targetProduct->code . ')';

                $notes = trim((string) ($validated['notes'] ?? ''));
                if ($notes !== '') {
                    $notes .= ' | ';
                }
                $notes .= 'Target finished good: ' . $targetLabel;

                $transaction = InventoryTransaction::query()->create([
                    'inventory_type_id' => $inventoryTypeId,
                    'trx_type' => InventoryTransaction::TYPE_OUT,
                    'status' => InventoryTransaction::STATUS_PENDING,
                    'reference_type' => 'production_transfer',
                    'reference_id' => $lockedStock->id,
                    'target_product_id' => $targetProduct->id,
                    'from_location_id' => $lockedStock->location_id,
                    'to_location_id' => null,
                    'vendor_id' => $lockedStock->vendor_id,
                    'trx_date' => now(),
                    'notes' => $notes,
                    'created_by' => (int) auth()->id(),
                ]);

                $unitCost = (float) $lockedStock->unit_cost;
                $transaction->items()->create([
                    'product_id' => $lockedStock->product_id,
                    'qty' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $quantity * $unitCost,
                ]);

                if ($lockedStock->location->type === InventoryLocation::TYPE_FACTORY) {
                    $legacyStock = ManufactureUnitStock::query()->firstOrCreate(
                        ['product_id' => $lockedStock->product_id],
                        ['stock_quantity' => 0]
                    );

                    $legacyQty = (float) $legacyStock->stock_quantity;
                    $legacyStock->stock_quantity = max(0, $legacyQty - $quantity);
                    $legacyStock->save();
                }
            });
        } catch (\RuntimeException $exception) {
            return $this->redirectToIndex($tab)
                ->with('error', $exception->getMessage());
        }

        return $this->redirectToIndex($tab)
            ->with('success', 'Stock transferred for production successfully.');
    }

    /**
     * Transfer final goods from factory stock to outlet or warehouse.
     */
    public function transferFinalGoods(TransferFinalGoodsRequest $request, InventoryStock $stock)
    {
        $tab = (string) $request->input('tab', '');
        $validated = $request->validated();
        $destinationLocation = InventoryLocation::query()
            ->whereKey((int) $validated['to_location_id'])
            ->where('is_active', true)
            ->first();

        if (!$destinationLocation || !in_array($destinationLocation->type, [InventoryLocation::TYPE_OUTLET, InventoryLocation::TYPE_WAREHOUSE], true)) {
            return $this->redirectToIndex($tab)
                ->with('error', 'Destination must be an active outlet or warehouse location.');
        }

        $inventoryTypeCode = $destinationLocation->type === InventoryLocation::TYPE_OUTLET
            ? InventoryType::OUTLET
            : InventoryType::MANUFACTURING;
        $inventoryTypeId = InventoryType::query()
            ->where('code', $inventoryTypeCode)
            ->value('id');

        if (!$inventoryTypeId) {
            return $this->redirectToIndex($tab)
                ->with('error', 'Required inventory type is missing. Run inventory type seeder.');
        }

        $quantity = (float) $validated['quantity'];
        $userNotes = trim((string) ($validated['notes'] ?? ''));

        try {
            DB::transaction(function () use ($stock, $destinationLocation, $inventoryTypeId, $quantity, $userNotes): void {
                $sourceStock = InventoryStock::query()
                    ->with(['location:id,type', 'product.category:id,slug'])
                    ->lockForUpdate()
                    ->findOrFail($stock->id);

                if (!$sourceStock->location || $sourceStock->location->type !== InventoryLocation::TYPE_FACTORY) {
                    throw new \RuntimeException('Final goods transfer is allowed only from factory stock.');
                }

                if ((int) $sourceStock->location_id === (int) $destinationLocation->id) {
                    throw new \RuntimeException('Source and destination locations must be different.');
                }

                $categorySlug = strtolower((string) ($sourceStock->product?->category?->slug ?? ''));
                if ($categorySlug === 'fabrics') {
                    throw new \RuntimeException('Fabric items cannot be transferred as final goods. Use transfer for production.');
                }

                $availableQty = (float) $sourceStock->on_hand_qty;
                if ($availableQty < $quantity) {
                    throw new \RuntimeException('Insufficient stock in factory for this transfer.');
                }

                $sourceStock->on_hand_qty = $availableQty - $quantity;
                $sourceStock->save();

                $unitCost = (float) $sourceStock->unit_cost;
                $targetStock = InventoryStock::query()->firstOrCreate(
                    [
                        'location_id' => (int) $destinationLocation->id,
                        'product_id' => (int) $sourceStock->product_id,
                        'vendor_id' => $sourceStock->vendor_id,
                    ],
                    [
                        'unit_id' => null,
                        'on_hand_qty' => 0,
                        'reserved_qty' => 0,
                        'unit_cost' => $unitCost,
                    ]
                );

                $targetCurrentQty = (float) $targetStock->on_hand_qty;
                $targetNewQty = $targetCurrentQty + $quantity;
                $targetCurrentValue = $targetCurrentQty * (float) $targetStock->unit_cost;
                $incomingValue = $quantity * $unitCost;

                $targetStock->unit_cost = $targetNewQty > 0 ? (($targetCurrentValue + $incomingValue) / $targetNewQty) : 0;
                $targetStock->on_hand_qty = $targetNewQty;
                $targetStock->unit_id = null;
                $targetStock->save();

                $notes = 'Final goods transferred from factory stock.';
                if ($userNotes !== '') {
                    $notes .= ' ' . $userNotes;
                }

                $transaction = InventoryTransaction::query()->create([
                    'inventory_type_id' => $inventoryTypeId,
                    'trx_type' => InventoryTransaction::TYPE_TRANSFER,
                    'reference_type' => 'factory_final_goods_transfer',
                    'reference_id' => $sourceStock->id,
                    'from_location_id' => (int) $sourceStock->location_id,
                    'to_location_id' => (int) $destinationLocation->id,
                    'vendor_id' => $sourceStock->vendor_id,
                    'trx_date' => now(),
                    'notes' => $notes,
                    'created_by' => (int) auth()->id(),
                ]);

                $transaction->items()->create([
                    'product_id' => (int) $sourceStock->product_id,
                    'qty' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $quantity * $unitCost,
                ]);

                if (true) {
                    $legacyStock = ManufactureUnitStock::query()->firstOrCreate(
                        ['product_id' => (int) $sourceStock->product_id],
                        ['stock_quantity' => 0]
                    );

                    $legacyQty = (float) $legacyStock->stock_quantity;
                    $legacyStock->stock_quantity = max(0, $legacyQty - $quantity);
                    $legacyStock->save();
                }
            });
        } catch (\RuntimeException $exception) {
            return $this->redirectToIndex($tab)
                ->with('error', $exception->getMessage());
        }

        return $this->redirectToIndex($tab)
            ->with('success', 'Final goods transferred successfully.');
    }

    /**
     * Update production transfer workflow status.
     */
    public function updateTransferStatus(UpdateTransferStatusRequest $request, InventoryTransaction $transaction)
    {
        $tab = (string) $request->input('tab', '');
        if ($transaction->reference_type !== 'production_transfer') {
            return $this->redirectToIndex($tab)
                ->with('error', 'Only production transfer records can be updated from this screen.');
        }

        if ($transaction->status === InventoryTransaction::STATUS_COMPLETED) {
            return $this->redirectToIndex($tab)
                ->with('error', 'Completed transfers are locked and cannot be updated.');
        }

        $isManufacturing = InventoryType::query()
            ->whereKey($transaction->inventory_type_id)
            ->where('code', InventoryType::MANUFACTURING)
            ->exists();

        if (!$isManufacturing) {
            return $this->redirectToIndex($tab)
                ->with('error', 'Only manufacturing transfer records can be updated from this screen.');
        }

        $transaction->status = (string) $request->validated('status');
        $transaction->save();

        return $this->redirectToIndex($tab)
            ->with('success', 'Transfer status updated successfully.');
    }

    /**
     * Record finished goods production and add to inventory.
     */
    public function storeWorkflow(StoreWorkflowRequest $request)
    {
        $tab = (string) $request->input('tab', '');
        $validated = $request->validated();
        $inventoryTypeId = InventoryType::query()
            ->where('code', InventoryType::MANUFACTURING)
            ->value('id');

        if (!$inventoryTypeId) {
            return $this->redirectToIndex($tab)
                ->with('error', 'Inventory type manufacturing is missing. Run inventory type seeder.');
        }

        $location = InventoryLocation::query()
            ->where('is_active', true)
            ->where('type', InventoryLocation::TYPE_FACTORY)
            ->orderBy('name')
            ->first();

        if (!$location) {
            return $this->redirectToIndex($tab)
                ->with('error', 'No active factory location found. Create/activate a factory location first.');
        }

        $qty = (float) $validated['quantity'];
        $materialWastageQty = array_key_exists('material_wastage_qty', $validated) && $validated['material_wastage_qty'] !== null
            ? (float) $validated['material_wastage_qty']
            : null;
        $unitCost = (float) $validated['unit_cost'];

        try {
            DB::transaction(function () use ($inventoryTypeId, $location, $qty, $materialWastageQty, $unitCost, $validated): void {
                $transfer = InventoryTransaction::query()
                    ->with(['targetProduct.category:id,slug'])
                    ->lockForUpdate()
                    ->findOrFail((int) $validated['transfer_transaction_id']);

                if ($transfer->reference_type !== 'production_transfer') {
                    throw new \RuntimeException('Selected transfer is not a production transfer record.');
                }

                if ($transfer->status !== InventoryTransaction::STATUS_PROGRESS) {
                    throw new \RuntimeException('Only progress transfers can be used for production output.');
                }

                $product = $transfer->targetProduct;
                if (!$product) {
                    throw new \RuntimeException('Selected transfer has no target finished good. Create a new transfer with target product.');
                }

                if (!in_array($product->category?->slug, ['ready-made', 'accessories'], true)) {
                    throw new \RuntimeException('Transfer target must be ready-made or accessories.');
                }

                $transaction = InventoryTransaction::query()->create([
                    'inventory_type_id' => $inventoryTypeId,
                    'trx_type' => InventoryTransaction::TYPE_IN,
                    'reference_type' => 'production_output',
                    'reference_id' => $transfer->id,
                    'from_location_id' => null,
                    'to_location_id' => $location->id,
                    'vendor_id' => null,
                    'trx_date' => now(),
                    'material_wastage_qty' => $materialWastageQty,
                    'notes' => $validated['notes'] ?? 'Finished goods production recorded',
                    'created_by' => (int) auth()->id(),
                ]);

                $transaction->items()->create([
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $unitCost !== null ? ($qty * $unitCost) : null,
                ]);

                $stock = InventoryStock::query()->firstOrCreate(
                    [
                        'location_id' => $location->id,
                        'product_id' => $product->id,
                        'vendor_id' => null,
                    ],
                    [
                        'unit_id' => null,
                        'on_hand_qty' => 0,
                        'reserved_qty' => 0,
                        'unit_cost' => 0,
                    ]
                );

                $currentQty = (float) $stock->on_hand_qty;
                $newQty = $currentQty + $qty;
                $stock->unit_id = null;

                $currentValue = $currentQty * (float) $stock->unit_cost;
                $incomingValue = $qty * $unitCost;
                $stock->unit_cost = $newQty > 0 ? (($currentValue + $incomingValue) / $newQty) : 0;

                $stock->on_hand_qty = $newQty;
                $stock->save();

                if ($location->type === InventoryLocation::TYPE_FACTORY) {
                    $legacyStock = ManufactureUnitStock::query()->firstOrCreate(
                        ['product_id' => $product->id],
                        ['stock_quantity' => 0]
                    );
                    $legacyStock->increment('stock_quantity', $qty);
                }

            });
        } catch (\RuntimeException $exception) {
            return $this->redirectToIndex($tab)
                ->with('error', $exception->getMessage());
        }

        return $this->redirectToIndex($tab)
            ->with('success', 'Production output recorded successfully.');
    }

    /**
     * Transfer produced goods from production log to current outlet inventory.
     */
    public function transferProducedGoodsToCurrentOutlet(TransferProductionOutputRequest $request, InventoryTransaction $transaction)
    {
        $tab = (string) $request->input('tab', '');
        if ($transaction->reference_type !== 'production_output') {
            return $this->redirectToIndex($tab)
                ->with('error', 'Only production output records can be transferred from this table.');
        }

        $inventoryTypeId = InventoryType::query()
            ->where('code', InventoryType::OUTLET)
            ->value('id');

        if (!$inventoryTypeId) {
            return $this->redirectToIndex($tab)
                ->with('error', 'Inventory type outlet is missing. Run inventory type seeder.');
        }

        $currentOutletId = (int) (auth()->user()?->current_outlet_id ?? 0);
        if ($currentOutletId < 1) {
            return $this->redirectToIndex($tab)
                ->with('error', 'No current outlet selected. Switch outlet first.');
        }

        $outletLocation = InventoryLocation::query()
            ->where('is_active', true)
            ->where('type', InventoryLocation::TYPE_OUTLET)
            ->where('outlet_id', $currentOutletId)
            ->first();

        if (!$outletLocation) {
            return $this->redirectToIndex($tab)
                ->with('error', 'No active inventory location found for your current outlet.');
        }

        $quantity = (float) $request->validated('quantity');
        $userNotes = trim((string) ($request->validated('notes') ?? ''));

        try {
            DB::transaction(function () use ($transaction, $quantity, $userNotes, $outletLocation, $inventoryTypeId): void {
                $lockedOutput = InventoryTransaction::query()
                    ->with(['toLocation:id,name,type', 'items'])
                    ->lockForUpdate()
                    ->findOrFail($transaction->id);

                if ($lockedOutput->reference_type !== 'production_output') {
                    throw new \RuntimeException('Selected row is not a production output transaction.');
                }

                $outputItem = $lockedOutput->items->first();
                if (!$outputItem) {
                    throw new \RuntimeException('Production output transaction item is missing.');
                }

                $sourceLocationId = (int) ($lockedOutput->to_location_id ?? 0);
                if ($sourceLocationId < 1) {
                    throw new \RuntimeException('Production output source location is invalid.');
                }

                $sourceStock = InventoryStock::query()
                    ->where('location_id', $sourceLocationId)
                    ->where('product_id', (int) $outputItem->product_id)
                    ->whereNull('vendor_id')
                    ->lockForUpdate()
                    ->first();

                if (!$sourceStock) {
                    throw new \RuntimeException('Source stock not found for produced goods.');
                }

                $sourceQty = (float) $sourceStock->on_hand_qty;
                if ($sourceQty < $quantity) {
                    throw new \RuntimeException('Insufficient produced stock in factory for this transfer.');
                }

                $sourceStock->on_hand_qty = $sourceQty - $quantity;
                $sourceStock->save();

                $unitCost = (float) $sourceStock->unit_cost;
                $targetStock = InventoryStock::query()->firstOrCreate(
                    [
                        'location_id' => $outletLocation->id,
                        'product_id' => (int) $outputItem->product_id,
                        'vendor_id' => null,
                    ],
                    [
                        'unit_id' => null,
                        'on_hand_qty' => 0,
                        'reserved_qty' => 0,
                        'unit_cost' => $unitCost,
                    ]
                );

                $targetCurrentQty = (float) $targetStock->on_hand_qty;
                $targetNewQty = $targetCurrentQty + $quantity;
                $targetCurrentValue = $targetCurrentQty * (float) $targetStock->unit_cost;
                $incomingValue = $quantity * $unitCost;
                $targetStock->unit_cost = $targetNewQty > 0 ? (($targetCurrentValue + $incomingValue) / $targetNewQty) : 0;
                $targetStock->on_hand_qty = $targetNewQty;
                $targetStock->unit_id = null;
                $targetStock->save();

                $notes = 'Produced goods moved to current outlet inventory.';
                if ($userNotes !== '') {
                    $notes .= ' ' . $userNotes;
                }

                $distributionTransaction = InventoryTransaction::query()->create([
                    'inventory_type_id' => $inventoryTypeId,
                    'trx_type' => InventoryTransaction::TYPE_TRANSFER,
                    'reference_type' => 'production_output_distribution',
                    'reference_id' => $lockedOutput->id,
                    'from_location_id' => $sourceLocationId,
                    'to_location_id' => $outletLocation->id,
                    'vendor_id' => null,
                    'trx_date' => now(),
                    'notes' => $notes,
                    'created_by' => (int) auth()->id(),
                ]);

                $distributionTransaction->items()->create([
                    'product_id' => (int) $outputItem->product_id,
                    'qty' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $quantity * $unitCost,
                ]);

                if ($lockedOutput->toLocation?->type === InventoryLocation::TYPE_FACTORY) {
                    $legacyStock = ManufactureUnitStock::query()->firstOrCreate(
                        ['product_id' => (int) $outputItem->product_id],
                        ['stock_quantity' => 0]
                    );

                    $legacyQty = (float) $legacyStock->stock_quantity;
                    $legacyStock->stock_quantity = max(0, $legacyQty - $quantity);
                    $legacyStock->save();
                }
            });
        } catch (\RuntimeException $exception) {
            return $this->redirectToIndex($tab)
                ->with('error', $exception->getMessage());
        }

        return $this->redirectToIndex($tab)
            ->with('success', 'Produced goods transferred to current outlet inventory successfully.');
    }

    private function redirectToIndex(?string $tab = null)
    {
        $allowedTabs = ['stock-records', 'production-transfers', 'production-log'];
        $tab = in_array((string) $tab, $allowedTabs, true) ? (string) $tab : '';
        $url = route('manufactureUnit.index') . ($tab !== '' ? ('#' . $tab) : '');

        return redirect()->to($url);
    }
}
