<?php

namespace App\Http\Requests\Inventory;

use App\Models\InventoryTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
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
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'adjustment_type' => ['nullable', 'in:add,remove,set'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'set_reorder_level' => ['nullable', 'boolean'],
            'reorder_min_qty' => ['nullable', 'numeric', 'gt:0', 'required_if:set_reorder_level,1'],
            'reorder_qty' => ['nullable', 'numeric', 'gt:0', 'gte:reorder_min_qty'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
