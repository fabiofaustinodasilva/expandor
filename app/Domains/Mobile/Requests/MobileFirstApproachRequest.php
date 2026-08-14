<?php

namespace App\Domains\Mobile\Requests;

use App\Domains\Mobile\Requests\Concerns\RequiresDueDayOnCompleteSale;
use App\Domains\Visits\Requests\StoreFirstApproachRequest;

class MobileFirstApproachRequest extends StoreFirstApproachRequest
{
    use RequiresDueDayOnCompleteSale;

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['sector_name'] = ['nullable', 'string', 'max:255'];
        $rules['neighborhood'] = ['nullable', 'string', 'max:255'];
        $rules['sector_id'] = array_merge(['nullable'], array_slice($rules['sector_id'] ?? [], 1));

        return array_merge($rules, $this->completeSaleExtraRules());
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), $this->completeSaleExtraMessages());
    }
}
