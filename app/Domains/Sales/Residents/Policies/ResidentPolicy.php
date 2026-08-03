<?php

namespace App\Domains\Sales\Residents\Policies;

use App\Domains\Company\Models\User;
use App\Domains\Sales\Residents\Models\Resident;

class ResidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('residents.view');
    }

    public function view(User $user, Resident $resident): bool
    {
        return $user->company_id === $resident->company_id
            && $user->hasPermission('residents.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('residents.manage');
    }

    public function update(User $user, Resident $resident): bool
    {
        return $user->company_id === $resident->company_id
            && $user->hasPermission('residents.manage');
    }

    public function changeStatus(User $user, Resident $resident): bool
    {
        return $this->update($user, $resident);
    }
}
