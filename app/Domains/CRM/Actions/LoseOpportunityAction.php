<?php

namespace App\Domains\CRM\Actions;

use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\CRM\Services\OpportunityService;

class LoseOpportunityAction
{
    public function __construct(protected OpportunityService $opportunities) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Opportunity $opportunity, array $data = [], ?User $actor = null): Opportunity
    {
        return $this->opportunities->lose($opportunity, $data, $actor);
    }
}
