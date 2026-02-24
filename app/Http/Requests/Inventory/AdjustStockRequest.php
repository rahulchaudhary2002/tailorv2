<?php

namespace App\Http\Requests\Inventory;

use App\Models\InventoryTransaction;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'adjustment_type' => ['nullable', 'in:add,remove,set'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'special_price' => ['nullable', 'numeric', 'min:0', 'lte:base_price'],
            'set_reorder_level' => ['nullable', 'boolean'],
            'reorder_min_qty' => ['nullable', 'numeric', 'gt:0', 'required_if:set_reorder_level,1'],
            'reorder_qty' => ['nullable', 'numeric', 'gt:0', 'gte:reorder_min_qty'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $productId = (int) $this->input('product_id', 0);
            $variantId = (int) $this->input('product_variant_id', 0);

            if ($productId < 1) {
                return;
            }

            $productHasVariants = ProductVariant::query()
                ->where('product_id', $productId)
                ->exists();

            if ($variantId < 1 && $productHasVariants) {
                $validator->errors()->add('product_variant_id', 'Variant is required for products that have variants.');
                return;
            }

            if ($variantId < 1) {
                return;
            }

            $belongsToProduct = ProductVariant::query()
                ->whereKey($variantId)
                ->where('product_id', $productId)
                ->exists();

            if (!$belongsToProduct) {
                $validator->errors()->add('product_variant_id', 'Selected variant does not belong to the selected product.');
            }
        });
    }
}
