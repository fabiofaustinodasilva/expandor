<?php

namespace App\Domains\Maps\Requests;

use App\Domains\Maps\Enums\MapCommercialGroup;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MapMarkersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $groups = array_column(MapCommercialGroup::cases(), 'value');

        return [
            'city_id' => ['nullable', 'integer', 'min:1'],
            'sector_id' => ['nullable', 'integer', 'min:1'],
            'property_status' => ['nullable', 'string', Rule::enum(PropertyStatus::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'min_latitude' => ['nullable', 'numeric', 'required_with:max_latitude,min_longitude,max_longitude'],
            'max_latitude' => ['nullable', 'numeric', 'required_with:min_latitude,min_longitude,max_longitude'],
            'min_longitude' => ['nullable', 'numeric', 'required_with:min_latitude,max_latitude,max_longitude'],
            'max_longitude' => ['nullable', 'numeric', 'required_with:min_latitude,max_latitude,min_longitude'],
            'commercial_groups' => ['nullable'],
            'commercial_groups.*' => ['string', Rule::in($groups)],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'campaign_id' => ['nullable', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $groups = $this->input('commercial_groups');
        if (is_string($groups) && $groups !== '') {
            $this->merge([
                'commercial_groups' => array_values(array_filter(array_map('trim', explode(',', $groups)))),
            ]);
        }
    }
}
