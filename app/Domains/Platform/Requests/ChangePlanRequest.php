<?php

namespace App\Domains\Platform\Requests;

use App\Domains\Company\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.manageCompanies') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')->where('status', Plan::STATUS_ACTIVE),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'plan_id' => 'plano',
        ];
    }
}
