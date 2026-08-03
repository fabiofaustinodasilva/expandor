<?php

namespace App\Domains\CRM\Policies;

use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\CommissionRule;

class CommissionRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('crm.view');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermission('crm.manage');
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, CommissionRule $rule): bool
    {
        return $user->company_id === $rule->company_id
            && $user->hasPermission('crm.manage');
    }
}
