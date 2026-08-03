<?php

namespace App\Domains\CRM\Policies;

use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\Opportunity;

class OpportunityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('crm.view');
    }

    public function view(User $user, Opportunity $opportunity): bool
    {
        return $user->company_id === $opportunity->company_id
            && $user->hasPermission('crm.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('crm.manage');
    }

    public function update(User $user, Opportunity $opportunity): bool
    {
        return $user->company_id === $opportunity->company_id
            && $user->hasPermission('crm.manage');
    }

    public function move(User $user, Opportunity $opportunity): bool
    {
        return $this->update($user, $opportunity);
    }

    public function win(User $user, Opportunity $opportunity): bool
    {
        return $this->update($user, $opportunity);
    }

    public function lose(User $user, Opportunity $opportunity): bool
    {
        return $this->update($user, $opportunity);
    }
}
