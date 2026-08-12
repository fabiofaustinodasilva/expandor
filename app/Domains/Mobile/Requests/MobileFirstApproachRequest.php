<?php

namespace App\Domains\Mobile\Requests;

use App\Domains\Mobile\Support\MobileAuthResponse;
use App\Domains\Visits\Requests\StoreFirstApproachRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MobileFirstApproachRequest extends StoreFirstApproachRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['sector_name'] = ['nullable', 'string', 'max:255'];
        $rules['neighborhood'] = ['nullable', 'string', 'max:255'];
        // sector_id continua opcional; o service resolve a partir de sector_name.
        $rules['sector_id'] = array_merge(['nullable'], array_slice($rules['sector_id'] ?? [], 1));

        return $rules;
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
}
