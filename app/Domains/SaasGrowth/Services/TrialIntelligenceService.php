<?php

namespace App\Domains\SaasGrowth\Services;

use App\Domains\Company\Models\Company;
use App\Domains\SaasGrowth\Enums\TrialMilestone;
use App\Domains\SaasGrowth\Models\TrialMilestoneRecord;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Support\Collection;

class TrialIntelligenceService
{
    public function __construct(
        protected SecurityService $security,
    ) {}

    public function complete(Company $company, TrialMilestone $milestone): TrialMilestoneRecord
    {
        $record = TrialMilestoneRecord::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'milestone' => $milestone->value,
            ],
            ['completed_at' => now()],
        );

        if ($record->wasRecentlyCreated) {
            $this->security->recordAudit(
                action: 'trial.milestone_completed',
                user: null,
                auditable: $company,
                newValues: ['milestone' => $milestone->value],
            );
        }

        return $record;
    }

    public function markStarted(Company $company): void
    {
        $this->complete($company, TrialMilestone::CompanyCreated);
        $this->security->recordAudit(
            action: 'trial.started',
            user: null,
            auditable: $company,
            newValues: ['company_id' => $company->id],
        );
    }

    public function markConverted(Company $company): void
    {
        $this->complete($company, TrialMilestone::Activated);
        $this->security->recordAudit(
            action: 'trial.converted',
            user: null,
            auditable: $company,
            newValues: ['company_id' => $company->id],
        );
    }

    /** @return Collection<int, TrialMilestoneRecord> */
    public function forCompany(Company $company): Collection
    {
        return TrialMilestoneRecord::query()
            ->where('company_id', $company->id)
            ->orderBy('completed_at')
            ->get();
    }

    public function progressPercent(Company $company): float
    {
        $done = TrialMilestoneRecord::query()
            ->where('company_id', $company->id)
            ->count();

        return round(($done / max(1, count(TrialMilestone::cases()))) * 100, 1);
    }
}
