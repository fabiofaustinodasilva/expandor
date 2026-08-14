<?php

namespace App\Domains\Mobile\Requests;

use App\Domains\Mobile\Requests\Concerns\RequiresDueDayOnCompleteSale;
use App\Domains\Visits\Requests\StoreVisitRequest;
use App\Tenancy\TenantContext;
use Illuminate\Validation\Rule;

class MobilePointVisitRequest extends StoreVisitRequest
{
    use RequiresDueDayOnCompleteSale;

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $point = $this->route('point');
        if ($point !== null) {
            $this->merge(['property_id' => (int) $point]);
        }
    }

    public function rules(): array
    {
        $companyId = app(TenantContext::class)->id();

        return array_merge(parent::rules(), [
            'campaign_id' => [
                'nullable',
                'integer',
                Rule::exists('campaigns', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
        ], $this->completeSaleExtraRules());
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), $this->completeSaleExtraMessages());
    }
}
