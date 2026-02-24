<?php

namespace App\Http\Requests\RawMaterialPurchase;

use App\Models\ProductCategory;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRequest extends FormRequest
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
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'purchased_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) {
                    $query->whereIn(
                        'product_category_id',
                        ProductCategory::query()->where('slug', 'fabrics')->select('id')
                    );
                }),
            ],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = $this->input('items', []);
            $productIds = collect($items)
                ->pluck('product_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
            /** @var Collection<int, int> $productsWithVariants */
            $productsWithVariants = ProductVariant::query()
                ->whereIn('product_id', $productIds)
                ->distinct()
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id);

            foreach ($items as $index => $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $variantId = (int) ($item['product_variant_id'] ?? 0);

                if ($productId < 1) {
                    continue;
                }

                if ($variantId < 1 && $productsWithVariants->contains($productId)) {
                    $validator->errors()->add("items.{$index}.product_variant_id", 'Variant is required for products that have variants.');
                    continue;
                }

                if ($variantId < 1) {
                    continue;
                }

                $belongsToProduct = ProductVariant::query()
                    ->whereKey($variantId)
                    ->where('product_id', $productId)
                    ->exists();

                if (!$belongsToProduct) {
                    $validator->errors()->add("items.{$index}.product_variant_id", 'Selected variant does not belong to the selected product.');
                }
            }
        });
    }
}
