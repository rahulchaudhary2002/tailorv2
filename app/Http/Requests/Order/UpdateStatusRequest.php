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

        return [
            'status' => [
                'required',
                Rule::in([
                    ...$nextStatuses,
                ]),
            ],
            'remaining_payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'worker_id' => ['nullable', 'integer', 'exists:users,id'],
            'worker_deadline_at' => ['nullable', 'date'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $status = (string) $this->input('status', '');

            if ($status === Order::STATUS_ASSIGNED) {
                if ((int) $this->input('worker_id', 0) < 1) {
                    $validator->errors()->add('worker_id', 'Worker is required when assigning the order.');
                }

                if (empty($this->input('worker_deadline_at'))) {
                    $validator->errors()->add('worker_deadline_at', 'Worker deadline is required when assigning the order.');
                }
            }

            if ($status === Order::STATUS_DELIVERED && empty($this->input('payment_method'))) {
                $validator->errors()->add('payment_method', 'Payment method is required when delivering the order.');
            }
        });
    }
}
