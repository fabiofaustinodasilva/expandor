<?php

namespace App\Domains\Visits\Actions;

use App\Domains\Company\Models\User;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Services\VisitService;

class CompleteFollowUpAction
{
    public function __construct(
        protected VisitService $visits
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{visit: Visit, follow_up: FollowUp, next_follow_up: ?FollowUp}
     */
    public function execute(FollowUp $followUp, array $data, User $actor): array
    {
        return $this->visits->completeFollowUpWithOutcome($followUp, $data, $actor);
    }
}
