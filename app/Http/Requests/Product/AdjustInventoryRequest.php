<?php

namespace App\Http\Requests\Product;

use App\Models\InventoryTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustInventoryRequest extends FormRequest
{
    protected $errorBag = 'inventoryTransaction';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trx_type' => [
                'required',
                Rule::in([
                    InventoryTransaction::TYPE_IN,
                    InventoryTransaction::TYPE_OUT,
                    InventoryTransaction::TYPE_TRANSFER,
                    InventoryTransaction::TYPE_ADJUSTMENT,
                ]),
            ],
            'location_id' => ['nullable', 'integer', 'exists:inventory_locations,id'],
            'from_location_id' => ['nullable', 'integer', 'exists:inventory_locations,id'],
            'to_location_id' => ['nullable', 'integer', 'exists:inventory_locations,id'],
            'adjustment_type' => ['nullable', 'in:add,remove,set'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        $product = $this->route('product');

        return route('product.edit', $product) . '#inventory';
    }
}
