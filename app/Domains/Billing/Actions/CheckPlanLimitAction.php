<?php

namespace App\Domains\Billing\Actions;

use App\Domains\Billing\DTOs\LimitCheckResult;
use App\Domains\Billing\Enums\UsageMetric;
use App\Domains\Billing\Services\BillingService;
use App\Domains\Company\Models\Company;

class CheckPlanLimitAction
{
    public function __construct(
        protected BillingService $billing,
    ) {}

    public function execute(
        UsageMetric $metric,
        int $additional = 1,
        ?Company $company = null,
        bool $strict = true,
    ): LimitCheckResult {
        if ($strict) {
            return $this->billing->assertWithinLimit($metric, $additional, $company);
        }

        return $this->billing->checkLimit($metric, $additional, $company);
    }
}
