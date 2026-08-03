<?php

namespace App\Domains\Security\Requests;

use App\Domains\Security\Enums\ConsentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resident_id' => ['required', 'integer', 'exists:residents,id'],
            'consent_type' => ['required', 'string', Rule::enum(ConsentType::class)],
            'granted' => ['required', 'boolean'],
            'source' => ['nullable', 'string', 'max:50'],
        ];
    }
}
