<?php

namespace App\Http\Requests\ManufactureUnit;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkflowRequest extends FormRequest
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
            'transfer_transaction_id' => ['required', 'integer', 'exists:inventory_transactions,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'material_wastage_qty' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'special_price' => ['nullable', 'numeric', 'min:0', 'lte:base_price'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
