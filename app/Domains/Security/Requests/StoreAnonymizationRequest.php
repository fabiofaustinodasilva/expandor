<?php

namespace App\Domains\Security\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnonymizationRequest extends FormRequest
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
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
