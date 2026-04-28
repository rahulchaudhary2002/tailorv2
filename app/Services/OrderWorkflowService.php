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

        if (!array_key_exists($toStatus, Order::statusLabels())) {
            throw new \RuntimeException('Invalid order status transition.');
        }

        $now = now();
        $fabricIssuedOrLaterStatuses = [
            Order::STATUS_FABRIC_ISSUED,
            Order::STATUS_ASSIGNED,
            Order::STATUS_IN_PROGRESS,
            Order::STATUS_NEAR_COMPLETION,
            Order::STATUS_COMPLETED,
            Order::STATUS_DELIVERED,
        ];

        if (in_array($toStatus, $fabricIssuedOrLaterStatuses, true) && !$order->fabric_issued_at) {
            $order->fabric_issued_at = $now;
        }

        $order->completed_at = in_array($toStatus, [Order::STATUS_COMPLETED, Order::STATUS_DELIVERED], true)
            ? ($order->completed_at ?? $now)
            : null;

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

        if (!in_array($toStatus, $fabricIssuedOrLaterStatuses, true)) {
            $order->fabric_issued_at = null;
            $order->delivered_at = null;
            $order->closed_at = null;
        }

        if ($toStatus !== Order::STATUS_DELIVERED && $toStatus !== Order::STATUS_CANCELLED) {
            $order->delivered_at = null;
            $order->closed_at = null;
        }

        $order->status = $toStatus;
        $order->save();
    }
}
