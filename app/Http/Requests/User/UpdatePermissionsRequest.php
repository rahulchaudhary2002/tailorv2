<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionsRequest extends FormRequest
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
            'assignment_outlet_id' => ['required', 'integer', Rule::exists('outlets', 'id')->whereNull('deleted_at')],
            'permission_overrides' => ['nullable', 'array'],
            'permission_overrides.*' => ['nullable', 'in:allow,deny'],
        ];
    }
}
