<?php

namespace App\Http\Requests\Order;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Order|null $order */
        $order = $this->route('order');
        $nextStatuses = $order
            ? Order::nextStatusesFor((string) $order->status)
            : [];

        // Check if order has any fabric or custom with fabric
        $hasFabricOrCustom = false;
        if ($order) {
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
        }

        $customNextStatuses = $nextStatuses;
        if (!$hasFabricOrCustom) {
            // For non-fabric/non-custom, allow only in_progress after confirmed, delivered after in_progress
            $customNextStatuses = [];
            if ((string) $order->status === \App\Models\Order::STATUS_CONFIRMED) {
                $customNextStatuses[] = \App\Models\Order::STATUS_IN_PROGRESS;
            } elseif ((string) $order->status === \App\Models\Order::STATUS_IN_PROGRESS) {
                $customNextStatuses[] = \App\Models\Order::STATUS_DELIVERED;
            }
        }

        return [
            'status' => [
                'required',
                Rule::in([
                    ...$customNextStatuses,
                ]),
            ],
            'remaining_payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $status = (string) $this->input('status', '');

            if ($status === Order::STATUS_DELIVERED && empty($this->input('payment_method'))) {
                $validator->errors()->add('payment_method', 'Payment method is required when delivering the order.');
            }
        });
    }
}
