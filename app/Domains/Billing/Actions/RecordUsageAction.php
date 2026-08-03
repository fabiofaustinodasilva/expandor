<?php

namespace App\Domains\Billing\Actions;

use App\Domains\Billing\Enums\UsageMetric;
use App\Domains\Billing\Models\UsageRecord;
use App\Domains\Billing\Services\BillingService;
use App\Domains\Company\Models\Company;

class RecordUsageAction
{
    public function __construct(
        protected BillingService $billing,
    ) {}

    public function execute(
        UsageMetric $metric,
        int $amount = 1,
        ?string $period = null,
        ?Company $company = null,
    ): UsageRecord {
        return $this->billing->registerConsumption($metric, $amount, $period, $company);
    }
}
