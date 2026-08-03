<?php

namespace App\Domains\Company\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Tenancy\TenantContext;

class DashboardService
{
    public function __construct(
        protected TenantContext $tenant
    ) {}

    /**
     * @return array{
     *     company: Company,
     *     plan_name: string,
     *     subscription_status: string,
     *     users_count: int
     * }
     */
    public function summary(): array
    {
        /** @var Company $company */
        $company = $this->tenant->company()
            ?? Company::query()->findOrFail($this->tenant->id());

        $subscription = Subscription::query()
            ->with('plan')
            ->where('company_id', $company->id)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
            ->latest('id')
            ->first();

        return [
            'company' => $company,
            'plan_name' => $subscription?->plan?->name ?? 'Sem plano',
            'subscription_status' => $subscription?->status ?? 'none',
            'users_count' => User::query()->where('company_id', $company->id)->count(),
        ];
    }
}
