<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inventory\AdjustStockRequest;
use App\Models\InventoryAlert;
use App\Models\InventoryLocation;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\InventoryType;
use App\Models\InventoryReorderLevel;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display inventory records by location.
     */
    public function index(Request $request)
    {
        $outletId = $this->currentOutletId();
        $q = trim((string) $request->query('q', ''));
        $qLower = mb_strtolower($q);
        $productFilterId = (int) $request->query('product_id', 0);
        $locationFilterId = (int) $request->query('location_id', 0);
        $vendorFilterId = (int) $request->query('vendor_id', 0);

        $stocksQuery = InventoryStock::query()
            ->whereHas('location', function ($query) use ($outletId) {
                $query->where('is_active', true)
                    ->where(function ($nested) use ($outletId) {
                        $nested->whereIn('type', [
                            InventoryLocation::TYPE_WAREHOUSE,
                            InventoryLocation::TYPE_FACTORY,
                        ])->orWhere(function ($outletQuery) use ($outletId) {
                            $outletQuery->where('type', InventoryLocation::TYPE_OUTLET)
                                ->where('outlet_id', $outletId);
                        });
                    });
            })
            ->with([
                'location:id,name,type,outlet_id',
                'location.outlet:id,name',
                'product:id,name,code',
                'vendor:id,name',
                'unit:id,name,symbol',
            ]);

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

        if ($locationFilterId > 0) {
            $stocksQuery->where('location_id', $locationFilterId);
        }

        if ($productFilterId > 0) {
            $stocksQuery->where('product_id', $productFilterId);
        }

        if ($vendorFilterId > 0) {
            $stocksQuery->where('vendor_id', $vendorFilterId);
        }

        $stocks = $stocksQuery
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $locations = InventoryLocation::query()
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
            ->get(['id', 'name', 'type', 'outlet_id']);

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $vendors = Vendor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $alerts = InventoryAlert::query()
            ->with(['product:id,name,code', 'location:id,name,type'])
            ->whereHas('location', function ($query) use ($outletId) {
                $query->where('type', InventoryLocation::TYPE_OUTLET)
                    ->where('outlet_id', $outletId);
            })
            ->where('status', InventoryAlert::STATUS_OPEN)
            ->latest()
            ->limit(10)
            ->get();

        $stats = [
            'locations_count' => InventoryLocation::query()
                ->where('is_active', true)
                ->where('type', InventoryLocation::TYPE_OUTLET)
                ->where('outlet_id', $outletId)
                ->count(),
            'products_in_stock' => InventoryStock::query()
                ->whereHas('location', function ($query) use ($outletId) {
                    $query->where('type', InventoryLocation::TYPE_OUTLET)
                        ->where('outlet_id', $outletId);
                })
                ->distinct('product_id')
                ->count('product_id'),
            'total_quantity' => (float) InventoryStock::query()
                ->whereHas('location', function ($query) use ($outletId) {
                    $query->where('type', InventoryLocation::TYPE_OUTLET)
                        ->where('outlet_id', $outletId);
                })
                ->sum('on_hand_qty'),
            'open_low_stock_alerts' => InventoryAlert::query()
                ->whereHas('location', function ($query) use ($outletId) {
                    $query->where('type', InventoryLocation::TYPE_OUTLET)
                        ->where('outlet_id', $outletId);
                })
                ->where('status', InventoryAlert::STATUS_OPEN)
                ->count(),
        ];

        return view('modules.inventory.index', compact('stocks', 'locations', 'products', 'vendors', 'stats', 'alerts'));
    }

    /**
     * Record inventory transaction and update summary stock.
     */
    public function adjust(AdjustStockRequest $request)
    {
        $tab = (string) $request->input('tab', '');
        $outletId = $this->currentOutletId();
        if ($outletId < 1) {
            return $this->redirectToIndex($tab)
                ->with('error', 'Set your current outlet before adjusting inventory.');
        }

        $validated = $request->validated();
        $product = Product::query()->findOrFail((int) $validated['product_id']);
        $unitId = $this->resolveInventoryUnitIdForProduct((int) $product->id);
        $quantity = (float) $validated['quantity'];
        $trxType = (string) $validated['trx_type'];

        $vendorId = isset($validated['vendor_id']) ? (int) $validated['vendor_id'] : null;
        $unitCost = (float) $validated['unit_cost'];
        $adjustmentType = (string) ($validated['adjustment_type'] ?? 'add');
        $setReorderLevel = $request->boolean('set_reorder_level');
        $reorderMinQty = array_key_exists('reorder_min_qty', $validated) && $validated['reorder_min_qty'] !== null
            ? (float) $validated['reorder_min_qty']
            : null;
        $reorderQty = array_key_exists('reorder_qty', $validated) && $validated['reorder_qty'] !== null
            ? (float) $validated['reorder_qty']
            : null;

        $locationId = isset($validated['location_id']) ? (int) $validated['location_id'] : null;
        $fromLocationId = isset($validated['from_location_id']) ? (int) $validated['from_location_id'] : null;
        $toLocationId = isset($validated['to_location_id']) ? (int) $validated['to_location_id'] : null;

        $isTransfer = $trxType === InventoryTransaction::TYPE_TRANSFER;
        if (
            (!$isTransfer && !$this->isValidSingleLocationForUser($locationId, $outletId)) ||
            ($isTransfer && !$this->isValidActiveLocation($fromLocationId)) ||
            ($isTransfer && !$this->isValidActiveLocation($toLocationId))
        ) {
            return $this->redirectToIndex($tab)
                ->with('error', 'Selected location is not valid for this transaction.');
        }

        if (in_array($trxType, [InventoryTransaction::TYPE_IN, InventoryTransaction::TYPE_OUT, InventoryTransaction::TYPE_ADJUSTMENT], true) && !$locationId) {
            return $this->redirectToIndex($tab)->with('error', 'Location is required for selected transaction type.');
        }

        if ($trxType === InventoryTransaction::TYPE_TRANSFER && (!$fromLocationId || !$toLocationId || $fromLocationId === $toLocationId)) {
            return $this->redirectToIndex($tab)->with('error', 'Valid source and destination locations are required for transfer.');
        }

        if ($setReorderLevel && $reorderMinQty === null) {
            return $this->redirectToIndex($tab)
                ->with('error', 'Reorder minimum quantity is required when enabling reorder level.');
        }

        $inventoryTypeId = $this->resolveInventoryTypeId($trxType, $locationId, $fromLocationId);
        if (!$inventoryTypeId) {
            return $this->redirectToIndex($tab)
                ->with('error', 'Unable to resolve inventory type from selected location.');
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
                $vendorId,
                $unitId,
                $unitCost,
                $adjustmentType,
                $inventoryTypeId,
                $setReorderLevel,
                $reorderMinQty,
                $reorderQty
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
                    'vendor_id' => $vendorId,
                    'trx_date' => now(),
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => (int) auth()->id(),
                ]);

                $transaction->items()->create([
                    'product_id' => $product->id,
                    'qty' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $unitCost !== null ? $unitCost * $quantity : null,
                ]);

                if ($trxType === InventoryTransaction::TYPE_TRANSFER) {
                    $updatedTargetStocks = $this->transferStockBetweenLocations(
                        productId: (int) $product->id,
                        fromLocationId: (int) $fromLocationId,
                        toLocationId: (int) $toLocationId,
                        vendorId: $vendorId,
                        unitId: $unitId,
                        quantity: $quantity,
                        fallbackUnitCost: $unitCost
                    );

                    if ($setReorderLevel && $reorderMinQty !== null) {
                        $this->upsertReorderLevel(
                            productId: (int) $product->id,
                            locationId: (int) $toLocationId,
                            minQty: $reorderMinQty,
                            reorderQty: $reorderQty
                        );
                    }

                    foreach ($updatedTargetStocks as $targetStock) {
                        $this->syncLowStockAlert($targetStock);
                    }
                    return;
                }

                if ($trxType === InventoryTransaction::TYPE_ADJUSTMENT && $adjustmentType === 'set') {
                    $stock = $this->getOrCreateStock(
                        productId: (int) $product->id,
                        locationId: (int) $locationId,
                        vendorId: $vendorId,
                        unitId: $unitId
                    );
                    $delta = $quantity - (float) $stock->on_hand_qty;
                    $stock = $this->applyDeltaToStock(
                        productId: (int) $product->id,
                        locationId: (int) $locationId,
                        vendorId: $vendorId,
                        unitId: $unitId,
                        delta: $delta,
                        unitCost: $unitCost
                    );

                    if ($setReorderLevel && $reorderMinQty !== null) {
                        $this->upsertReorderLevel(
                            productId: (int) $product->id,
                            locationId: (int) $locationId,
                            minQty: $reorderMinQty,
                            reorderQty: $reorderQty
                        );
                    }

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
                    vendorId: $vendorId,
                    unitId: $unitId,
                    delta: $signedQty,
                    unitCost: $unitCost
                );

                if ($setReorderLevel && $reorderMinQty !== null) {
                    $this->upsertReorderLevel(
                        productId: (int) $product->id,
                        locationId: (int) $locationId,
                        minQty: $reorderMinQty,
                        reorderQty: $reorderQty
                    );
                }

                $this->syncLowStockAlert($stock);
            });
        } catch (\RuntimeException $exception) {
            return $this->redirectToIndex($tab)
                ->with('error', $exception->getMessage());
        }

        $this->notifyInventoryRecipients(
            $product,
            $trxType,
            $quantity,
            $outletId,
            route('inventory.index')
        );

        return $this->redirectToIndex($tab)
            ->with('success', 'Inventory updated successfully.');
    }

    private function notifyInventoryRecipients(Product $product, string $trxType, float $quantity, int $outletId, string $url): void
    {
        $actorName = (string) (auth()->user()?->name ?: 'System');

        app(NotificationService::class)->notifyPermission(
            'receive-inventory-notifications',
            $outletId,
            [
                'title' => 'Inventory updated',
                'message' => $actorName . ': ' . ucfirst($trxType) . ' transaction of ' . number_format($quantity, 2) . ' ' . $product->defaultUnitLabel() . ' recorded for ' . $product->name . '.',
                'url' => $url,
                'module' => 'Inventory',
            ],
            array_filter([(int) auth()->id()])
        );
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

        $query = InventoryLocation::query()
            ->whereKey($locationId)
            ->where('is_active', true);

        return $query
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

    private function redirectToIndex(?string $tab = null)
    {
        $allowedTabs = ['transaction', 'alerts', 'stock-summary'];
        $tab = in_array((string) $tab, $allowedTabs, true) ? (string) $tab : '';
        $url = route('inventory.index') . ($tab !== '' ? ('#' . $tab) : '');

        return redirect()->to($url);
    }

    private function getOrCreateStock(int $productId, int $locationId, ?int $vendorId, ?int $unitId): InventoryStock
    {
        $stock = InventoryStock::query()->firstOrCreate(
            [
                'product_id' => $productId,
                'location_id' => $locationId,
                'vendor_id' => $vendorId,
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
        ?int $vendorId,
        ?int $unitId,
        float $delta,
        float $unitCost
    ): InventoryStock {
        if ($delta < 0) {
            return $this->consumeStockFifo(
                productId: $productId,
                locationId: $locationId,
                vendorId: $vendorId,
                quantity: abs($delta)
            );
        }

        $stock = $this->getOrCreateStock($productId, $locationId, $vendorId, $unitId);

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
        ?int $vendorId,
        float $quantity
    ): InventoryStock {
        $sourceStocks = InventoryStock::query()
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->when($vendorId !== null, function ($query) use ($vendorId) {
                $query->where('vendor_id', $vendorId);
            })
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

        return $lastTouchedStock ?: $this->getOrCreateStock($productId, $locationId, $vendorId, null);
    }

    /**
     * @return array<int, InventoryStock>
     */
    private function transferStockBetweenLocations(
        int $productId,
        int $fromLocationId,
        int $toLocationId,
        ?int $vendorId,
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
            ->when($vendorId !== null, function ($query) use ($vendorId) {
                $query->where('vendor_id', $vendorId);
            })
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

            $targetVendorId = $vendorId !== null ? $vendorId : (int) ($sourceStock->vendor_id ?? 0);
            $normalizedVendorId = $targetVendorId > 0 ? $targetVendorId : null;
            $targetStock = $this->getOrCreateStock($productId, $toLocationId, $normalizedVendorId, $unitId);

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

    private function upsertReorderLevel(int $productId, int $locationId, float $minQty, ?float $reorderQty): void
    {
        InventoryReorderLevel::query()->updateOrCreate(
            [
                'product_id' => $productId,
                'location_id' => $locationId,
            ],
            [
                'min_qty' => $minQty,
                'reorder_qty' => $reorderQty,
                'is_active' => true,
            ]
        );
    }

    private function resolveInventoryUnitIdForProduct(int $productId): ?int
    {
        return Product::query()
            ->with('category:id,slug')
            ->find($productId)
            ?->resolveDefaultUnitId();
    }
}
