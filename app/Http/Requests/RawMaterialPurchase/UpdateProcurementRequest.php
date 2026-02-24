<?php

namespace App\Http\Requests\RawMaterialPurchase;

use App\Models\InventoryLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProcurementRequest extends FormRequest
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
            'vendor_bill_number' => ['nullable', 'string', 'max:120'],
            'vendor_bill_amount' => ['nullable', 'numeric', 'min:0'],
            'bill_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'update_inventory' => ['nullable', 'boolean'],
            'inventory_location_id' => [
                'nullable',
                'integer',
                'required_with:update_inventory',
                Rule::exists('inventory_locations', 'id')->where(function ($query) {
                    $query
                        ->where('is_active', true)
                        ->where('type', InventoryLocation::TYPE_WAREHOUSE);
                }),
            ],
            'inventory_base_price' => ['nullable', 'numeric', 'min:0', 'required_if:update_inventory,1'],
            'inventory_special_price' => ['nullable', 'numeric', 'min:0', 'lte:inventory_base_price'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
