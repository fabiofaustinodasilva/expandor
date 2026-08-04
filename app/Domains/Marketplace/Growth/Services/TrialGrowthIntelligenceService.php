<?php

namespace App\Domains\Marketplace\Growth\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Marketplace\Growth\Enums\MarketplaceLeadStatus;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Growth\Models\MarketplaceTrialMilestone;
use Illuminate\Support\Facades\Log;

class TrialGrowthIntelligenceService
{
    public const TRIAL_STARTED = 'trial.started';

    public const TRIAL_DAY1 = 'trial.day1';

    public const TRIAL_DAY3 = 'trial.day3';

    public const TRIAL_DAY7 = 'trial.day7';

    public const TRIAL_ACTIVATION_COMPLETED = 'trial.activation_completed';

    public function __construct(
        protected ConversionTrackingService $tracking,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function recordMilestone(Company $company, string $event, array $metadata = []): MarketplaceTrialMilestone
    {
        $milestone = MarketplaceTrialMilestone::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'event' => $event,
            ],
            [
                'occurred_at' => now(),
                'metadata' => $metadata !== [] ? $metadata : null,
            ],
        );

        if ($milestone->wasRecentlyCreated) {
            $this->tracking->record('marketplace.'.$event, [
                'company_id' => $company->id,
                'metadata' => array_merge($metadata, ['trial_event' => $event]),
            ]);
        }

        return $milestone;
    }

    public function markTrialStarted(Company $company, ?string $email = null): void
    {
        $this->recordMilestone($company, self::TRIAL_STARTED);

        if (filled($email)) {
            MarketplaceLead::query()
                ->where('email', strtolower($email))
                ->whereIn('status', [MarketplaceLeadStatus::New->value, MarketplaceLeadStatus::Contacted->value])
                ->update(['status' => MarketplaceLeadStatus::TrialStarted->value]);
        }
    }

    public function markActivationCompleted(Company $company): void
    {
        $this->recordMilestone($company, self::TRIAL_ACTIVATION_COMPLETED);
    }

    public function evaluateDayMilestones(Company $company): void
    {
        $started = MarketplaceTrialMilestone::query()
            ->where('company_id', $company->id)
            ->where('event', self::TRIAL_STARTED)
            ->first();

        if ($started === null) {
            return;
        }

        $days = $started->occurred_at->diffInDays(now());

        try {
            if ($days >= 1) {
                $this->recordMilestone($company, self::TRIAL_DAY1);
            }
            if ($days >= 3) {
                $this->recordMilestone($company, self::TRIAL_DAY3);
            }
            if ($days >= 7) {
                $this->recordMilestone($company, self::TRIAL_DAY7);
            }
        } catch (\Throwable $e) {
            Log::warning('marketplace.trial_milestone_failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
