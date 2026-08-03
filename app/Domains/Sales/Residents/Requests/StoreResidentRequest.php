<?php

namespace App\Domains\Sales\Residents\Requests;

use App\Domains\Sales\Residents\Enums\ResidentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'document' => ['nullable', 'string', 'max:32'],
            'is_primary_contact' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::enum(ResidentStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_primary_contact' => $this->boolean('is_primary_contact'),
        ]);
    }
}
