<?php

namespace App\Http\Requests\Operations;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Company\Support\CommercialProfileCatalog;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    public function rules(): array
    {
        $companyId = app(TenantContext::class)->id();
        $commercialRoleIds = Role::query()
            ->whereIn('slug', CommercialProfileCatalog::commercialRoleSlugs())
            ->pluck('id')
            ->all();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', Rule::in($commercialRoleIds)],
            'campaign_id' => [
                'nullable',
                Rule::exists('campaigns', 'id')->where(
                    fn ($q) => $q->where('company_id', $companyId)
                        ->where('status', CampaignStatus::ACTIVE->value)
                ),
            ],
            'status' => ['nullable', Rule::in([User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_BLOCKED])],
        ];
    }
}
