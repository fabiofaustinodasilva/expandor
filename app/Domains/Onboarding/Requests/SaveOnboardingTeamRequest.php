<?php

namespace App\Domains\Onboarding\Requests;

use App\Domains\Onboarding\Services\SaasOnboardingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveOnboardingTeamRequest extends FormRequest
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
        $roles = array_keys(app(SaasOnboardingService::class)->allowedRoles());

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'role' => ['required', 'string', Rule::in($roles)],
            'password' => ['nullable', 'string', 'min:8', 'max:120'],
        ];
    }
}
