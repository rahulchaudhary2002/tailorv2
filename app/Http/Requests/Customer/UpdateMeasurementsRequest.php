<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeasurementsRequest extends FormRequest
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
            'garment_type_ids' => ['nullable', 'array'],
            'garment_type_ids.*' => ['integer', 'distinct', Rule::exists('garment_types', 'id')->whereNull('deleted_at')],
            'measurements' => ['nullable', 'array'],
            'measurements.*.garment_type_id' => ['required', 'integer', Rule::exists('garment_types', 'id')->whereNull('deleted_at')],
            'measurements.*.type' => ['required', 'string', 'max:100'],
            'measurements.*.measurement' => ['required', 'string', 'max:50'],
            'measurements.*.unit' => ['required', 'string', 'max:20'],
            'active_tab' => ['nullable', 'in:details,measurements'],
        ];
    }
}
