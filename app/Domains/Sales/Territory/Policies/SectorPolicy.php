<?php

namespace App\Domains\Sales\Territory\Policies;

use App\Domains\Company\Models\User;
use App\Domains\Sales\Territory\Models\Sector;

class SectorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('sectors.view');
    }

    public function view(User $user, Sector $sector): bool
    {
        return $user->company_id === $sector->company_id
            && $user->hasPermission('sectors.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('sectors.manage');
    }

    public function update(User $user, Sector $sector): bool
    {
        return $user->company_id === $sector->company_id
            && $user->hasPermission('sectors.manage');
    }

    public function toggleStatus(User $user, Sector $sector): bool
    {
        return $this->update($user, $sector);
    }
}
