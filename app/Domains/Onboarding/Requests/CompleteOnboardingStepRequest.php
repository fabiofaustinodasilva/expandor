<?php

namespace App\Domains\Onboarding\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteOnboardingStepRequest extends FormRequest
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
            'metadata' => ['nullable', 'array'],
        ];
    }
}
