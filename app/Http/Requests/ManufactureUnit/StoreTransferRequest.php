<?php

namespace App\Http\Requests\ManufactureUnit;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTransferRequest extends FormRequest
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
            'quantity' => ['required', 'numeric', 'gt:0'],
            'target_product_id' => ['required', 'integer', 'exists:products,id'],
            'target_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $productId = (int) $this->input('target_product_id', 0);
            $variantId = (int) $this->input('target_variant_id', 0);

            if ($productId < 1) {
                return;
            }

            $productHasVariants = ProductVariant::query()
                ->where('product_id', $productId)
                ->exists();

            if ($variantId < 1 && $productHasVariants) {
                $validator->errors()->add('target_variant_id', 'Variant is required for products that have variants.');
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
                $validator->errors()->add('target_variant_id', 'Selected target variant does not belong to selected target product.');
            }
        });
    }
}
