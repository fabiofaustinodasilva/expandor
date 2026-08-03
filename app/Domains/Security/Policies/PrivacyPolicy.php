<?php

namespace App\Domains\Security\Policies;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;

class PrivacyPolicy
{
    public function view(User $user, ?Company $company = null): bool
    {
        if (! $user->hasPermission('privacy.view')) {
            return false;
        }

        return $company === null || $user->company_id === $company->id;
    }

    public function manage(User $user, ?Company $company = null): bool
    {
        if (! $user->hasPermission('privacy.manage')) {
            return false;
        }

        return $company === null || $user->company_id === $company->id;
    }
}
