<?php

namespace App\Domains\Onboarding\Requests;

use App\Domains\Onboarding\Services\SaasOnboardingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveOnboardingDealRequest extends FormRequest
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
        $statuses = array_keys(app(SaasOnboardingService::class)->dealStatusOptions());

        return [
            'lead_id' => ['nullable', 'integer'],
            'customer_name' => ['required', 'string', 'max:180'],
            'customer_email' => ['nullable', 'email', 'max:180'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'product_name' => ['required', 'string', 'max:180'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in($statuses)],
            'title' => ['nullable', 'string', 'max:180'],
        ];
    }
}
