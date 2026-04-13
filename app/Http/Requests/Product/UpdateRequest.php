<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
        /** @var Product|null $product */
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:80',
                'alpha_dash',
                Rule::unique('products', 'code')->whereNull('deleted_at')->ignore($product),
            ],
            'product_category_id' => [
                'required',
                'integer',
                Rule::exists('product_categories', 'id')->where(function ($query) {
                    $query->whereIn('slug', ProductCategory::PRODUCT_CREATABLE_SLUGS);
                }),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
