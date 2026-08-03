<?php

namespace App\Domains\Sales\Territory\Policies;

use App\Domains\Company\Models\User;
use App\Domains\Sales\Territory\Models\City;

class CityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('cities.view');
    }

    public function view(User $user, City $city): bool
    {
        return $user->company_id === $city->company_id
            && $user->hasPermission('cities.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('cities.manage');
    }

    public function update(User $user, City $city): bool
    {
        return $user->company_id === $city->company_id
            && $user->hasPermission('cities.manage');
    }

    public function toggleStatus(User $user, City $city): bool
    {
        return $this->update($user, $city);
    }
}
