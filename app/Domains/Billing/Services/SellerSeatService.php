<?php

namespace App\Domains\Billing\Services;

use App\Domains\Billing\Exceptions\SellerLimitExceededException;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Tenancy\TenantContext;

class SellerSeatService
{
    public function __construct(
        protected TenantContext $tenant,
    ) {}

    public function activeSellerCount(?Company $company = null): int
    {
        $company = $company ?? $this->tenant->company();
        if ($company === null) {
            return 0;
        }

        return User::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', User::STATUS_ACTIVE)
            ->whereHas('role', fn ($query) => $query->where('slug', Role::SELLER))
            ->count();
    }

    public function limitFor(?Plan $plan): ?int
    {
        if ($plan === null) {
            return null;
        }

        $value = $plan->getAttributes()['max_sellers'] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    public function roleIsSeller(int $roleId): bool
    {
        $slug = Role::query()->where('id', $roleId)->value('slug');

        return $slug === Role::SELLER;
    }

    public function assertCanOccupySellerSeat(
        Company $company,
        Plan $plan,
        ?User $existing = null,
        bool $willBeActive = true,
    ): void {
        $limit = $this->limitFor($plan);
        if ($limit === null || ! $willBeActive) {
            return;
        }

        $current = $this->activeSellerCount($company);
        if ($existing !== null && $this->countsAsOccupiedSeat($existing)) {
            return;
        }

        if (($current + 1) > $limit) {
            throw new SellerLimitExceededException(
                limit: $limit,
                current: $current,
                planSlug: $plan->slug,
            );
        }
    }

    protected function countsAsOccupiedSeat(User $user): bool
    {
        if ($user->status !== User::STATUS_ACTIVE) {
            return false;
        }

        $slug = $user->role?->slug ?? Role::query()->where('id', $user->role_id)->value('slug');

        return $slug === Role::SELLER;
    }
}
