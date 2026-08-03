<?php

namespace App\Domains\Onboarding\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveWizardStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('onboarding.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'step' => ['required', 'string', 'max:80'],
            'name' => ['nullable', 'string', 'max:190'],
            'legal_name' => ['nullable', 'string', 'max:190'],
            'document' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'system_name' => ['nullable', 'string', 'max:120'],
            'display_name' => ['nullable', 'string', 'max:120'],
            'theme' => ['nullable', 'string', 'max:30'],
            'support_email' => ['nullable', 'email'],
            'support_phone' => ['nullable', 'string', 'max:40'],
            'colors' => ['nullable', 'array'],
            'fonts' => ['nullable', 'array'],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['nullable', 'string', 'max:40'],
            'state' => ['nullable', 'string', 'size:2'],
            'city_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
