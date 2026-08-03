<?php

namespace App\Domains\CRM\Actions;

use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\CommissionEntry;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\CRM\Services\CommissionService;

class CalculateCommissionAction
{
    public function __construct(protected CommissionService $commissions) {}

    public function execute(Opportunity $opportunity, ?User $actor = null): ?CommissionEntry
    {
        return $this->commissions->prepareForWonOpportunity($opportunity, $actor);
    }
}
