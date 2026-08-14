<?php

namespace App\Domains\Mobile\Requests;

use App\Domains\Mobile\Requests\Concerns\RequiresDueDayOnCompleteSale;
use App\Domains\Visits\Requests\CompleteFollowUpRequest;

class MobileCompleteFollowUpRequest extends CompleteFollowUpRequest
{
    use RequiresDueDayOnCompleteSale;

    public function rules(): array
    {
        return array_merge(parent::rules(), $this->completeSaleExtraRules());
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), $this->completeSaleExtraMessages());
    }
}
