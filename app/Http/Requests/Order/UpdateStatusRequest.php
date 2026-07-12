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
        return [
            'status' => [
                'required',
                Rule::in(array_keys(Order::statusLabels())),
            ],
            'remaining_payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', Rule::in(Order::availablePaymentMethods())],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $status = (string) $this->input('status', '');

            $order = $this->route('order');
            $remainingDue = $order instanceof Order ? $order->dueAmount() : 0.0;

            if ($status === Order::STATUS_DELIVERED && $remainingDue > 0.0001 && empty($this->input('payment_method'))) {
                $validator->errors()->add('payment_method', 'Payment method is required when delivering the order.');
            }
        });
    }
}
