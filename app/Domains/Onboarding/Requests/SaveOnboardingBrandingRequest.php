<?php

namespace App\Domains\Onboarding\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveOnboardingBrandingRequest extends FormRequest
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
            'display_name' => ['required', 'string', 'max:120'],
            'slogan' => ['nullable', 'string', 'max:180'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'highlight_color' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
