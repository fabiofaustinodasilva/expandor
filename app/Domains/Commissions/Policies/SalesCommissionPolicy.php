<?php

namespace App\Domains\Commissions\Policies;

use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Company\Models\User;

class SalesCommissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('commissions.manage')
            || $user->hasPermission('commissions.view_self');
    }

    public function view(User $user, SalesCommission $commission): bool
    {
        if ((int) $user->company_id !== (int) $commission->company_id) {
            return false;
        }

        if ($user->hasPermission('commissions.manage')) {
            return true;
        }

        return $user->hasPermission('commissions.view_self')
            && (int) $user->id === (int) $commission->user_id;
    }

    public function manage(User $user): bool
    {
        return $user->hasPermission('commissions.manage');
    }

    public function approve(User $user, SalesCommission $commission): bool
    {
        return $this->manage($user)
            && (int) $user->company_id === (int) $commission->company_id;
    }

    public function markPaid(User $user, SalesCommission $commission): bool
    {
        return $this->approve($user, $commission);
    }
}
