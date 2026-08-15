<?php

namespace App\Domains\Visits\Actions;

use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Visits\Services\VisitService;

class CancelPendingFollowUpsForPropertyAction
{
    public function __construct(
        protected VisitService $visits,
    ) {}

    public function execute(Property $property): int
    {
        return $this->visits->cancelPendingFollowUpsForProperty($property);
    }
}
