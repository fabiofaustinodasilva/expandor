<?php

namespace App\Domains\Visits\Actions;

use App\Domains\Company\Models\User;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Services\VisitService;

class ScheduleFollowUpAction
{
    public function __construct(
        protected VisitService $visits
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Visit $visit, array $data, ?User $actor = null): FollowUp
    {
        return $this->visits->scheduleFollowUp($visit, $data, $actor);
    }
}
