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

        if (!Order::canTransition((string) $order->status, $toStatus)) {
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
