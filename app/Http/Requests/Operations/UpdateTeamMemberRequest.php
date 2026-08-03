<?php

namespace App\Http\Requests\Operations;

use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Company\Support\CommercialProfileCatalog;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $member */
        $member = $this->route('user');

        return $this->user()?->can('update', $member) ?? false;
    }

    public function rules(): array
    {
        $companyId = app(TenantContext::class)->id();
        /** @var User $member */
        $member = $this->route('user');
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
                Rule::unique('users', 'email')
                    ->where(fn ($q) => $q->where('company_id', $companyId))
                    ->ignore($member->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8'],
            'role_id' => ['required', Rule::in($commercialRoleIds)],
            'campaign_id' => [
                'nullable',
                Rule::exists('campaigns', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'status' => ['required', Rule::in([User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_BLOCKED])],
        ];
    }
}
