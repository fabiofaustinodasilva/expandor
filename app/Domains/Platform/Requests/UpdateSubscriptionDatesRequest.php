<?php

namespace App\Domains\Platform\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionDatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.manageCompanies') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ends_at' => ['nullable', 'date'],
            'next_billing_at' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'ends_at' => 'data de término',
            'next_billing_at' => 'próxima cobrança',
            'trial_ends_at' => 'fim do trial',
        ];
    }
}
