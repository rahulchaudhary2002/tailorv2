<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Rules\ProductMediaFileRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $variants = collect($this->input('variants', []))
            ->filter(function ($row) {
                if (!is_array($row)) {
                    return false;
                }

                $sku = trim((string) ($row['sku'] ?? ''));
                $size = trim((string) ($row['size'] ?? ''));
                $color = trim((string) ($row['color'] ?? ''));
                $material = trim((string) ($row['material'] ?? ''));

                return $sku !== '' || $size !== '' || $color !== '' || $material !== '';
            })
            ->values()
            ->all();

        $this->merge(['variants' => $variants]);
    }

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
        /** @var Product|null $product */
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:150'],
            'sku' => [
                'required',
                'string',
                'max:80',
                'alpha_dash',
                Rule::unique('products', 'sku')->ignore($product),
                Rule::unique('product_variants', 'sku'),
            ],
            'product_category_id' => [
                'required',
                'integer',
                Rule::exists('product_categories', 'id')->where(function ($query) {
                    $query->whereIn('slug', ProductCategory::PRODUCT_CREATABLE_SLUGS);
                }),
            ],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'variants' => ['nullable', 'array'],
            'variants.*.sku' => [
                'required',
                'string',
                'max:80',
                'alpha_dash',
                'distinct',
                Rule::unique('products', 'sku')->ignore($product),
                Rule::unique('product_variants', 'sku')->where(function ($query) use ($product) {
                    $query->where('product_id', '!=', $product?->id ?? 0);
                }),
                'different:sku',
            ],
            'variants.*.size' => ['nullable', 'string', 'max:50'],
            'variants.*.color' => ['nullable', 'string', 'max:50'],
            'variants.*.material' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'media_files' => ['nullable', 'array'],
            'media_files.*' => [
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/x-msvideo,video/webm,video/x-matroska',
                'max:102400',
                new ProductMediaFileRule(),
            ],
            'remove_media_ids' => ['nullable', 'array'],
            'remove_media_ids.*' => [
                'integer',
                Rule::exists('product_media', 'id')->where(function ($query) use ($product) {
                    $query->where('product_id', $product?->id ?? 0);
                }),
            ],
        ];
    }
}
