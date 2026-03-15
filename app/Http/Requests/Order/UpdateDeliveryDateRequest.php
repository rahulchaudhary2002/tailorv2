<?php

namespace App\Http\Requests\Order;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryDateRequest extends FormRequest
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
        $orderedAt = $order?->ordered_at?->format('Y-m-d H:i:s');

        return [
            'delivery_due_at' => ['required', 'date', $orderedAt ? 'after_or_equal:' . $orderedAt : 'date'],
        ];
    }
}
