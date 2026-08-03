<?php

namespace App\Domains\Payments\Policies;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;

class SubscriptionPolicy
{
    public function view(User $user, ?Company $company = null): bool
    {
        if ($user->isPlatformAdmin()) {
            return false;
        }

        if (! $user->hasPermission('billing.view') && ! $user->hasPermission('billing.manage')) {
            return false;
        }

        return $company === null || $user->company_id === $company->id;
    }

    public function manage(User $user, ?Company $company = null): bool
    {
        if ($user->isPlatformAdmin()) {
            return false;
        }

        if (! $user->hasPermission('billing.manage')) {
            return false;
        }

        return $company === null || $user->company_id === $company->id;
    }
}
