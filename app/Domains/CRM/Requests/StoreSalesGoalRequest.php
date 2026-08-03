<?php

namespace App\Domains\CRM\Requests;

use App\Domains\CRM\Enums\GoalPeriod;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalesGoalRequest extends FormRequest
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
        $companyId = app(TenantContext::class)->id();

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'period_type' => ['required', Rule::in(array_keys(GoalPeriod::options()))],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'target_amount' => ['nullable', 'numeric', 'min:0'],
            'target_count' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
