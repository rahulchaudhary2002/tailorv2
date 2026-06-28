<?php

namespace App\Services;

use App\Models\InventoryLocation;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\InventoryType;
use App\Models\Order;
use Illuminate\Support\Collection;

class OrderInventoryService
{
    public function orderHasIssuedStock(Order $order): bool
    {
        return $this->statusRequiresIssuedStock((string) $order->status);
    }

    public function statusRequiresIssuedStock(string $status): bool
    {
        return in_array($status, [
            Order::STATUS_FABRIC_ISSUED,
            Order::STATUS_ASSIGNED,
            Order::STATUS_IN_PROGRESS,
            Order::STATUS_NEAR_COMPLETION,
            Order::STATUS_COMPLETED,
            Order::STATUS_DELIVERED,
        ], true);
    }

    public function resolveOutletLocationId(int $outletId): int
    {
        if ($outletId < 1) {
            return 0;
        }

        return (int) (InventoryLocation::query()
            ->where('outlet_id', $outletId)
            ->where('type', InventoryLocation::TYPE_OUTLET)
            ->where('is_active', true)
            ->value('id') ?? 0);
    }

    public function resolveOutletInventoryTypeId(): int
    {
        return (int) (InventoryType::query()
            ->where('code', InventoryType::OUTLET)
            ->value('id') ?? 0);
    }

    public function reserveStockForOrder(Order $order, int $locationId): void
    {
        foreach ($this->getOrderCommittedStockMap($order) as $productId => $qty) {
            $this->reserveOutletStock($locationId, (int) $productId, (float) $qty);
        }
    }

    public function releaseReservedStockForOrder(Order $order, int $locationId): void
    {
        foreach ($this->getOrderCommittedStockMap($order) as $productId => $qty) {
            $this->releaseReservedOutletStock($locationId, (int) $productId, (float) $qty);
        }
    }

    public function issueStockForOrder(
        Order $order,
        int $locationId,
        int $inventoryTypeId,
        mixed $trxDate,
        int $createdBy
    ): void {
        $requirements = $this->getOrderCommittedStockMap($order);

        foreach ($requirements as $productId => $qty) {
            $this->releaseReservedOutletStock($locationId, (int) $productId, (float) $qty);

            $averageCost = $this->deductFromOutletStock(
                locationId: $locationId,
                productId: (int) $productId,
                requiredQty: (float) $qty
            );

            $transaction = InventoryTransaction::query()->create([
                'inventory_type_id' => $inventoryTypeId,
                'trx_type' => InventoryTransaction::TYPE_OUT,
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'from_location_id' => $locationId,
                'to_location_id' => null,
                'vendor_id' => null,
                'trx_date' => $trxDate,
                'notes' => 'Order '.$order->order_number.' stock deduction',
                'created_by' => $createdBy,
            ]);

            $transaction->items()->create([
                'product_id' => (int) $productId,
                'qty' => (float) $qty,
                'unit_cost' => $averageCost,
                'total_cost' => $averageCost !== null ? $averageCost * (float) $qty : null,
            ]);
        }
    }

    public function restoreOrderInventory(Order $order): void
    {
        $transactions = InventoryTransaction::query()
            ->where('reference_type', 'order')
            ->where('reference_id', (int) $order->id)
            ->where('trx_type', InventoryTransaction::TYPE_OUT)
            ->with('items')
            ->lockForUpdate()
            ->get();

        foreach ($transactions as $transaction) {
            $locationId = (int) ($transaction->from_location_id ?? 0);
            if ($locationId < 1) {
                $transaction->items()->delete();
                $transaction->delete();

                continue;
            }

            foreach ($transaction->items as $transactionItem) {
                $productId = (int) ($transactionItem->product_id ?? 0);
                $qty = (float) ($transactionItem->qty ?? 0);
                $unitCost = (float) ($transactionItem->unit_cost ?? 0);

                if ($productId < 1 || $qty <= 0) {
                    continue;
                }

                $stock = InventoryStock::query()
                    ->where('location_id', $locationId)
                    ->where('product_id', $productId)
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if (! $stock) {
                    $stock = InventoryStock::query()->create([
                        'location_id' => $locationId,
                        'product_id' => $productId,
                        'vendor_id' => null,
                        'unit_id' => null,
                        'on_hand_qty' => 0,
                        'reserved_qty' => 0,
                        'unit_cost' => $unitCost,
                    ]);
                }

                $currentQty = (float) $stock->on_hand_qty;
                $currentValue = $currentQty * (float) $stock->unit_cost;
                $incomingValue = $qty * $unitCost;
                $newQty = $currentQty + $qty;

                $stock->unit_cost = $newQty > 0 ? (($currentValue + $incomingValue) / $newQty) : 0;
                $stock->on_hand_qty = $newQty;
                $stock->save();
            }

            $transaction->items()->delete();
            $transaction->delete();
        }
    }

    /**
     * @return array<string, float>
     */
    public function getOrderCommittedStockMap(Order $order): array
    {
        $order->loadMissing('items');

        $requirements = [];

        foreach ($order->items as $item) {
            $itemCategory = (string) ($item->item_category ?? '');

            if ($itemCategory === 'custom') {
                if ((string) data_get($item->custom_details, 'fabric_source') !== 'stock') {
                    continue;
                }

                $productId = (int) data_get($item->custom_details, 'fabric_product_id', 0);
                $qty = (float) data_get($item->custom_details, 'fabric_quantity', $item->quantity);
            } else {
                $productId = (int) ($item->product_id ?? 0);
                $qty = (float) ($item->quantity ?? 0);
            }

            if ($productId < 1 || $qty <= 0) {
                continue;
            }

            $stockKey = (string) $productId;
            if (! array_key_exists($stockKey, $requirements)) {
                $requirements[$stockKey] = 0.0;
            }

            $requirements[$stockKey] += $qty;
        }

        return $requirements;
    }

    private function deductFromOutletStock(int $locationId, int $productId, float $requiredQty): ?float
    {
        $remainingQty = $requiredQty;

        $stocks = InventoryStock::query()
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $availableQty = (float) $stocks->sum(function (InventoryStock $stock) {
            return max(0, (float) $stock->on_hand_qty - (float) $stock->reserved_qty);
        });

        if ($availableQty < $requiredQty) {
            throw new \RuntimeException('Insufficient stock for one or more order items at current outlet.');
        }

        $totalCost = 0.0;
        $consumedQty = 0.0;

        /** @var Collection<int, InventoryStock> $stocks */
        foreach ($stocks as $stock) {
            if ($remainingQty <= 0) {
                break;
            }

            $availableStockQty = max(0, (float) $stock->on_hand_qty - (float) $stock->reserved_qty);
            if ($availableStockQty <= 0) {
                continue;
            }

            $deductQty = min($remainingQty, $availableStockQty);
            $stock->on_hand_qty = (float) $stock->on_hand_qty - $deductQty;
            $stock->save();

            $cost = (float) $stock->unit_cost;
            $totalCost += $deductQty * $cost;
            $consumedQty += $deductQty;
            $remainingQty -= $deductQty;
        }

        if ($consumedQty <= 0) {
            return null;
        }

        return $totalCost / $consumedQty;
    }

    private function reserveOutletStock(int $locationId, int $productId, float $requiredQty): void
    {
        $remainingQty = $requiredQty;

        $stocks = InventoryStock::query()
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $availableQty = (float) $stocks->sum(function (InventoryStock $stock) {
            return max(0, (float) $stock->on_hand_qty - (float) $stock->reserved_qty);
        });

        if ($availableQty + 0.000001 < $requiredQty) {
            throw new \RuntimeException('Insufficient stock for one or more order items at current outlet.');
        }

        foreach ($stocks as $stock) {
            if ($remainingQty <= 0) {
                break;
            }

            $availableStockQty = max(0, (float) $stock->on_hand_qty - (float) $stock->reserved_qty);
            if ($availableStockQty <= 0) {
                continue;
            }

            $reserveQty = min($remainingQty, $availableStockQty);
            $stock->reserved_qty = (float) $stock->reserved_qty + $reserveQty;
            $stock->save();
            $remainingQty -= $reserveQty;
        }
    }

    private function releaseReservedOutletStock(int $locationId, int $productId, float $qtyToRelease): void
    {
        if ($qtyToRelease <= 0) {
            return;
        }

        $remainingQty = $qtyToRelease;

        $stocks = InventoryStock::query()
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->where('reserved_qty', '>', 0)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->get();

        foreach ($stocks as $stock) {
            if ($remainingQty <= 0) {
                break;
            }

            $reservedQty = (float) $stock->reserved_qty;
            if ($reservedQty <= 0) {
                continue;
            }

            $releaseQty = min($remainingQty, $reservedQty);
            $stock->reserved_qty = $reservedQty - $releaseQty;
            $stock->save();
            $remainingQty -= $releaseQty;
        }
    }
}
