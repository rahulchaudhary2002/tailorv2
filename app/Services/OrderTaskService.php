<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderTask;

class OrderTaskService
{
    /**
     * Sync custom garment tasks for active orders in an outlet.
     */
    public function syncForOutlet(int $outletId): void
    {
        if ($outletId < 1) {
            return;
        }

        $orders = Order::query()
            ->with(['items'])
            ->where('outlet_id', $outletId)
            ->whereIn('status', [
                Order::STATUS_CONFIRMED,
                Order::STATUS_FABRIC_ISSUED,
                Order::STATUS_ASSIGNED,
                Order::STATUS_IN_PROGRESS,
                Order::STATUS_NEAR_COMPLETION,
                Order::STATUS_COMPLETED,
                Order::STATUS_DELIVERED,
            ])
            ->get();

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if ((string) $item->item_category !== 'custom') {
                    continue;
                }

                $garments = collect((array) data_get($item->custom_details, 'garments', []))->values();
                foreach ($garments as $index => $garment) {
                    $quantity = max(1, (float) ($garment['quantity'] ?? 1));
                    $rateAmount = max(0, (float) ($garment['tailoring_amount'] ?? 0));
                    $task = OrderTask::query()->updateOrCreate(
                        [
                            'order_item_id' => (int) $item->id,
                            'source_garment_index' => (int) $index,
                        ],
                        [
                            'order_id' => (int) $order->id,
                            'garment_type_id' => !empty($garment['garment_type_id']) ? (int) $garment['garment_type_id'] : null,
                            'task_title' => trim((string) ($garment['garment_title'] ?? 'Custom Task')) ?: 'Custom Task',
                            'quantity' => $quantity,
                            'rate_amount' => $rateAmount,
                            'payable_amount' => $quantity * $rateAmount,
                            'created_by' => (int) ($order->created_by ?? auth()->id()),
                        ]
                    );

                    if (!$task->task_number) {
                        $task->task_number = sprintf('TASK-%06d', $task->id);
                        $task->save();
                    }
                }
            }
        }
    }

    public function syncOrderStatus(Order $order): void
    {
        if (in_array((string) $order->status, [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED], true)) {
            return;
        }

        $tasks = $order->tasks()->get(['status']);
        if ($tasks->isEmpty()) {
            return;
        }

        // Check if any order item is fabric or custom with fabric
        $hasFabricOrCustom = false;
        foreach ($order->items as $item) {
            if ((string) $item->item_category === 'custom') {
                $fabricProductId = (int) data_get($item->custom_details, 'fabric_product_id', 0);
                $fabricQty = (float) data_get($item->custom_details, 'fabric_quantity', 0);
                if ($fabricProductId > 0 && $fabricQty > 0) {
                    $hasFabricOrCustom = true;
                    break;
                }
            } elseif ((string) $item->item_category === 'fabric') {
                $hasFabricOrCustom = true;
                break;
            }
        }

        $activeTasks = $tasks->filter(fn ($task) => (string) $task->status !== OrderTask::STATUS_CANCELLED)->values();

        if (!$hasFabricOrCustom) {
            // For non-fabric/non-custom, only allow in progress and delivered
            if ($activeTasks->isEmpty()) {
                $nextStatus = Order::STATUS_CONFIRMED;
            } elseif ($activeTasks->every(fn($task) => (string) $task->status === OrderTask::STATUS_COMPLETED)) {
                $nextStatus = Order::STATUS_DELIVERED;
            } elseif ($activeTasks->contains(fn($task) => (string) $task->status === OrderTask::STATUS_IN_PROGRESS)) {
                $nextStatus = Order::STATUS_IN_PROGRESS;
            } else {
                $nextStatus = Order::STATUS_CONFIRMED;
            }
        } else {
            if ($activeTasks->isEmpty()) {
                $nextStatus = $order->fabric_issued_at
                    ? Order::STATUS_FABRIC_ISSUED
                    : Order::STATUS_CONFIRMED;
            } elseif ($activeTasks->every(fn ($task) => (string) $task->status === OrderTask::STATUS_COMPLETED)) {
                $nextStatus = Order::STATUS_COMPLETED;
            } elseif ($activeTasks->contains(fn ($task) => (string) $task->status === OrderTask::STATUS_IN_PROGRESS)) {
                $nextStatus = Order::STATUS_IN_PROGRESS;
            } elseif ($activeTasks->contains(fn ($task) => (string) $task->status === OrderTask::STATUS_COMPLETED)) {
                $nextStatus = Order::STATUS_NEAR_COMPLETION;
            } elseif ($activeTasks->contains(fn ($task) => (string) $task->status === OrderTask::STATUS_ASSIGNED)) {
                $nextStatus = Order::STATUS_ASSIGNED;
            } else {
                $nextStatus = $order->fabric_issued_at
                    ? Order::STATUS_FABRIC_ISSUED
                    : Order::STATUS_CONFIRMED;
            }
        }

        $order->status = $nextStatus;

        if ($hasFabricOrCustom && in_array($nextStatus, [
            Order::STATUS_FABRIC_ISSUED,
            Order::STATUS_ASSIGNED,
            Order::STATUS_IN_PROGRESS,
            Order::STATUS_NEAR_COMPLETION,
            Order::STATUS_COMPLETED,
        ], true)) {
            $order->fabric_issued_at = $order->fabric_issued_at ?? now();
        }

        $order->completed_at = $nextStatus === Order::STATUS_COMPLETED || $nextStatus === Order::STATUS_DELIVERED
            ? ($order->completed_at ?? now())
            : null;

        $order->save();
    }
}
