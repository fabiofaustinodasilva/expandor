<?php

namespace App\Domains\Billing\Policies;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;

class BillingPolicy
{
    public function view(User $user, ?Company $company = null): bool
    {
        if (! $user->hasPermission('billing.view')) {
            return false;
        }

        if ($company === null) {
            return true;
        }

        return $user->company_id === $company->id;
    }

    public function manage(User $user, ?Company $company = null): bool
    {
        if (! $user->hasPermission('billing.manage')) {
            return false;
        }

        if ($company === null) {
            return true;
        }

        return $user->company_id === $company->id;
    }
}
