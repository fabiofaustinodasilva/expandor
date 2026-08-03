<?php

namespace App\Domains\Platform\Policies;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;

class PlatformPolicy
{
    public function access(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasPermission('platform.access');
    }

    public function manageCompanies(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasPermission('platform.companies.manage');
    }

    public function managePlans(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasPermission('platform.plans.manage');
    }

    public function impersonate(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasPermission('platform.impersonate');
    }

    public function manageFeatureFlags(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasPermission('platform.feature_flags.manage');
    }

    public function viewHealth(User $user): bool
    {
        return $user->isPlatformAdmin()
            && ($user->hasPermission('platform.health.view') || $user->hasPermission('platform.companies.manage'));
    }

    public function manageBranding(User $user): bool
    {
        return $user->isPlatformAdmin()
            && $user->hasPermission('platform.access');
    }

    public function viewCompany(User $user, Company $company): bool
    {
        return $this->manageCompanies($user) && ! $company->is_system;
    }
}
