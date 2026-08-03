<?php

namespace App\Domains\Billing\Actions;

use App\Domains\Billing\Enums\UsageMetric;
use App\Domains\Billing\Repositories\BillingRepository;
use App\Domains\Company\Models\Plan;

class SyncPlanFeaturesAction
{
    public function __construct(
        protected BillingRepository $repository,
    ) {}

    /**
     * @param  array<string, string|null>|null  $overrides
     */
    public function execute(Plan $plan, ?array $overrides = null): void
    {
        $features = $overrides ?? [
            UsageMetric::USERS->value => $plan->max_users === null ? 'unlimited' : (string) $plan->max_users,
            UsageMetric::PROPERTIES->value => $plan->max_properties === null ? 'unlimited' : (string) $plan->max_properties,
            UsageMetric::CAMPAIGNS->value => $plan->max_campaigns === null ? 'unlimited' : (string) $plan->max_campaigns,
            UsageMetric::MESSAGES->value => $this->defaultMessagesLimit($plan),
            UsageMetric::AI_TOKENS->value => $this->defaultAiTokensLimit($plan),
        ];

        $this->repository->syncPlanFeatures($plan, $features);
    }

    protected function defaultMessagesLimit(Plan $plan): string
    {
        return match ($plan->slug) {
            'free' => '100',
            'professional' => '5000',
            default => 'unlimited',
        };
    }

    protected function defaultAiTokensLimit(Plan $plan): string
    {
        return match ($plan->slug) {
            'free' => '0',
            'professional' => '50000',
            default => 'unlimited',
        };
    }
}
