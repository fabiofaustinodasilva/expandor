<?php

namespace App\Domains\Company\Policies;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;

class CompanyPolicy
{
    public function view(User $user, Company $company): bool
    {
        return $user->company_id === $company->id
            && $user->hasPermission('company.manage');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->company_id === $company->id
            && $user->hasPermission('company.manage');
    }
}
