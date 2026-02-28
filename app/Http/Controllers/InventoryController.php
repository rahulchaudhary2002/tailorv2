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
use App\Models\Unit;
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
            $stocksQuery->where(function ($query) use ($q): void {
                $query->whereHas('product', function ($productQuery) use ($q): void {
                    $productQuery->where('name', 'like', '%' . $q . '%')
                        ->orWhere('code', 'like', '%' . $q . '%');
                })->orWhereHas('location', function ($locationQuery) use ($q): void {
                    $locationQuery->where('name', 'like', '%' . $q . '%');
                });
            });
        }

        $reporting = [
            'total' => (clone $stocksQuery)->count(),
            'added_this_week' => (clone $stocksQuery)->where('created_at', '>=', now()->startOfWeek())->count(),
            'added_this_month' => (clone $stocksQuery)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'added_last_30_days' => (clone $stocksQuery)->where('created_at', '>=', now()->subDays(30))->count(),
        ];

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

        return view('modules.inventory.index', compact('stocks', 'locations', 'products', 'stats', 'alerts', 'reporting'));
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

        $basePrice = (float) $validated['base_price'];
        $specialPrice = array_key_exists('special_price', $validated) && $validated['special_price'] !== null
            ? (float) $validated['special_price']
            : null;
        $vendorId = isset($validated['vendor_id']) ? (int) $validated['vendor_id'] : null;
        $unitCost = array_key_exists('unit_cost', $validated) && $validated['unit_cost'] !== null
            ? (float) $validated['unit_cost']
            : null;
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
                $basePrice,
                $specialPrice,
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
                    $this->applyDeltaToStock(
                        productId: (int) $product->id,
                        locationId: (int) $fromLocationId,
                        vendorId: $vendorId,
                        unitId: $unitId,
                        delta: -$quantity,
                        unitCost: $unitCost,
                        basePrice: $basePrice,
                        specialPrice: $specialPrice
                    );

                    $toStock = $this->applyDeltaToStock(
                        productId: (int) $product->id,
                        locationId: (int) $toLocationId,
                        vendorId: $vendorId,
                        unitId: $unitId,
                        delta: $quantity,
                        unitCost: $unitCost,
                        basePrice: $basePrice,
                        specialPrice: $specialPrice
                    );

                    if ($setReorderLevel && $reorderMinQty !== null) {
                        $this->upsertReorderLevel(
                            productId: (int) $product->id,
                            locationId: (int) $toLocationId,
                            minQty: $reorderMinQty,
                            reorderQty: $reorderQty
                        );
                    }

                    $this->syncLowStockAlert($toStock);
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
                        unitCost: $unitCost,
                        basePrice: $basePrice,
                        specialPrice: $specialPrice
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
                    unitCost: $unitCost,
                    basePrice: $basePrice,
                    specialPrice: $specialPrice
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

        return $this->redirectToIndex($tab)
            ->with('success', 'Inventory updated successfully.');
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
                'avg_cost' => 0,
                'base_price' => 0,
                'special_price' => null,
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
        ?float $unitCost,
        float $basePrice,
        ?float $specialPrice
    ): InventoryStock {
        $stock = $this->getOrCreateStock($productId, $locationId, $vendorId, $unitId);

        $currentQty = (float) $stock->on_hand_qty;
        $newQty = $currentQty + $delta;

        if ($newQty < 0) {
            throw new \RuntimeException('Insufficient stock for this transaction.');
        }

        if ($unitCost !== null && $delta > 0) {
            $currentValue = $currentQty * (float) $stock->avg_cost;
            $incomingValue = $delta * $unitCost;
            $stock->avg_cost = $newQty > 0 ? (($currentValue + $incomingValue) / $newQty) : 0;
        }

        $stock->on_hand_qty = $newQty;
        $stock->base_price = $basePrice;
        $stock->special_price = $specialPrice;
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
