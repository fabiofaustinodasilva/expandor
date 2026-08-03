<?php

namespace App\Domains\Visits\Policies;

use App\Domains\Company\Models\User;
use App\Domains\Visits\Models\Visit;

class VisitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('visits.view');
    }

    public function view(User $user, Visit $visit): bool
    {
        return $user->company_id === $visit->company_id
            && $user->hasPermission('visits.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('visits.manage');
    }

    public function manageFollowUps(User $user): bool
    {
        return $user->hasPermission('visits.manage');
    }
}
