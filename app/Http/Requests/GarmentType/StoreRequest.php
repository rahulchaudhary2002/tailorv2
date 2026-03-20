<?php

namespace App\Http\Requests\GarmentType;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $designNotes = collect($this->input('design_note', []))
            ->map(fn ($note) => trim((string) $note))
            ->filter()
            ->values()
            ->all();

        $this->merge([
            'title' => trim((string) $this->input('title')),
            'design_note' => $designNotes === [] ? null : $designNotes,
        ]);
    }

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
            'design_note' => ['nullable', 'array'],
            'design_note.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
