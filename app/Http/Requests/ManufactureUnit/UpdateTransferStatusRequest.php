<?php

namespace App\Http\Requests\ManufactureUnit;

use App\Models\InventoryTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransferStatusRequest extends FormRequest
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
            'status' => [
                'required',
                Rule::in([
                    InventoryTransaction::STATUS_PENDING,
                    InventoryTransaction::STATUS_PROGRESS,
                    InventoryTransaction::STATUS_COMPLETED,
                ]),
            ],
        ];
    }
}
