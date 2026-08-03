<?php

namespace App\Domains\CRM\Policies;

use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\Lead;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('crm.view');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->company_id === $lead->company_id
            && $user->hasPermission('crm.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('crm.manage');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->company_id === $lead->company_id
            && $user->hasPermission('crm.manage');
    }

    public function convert(User $user, Lead $lead): bool
    {
        return $this->update($user, $lead);
    }
}
