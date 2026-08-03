<?php

namespace App\Domains\Visits\Policies;

use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Visits\Models\FollowUp;

class FollowUpPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('visits.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('visits.manage');
    }

    public function complete(User $user, FollowUp $followUp): bool
    {
        if ($user->company_id !== $followUp->company_id
            || ! $user->hasPermission('visits.manage')) {
            return false;
        }

        $slug = $user->role?->slug;
        if ($slug === Role::SELLER) {
            return (int) $followUp->user_id === (int) $user->id;
        }

        return true;
    }
}
