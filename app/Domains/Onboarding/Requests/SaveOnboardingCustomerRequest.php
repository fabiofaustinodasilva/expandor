<?php

namespace App\Domains\Onboarding\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveOnboardingCustomerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:180'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'size:2'],
        ];
    }
}
