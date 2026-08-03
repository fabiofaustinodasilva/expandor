<?php

namespace App\Domains\Platform\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RenewTrialRequest extends FormRequest
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
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'days' => 'dias de trial',
        ];
    }
}
