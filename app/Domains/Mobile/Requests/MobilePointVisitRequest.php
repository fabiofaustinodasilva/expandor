<?php

namespace App\Domains\Mobile\Requests;

use App\Domains\Mobile\Support\MobileAuthResponse;
use App\Domains\Visits\Requests\StoreVisitRequest;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class MobilePointVisitRequest extends StoreVisitRequest
{
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
        ]);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            MobileAuthResponse::error(
                'Verifique os dados informados.',
                'validation_error',
                422,
                $validator->errors()->toArray(),
            )
        );
    }

    protected function requiresDueDayOnSale(): bool
    {
        return false;
    }
}
