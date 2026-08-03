<?php

namespace App\Domains\Sales\Properties\Actions;

use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Properties\Services\PropertyService;

class ChangePropertyStatusAction
{
    public function __construct(
        protected PropertyService $properties
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Property $property, array $data, User $user): Property
    {
        return $this->properties->changeStatus($property, $data, $user);
    }
}
