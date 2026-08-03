<?php

namespace App\Domains\Platform\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToggleFeatureFlagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.manageFeatureFlags') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:100'],
            'enabled' => ['required', 'boolean'],
        ];
    }
}
