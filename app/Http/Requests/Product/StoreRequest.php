<?php

namespace App\Http\Requests\Product;

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
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:80', 'alpha_dash', 'unique:products,code'],
            'product_category_id' => [
                'required',
                'integer',
                Rule::exists('product_categories', 'id')->where(function ($query) {
                    $query->whereIn('slug', ProductCategory::PRODUCT_CREATABLE_SLUGS);
                }),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'inventory_location_id' => ['nullable', 'integer', 'exists:inventory_locations,id'],
            'opening_quantity' => ['nullable', 'numeric', 'gt:0'],
            'opening_unit_cost' => ['nullable', 'numeric', 'min:0'],
            'opening_notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
