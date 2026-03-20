<?php

namespace App\Http\Requests\RawMaterialPurchase;

use App\Models\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'items.*.product_reference' => ['required', 'string', 'max:191'],
            'items.*.product_type' => ['required', 'string', Rule::in(ProductCategory::PRODUCT_CREATABLE_SLUGS)],
            'items.*.product_code' => ['nullable', 'string', 'max:80'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $allowedCategoryIds = ProductCategory::query()
                ->whereIn('slug', ProductCategory::PRODUCT_CREATABLE_SLUGS)
                ->pluck('id');

            foreach ((array) $this->input('items', []) as $index => $item) {
                $reference = trim((string) ($item['product_reference'] ?? ''));
                $productCode = trim((string) ($item['product_code'] ?? ''));

                if (str_starts_with($reference, 'existing:')) {
                    $productId = (int) substr($reference, strlen('existing:'));
                    $productExists = \App\Models\Product::query()
                        ->whereKey($productId)
                        ->whereIn('product_category_id', $allowedCategoryIds)
                        ->exists();

                    if (!$productExists) {
                        $validator->errors()->add("items.{$index}.product_reference", 'Select a valid vendor product.');
                    }

                    continue;
                }

                if (!str_starts_with($reference, 'new:') || trim(substr($reference, strlen('new:'))) === '') {
                    $validator->errors()->add("items.{$index}.product_reference", 'Enter a vendor product name.');
                }

                if ($productCode === '') {
                    $validator->errors()->add("items.{$index}.product_code", 'Product code is required for a new product.');
                    continue;
                }

                if (!preg_match('/^[A-Za-z0-9_-]+$/', $productCode)) {
                    $validator->errors()->add("items.{$index}.product_code", 'Product code may only contain letters, numbers, dashes, and underscores.');
                    continue;
                }

                if (\App\Models\Product::query()->where('code', strtoupper($productCode))->exists()) {
                    $validator->errors()->add("items.{$index}.product_code", 'This product code is already in use.');
                }
            }
        });
    }
}
