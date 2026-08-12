<?php

namespace App\Domains\Mobile\Requests;

use App\Domains\Mobile\Support\MobileAuthResponse;
use App\Domains\Visits\Requests\CompleteFollowUpRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MobileCompleteFollowUpRequest extends CompleteFollowUpRequest
{
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
