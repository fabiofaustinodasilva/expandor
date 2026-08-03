<?php

namespace App\Domains\CRM\Actions;

use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\CRM\Services\OpportunityService;

class WinOpportunityAction
{
    public function __construct(protected OpportunityService $opportunities) {}

    public function execute(Opportunity $opportunity, ?User $actor = null): Opportunity
    {
        return $this->opportunities->win($opportunity, $actor);
    }
}
