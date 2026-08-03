<?php

namespace App\Domains\CRM\Actions;

use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\Lead;
use App\Domains\CRM\Services\LeadService;

class ConvertLeadAction
{
    public function __construct(protected LeadService $leads) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Lead $lead, array $data = [], ?User $actor = null): Lead
    {
        return $this->leads->convert($lead, $data, $actor);
    }
}
