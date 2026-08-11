<?php

namespace App\Domains\Customers\Policies;

use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Models\Property;

/**
 * Autorização do CRM Clientes (visão comercial de Property).
 */
class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('customers.view');
    }

    public function view(User $user, Property $property): bool
    {
        if (! $user->hasPermission('customers.view')) {
            return false;
        }

        if ((int) $user->company_id !== (int) $property->company_id) {
            return false;
        }

        if ($user->role?->slug !== Role::SELLER) {
            return true;
        }

        return $this->sellerOwns($user, $property);
    }

    public function manage(User $user): bool
    {
        return $user->hasPermission('customers.manage');
    }

    public function delete(User $user, Property $property): bool
    {
        if (! $this->manage($user)) {
            return false;
        }

        if ((int) $user->company_id !== (int) $property->company_id) {
            return false;
        }

        return $user->role?->slug !== Role::SELLER;
    }

    public function sellerOwns(User $user, Property $property): bool
    {
        if ((int) $property->created_by === (int) $user->id) {
            return true;
        }

        return $property->visits()
            ->where('user_id', $user->id)
            ->exists();
    }
}
