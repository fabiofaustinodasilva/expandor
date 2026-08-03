<?php

namespace App\Domains\Sales\Properties\Policies;

use App\Domains\Company\Models\User;
use App\Domains\Company\Support\FieldOps\FieldOpsPolicyResolver;
use App\Domains\Sales\Properties\Models\Property;

class PropertyPolicy
{
    public function __construct(
        protected FieldOpsPolicyResolver $fieldOps,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('properties.view');
    }

    public function view(User $user, Property $property): bool
    {
        return $user->company_id === $property->company_id
            && $user->hasPermission('properties.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('properties.manage')
            || $user->hasPermission('properties.create');
    }

    public function update(User $user, Property $property): bool
    {
        $policy = $this->fieldOps->resolveForUser($user);

        return $this->fieldOps->canEditProperty($user, $property, $policy);
    }

    public function delete(User $user, Property $property): bool
    {
        $policy = $this->fieldOps->resolveForUser($user);

        return $this->fieldOps->canDeleteProperty($user, $property, $policy);
    }

    public function changeStatus(User $user, Property $property): bool
    {
        return $this->update($user, $property);
    }
}
