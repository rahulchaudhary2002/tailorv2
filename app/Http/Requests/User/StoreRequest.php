<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

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
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_super_admin' => ['nullable', 'boolean'],
            'outlet_ids' => ['required', 'array', 'min:1'],
            'outlet_ids.*' => ['integer', 'exists:outlets,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->boolean('is_super_admin') && !optional($this->user())->is_super_admin) {
                $validator->errors()->add('is_super_admin', 'Only super admin can create another super admin.');
            }
        });
    }
}
