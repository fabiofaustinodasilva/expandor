<?php

namespace App\Domains\Sales\Properties\Policies;

use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Models\Address;

class AddressPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('properties.view');
    }

    public function view(User $user, Address $address): bool
    {
        return $user->company_id === $address->company_id
            && $user->hasPermission('properties.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('properties.manage');
    }

    public function update(User $user, Address $address): bool
    {
        return $user->company_id === $address->company_id
            && $user->hasPermission('properties.manage');
    }
}
