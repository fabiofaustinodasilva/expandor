<?php

namespace App\Domains\Visits\Actions;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\User;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Services\VisitService;

class RegisterVisitAction
{
    public function __construct(
        protected VisitService $visits
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Campaign $campaign, array $data, ?User $actor = null): Visit
    {
        return $this->visits->register($campaign, $data, $actor);
    }
}
