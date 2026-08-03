<?php

namespace App\Domains\CRM\Actions;

use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\CRM\Models\PipelineStage;
use App\Domains\CRM\Services\OpportunityService;

class MoveOpportunityStageAction
{
    public function __construct(protected OpportunityService $opportunities) {}

    public function execute(Opportunity $opportunity, PipelineStage $stage, ?User $actor = null): Opportunity
    {
        return $this->opportunities->moveStage($opportunity, $stage, $actor);
    }
}
