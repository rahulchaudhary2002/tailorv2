<?php

namespace App\Http\Requests\GarmentTypeMeasurement;

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
        return [
            'title' => ['required', 'string', 'max:100'],
            'unit_id' => ['required', Rule::exists('units', 'id')->whereNull('deleted_at')],
            'order' => ['required', 'integer', 'min:1'],
        ];
    }
}
