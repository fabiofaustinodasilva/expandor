<?php

namespace App\Domains\CRM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoseOpportunityRequest extends FormRequest
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
            'lost_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
