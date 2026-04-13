<?php

namespace App\Http\Requests\Customer;

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
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('customers', 'email')->whereNull('deleted_at')->ignore($this->route('customer')),
            ],
            'phone' => [
                'required',
                'string',
                'max:30',
                Rule::unique('customers', 'phone')->whereNull('deleted_at')->ignore($this->route('customer')),
            ],
            'customer_type' => ['required', 'in:retail,wholesale,custom'],
            'address' => ['required', 'string', 'max:255'],
            'active_tab' => ['nullable', 'in:details,measurements'],
        ];
    }
}
