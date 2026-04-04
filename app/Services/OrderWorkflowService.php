<?php

namespace App\Services;

use App\Models\Order;

class OrderWorkflowService
{
    /**
     * @param array<string, mixed> $payload
     */
    public function transition(Order $order, array $payload): void
    {

        $toStatus = (string) ($payload['status'] ?? '');

        // Check if order has any fabric or custom with fabric
        $hasFabricOrCustom = false;
        $order->loadMissing('items');
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

        $fromStatus = (string) $order->status;
        $allowSimple = false;
        if (!$hasFabricOrCustom) {
            if (($fromStatus === Order::STATUS_CONFIRMED && $toStatus === Order::STATUS_IN_PROGRESS)
                || ($fromStatus === Order::STATUS_IN_PROGRESS && $toStatus === Order::STATUS_DELIVERED)) {
                $allowSimple = true;
            }
        }

        if (!$allowSimple && !Order::canTransition($fromStatus, $toStatus)) {
            throw new \RuntimeException('Invalid order status transition.');
        }

        $now = now();

        if ($toStatus === Order::STATUS_FABRIC_ISSUED && !$order->fabric_issued_at) {
            $order->fabric_issued_at = $now;
        }

        if ($toStatus === Order::STATUS_COMPLETED && !$order->completed_at) {
            $order->completed_at = $now;
        }

        if ($toStatus === Order::STATUS_DELIVERED) {
            $remainingDue = $order->dueAmount();

            $remainingPayment = (float) ($payload['remaining_payment_amount'] ?? 0);
            if ($remainingPayment + 0.0001 < $remainingDue) {
                throw new \RuntimeException('Collect full remaining payment before marking order delivered.');
            }

            if ($remainingPayment - 0.0001 > $remainingDue) {
                throw new \RuntimeException('Remaining payment cannot be greater than due amount.');
            }

            $order->advance_payment_amount = (float) ($order->advance_payment_amount ?? 0) + $remainingPayment;
            $order->payment_status = Order::PAYMENT_STATUS_PAID;
            $order->delivered_at = $payload['delivered_at'] ?? $now;
            $order->closed_at = $payload['closed_at'] ?? $now;

            if (!empty($payload['payment_method'])) {
                $order->payment_method = (string) $payload['payment_method'];
            }
        }

        if ($toStatus === Order::STATUS_CANCELLED) {
            $order->closed_at = $payload['closed_at'] ?? $now;
        }

        $order->status = $toStatus;
        $order->save();
    }
}
