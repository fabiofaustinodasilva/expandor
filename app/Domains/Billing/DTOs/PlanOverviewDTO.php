<?php

namespace App\Domains\Billing\DTOs;

use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Subscription;
use Illuminate\Support\Collection;

class PlanOverviewDTO
{
    /**
     * @param  Collection<int, MetricUsageDTO>  $metrics
     * @param  array<string, string|null>  $features
     */
    public function __construct(
        public readonly ?Plan $plan,
        public readonly ?Subscription $subscription,
        public readonly Collection $metrics,
        public readonly array $features,
        public readonly string $period,
    ) {}
}
