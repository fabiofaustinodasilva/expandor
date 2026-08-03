<?php

namespace App\Domains\Onboarding\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\Actions\CompleteStepAction;
use App\Domains\Onboarding\Actions\FinishOnboardingAction;
use App\Domains\Onboarding\Actions\InitializeOnboardingAction;
use App\Domains\Onboarding\Actions\SkipStepAction;
use App\Domains\Onboarding\DTOs\OnboardingStatusDTO;
use App\Domains\Onboarding\Enums\OnboardingRunStatus;
use App\Domains\Onboarding\Enums\TourStatus;
use App\Domains\Onboarding\Repositories\OnboardingRepository;
use App\Tenancy\TenantContext;

class OnboardingService
{
    public function __construct(
        protected OnboardingRepository $repository,
        protected ChecklistService $checklist,
        protected WizardService $wizard,
        protected DemoDataService $demoData,
        protected InitializeOnboardingAction $initialize,
        protected CompleteStepAction $completeStepAction,
        protected SkipStepAction $skipStepAction,
        protected FinishOnboardingAction $finishAction,
        protected TenantContext $tenant,
    ) {}

    public function ensureInitialized(?Company $company = null): void
    {
        $company ??= $this->tenant->company();
        if ($company === null || $company->isSystem()) {
            return;
        }

        $this->initialize->execute($company);
    }

    public function status(?Company $company = null): OnboardingStatusDTO
    {
        $company ??= $this->tenant->company();
        abort_if($company === null, 404);

        $this->ensureInitialized($company);

        $run = $this->repository->runForCompany($company);
        $checklist = $this->checklist->build($company);
        $steps = $checklist->steps;

        return new OnboardingStatusDTO(
            companyId: $company->id,
            status: $run?->status->value ?? OnboardingRunStatus::InProgress->value,
            percent: (int) ($run?->percent ?? 0),
            isCompleted: $run?->status === OnboardingRunStatus::Completed,
            demoGenerated: (bool) ($run?->demo_generated ?? false),
            tourStatus: $run?->tour_status->value ?? TourStatus::Pending->value,
            currentWizardKey: $checklist->nextStepKey ?? 'welcome',
            steps: $steps,
            alerts: $checklist->alerts,
            tourStops: $this->tourStops(),
            checklist: $checklist,
        );
    }

    public function isCompleted(?Company $company = null): bool
    {
        $company ??= $this->tenant->company();
        if ($company === null || $company->isSystem()) {
            return true;
        }

        $this->ensureInitialized($company);

        return $this->repository->runForCompany($company)?->status === OnboardingRunStatus::Completed;
    }

    public function completeStep(string $stepKey, ?Company $company = null, ?User $actor = null, array $metadata = []): void
    {
        $company ??= $this->tenant->company();
        abort_if($company === null, 404);

        $this->completeStepAction->execute($company, $stepKey, $actor, $metadata);
    }

    public function skipStep(string $stepKey, ?Company $company = null, ?User $actor = null): void
    {
        $company ??= $this->tenant->company();
        abort_if($company === null, 404);

        $this->skipStepAction->execute($company, $stepKey, $actor);
    }

    public function finish(?Company $company = null, ?User $actor = null): void
    {
        $company ??= $this->tenant->company();
        abort_if($company === null, 404);

        $this->finishAction->execute($company, $actor);
    }

    /**
     * @return array<string, mixed>
     */
    public function generateDemo(?Company $company = null, ?User $actor = null): array
    {
        $company ??= $this->tenant->company();
        abort_if($company === null, 404);

        $this->ensureInitialized($company);

        return $this->demoData->generate($company, $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveWizardStep(string $wizardKey, array $data, ?Company $company = null, ?User $actor = null): void
    {
        $company ??= $this->tenant->company();
        abort_if($company === null, 404);

        $this->ensureInitialized($company);
        $this->wizard->saveStep($company, $wizardKey, $data, $actor);
    }

    public function updateTour(string $status, ?Company $company = null): void
    {
        $company ??= $this->tenant->company();
        abort_if($company === null, 404);

        $this->ensureInitialized($company);
        $run = $this->repository->runForCompany($company);
        $tourStatus = TourStatus::tryFrom($status) ?? TourStatus::Pending;

        $run?->forceFill([
            'tour_status' => $tourStatus,
            'tour_completed_at' => in_array($tourStatus, [TourStatus::Completed, TourStatus::Skipped], true)
                ? now()
                : null,
        ])->save();
    }

    /**
     * @return array<int, array{key:string,label:string,route:string|null}>
     */
    public function tourStops(): array
    {
        return [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => 'dashboard'],
            ['key' => 'map', 'label' => 'Mapa', 'route' => 'map.index'],
            ['key' => 'campaigns', 'label' => 'Campanhas', 'route' => 'campaigns.index'],
            ['key' => 'visits', 'label' => 'Visitas / Retornos', 'route' => 'follow-ups.index'],
            ['key' => 'training', 'label' => 'Academia', 'route' => 'training.categories.index'],
            ['key' => 'whatsapp', 'label' => 'WhatsApp', 'route' => 'communication.messages.index'],
            ['key' => 'ai', 'label' => 'IA', 'route' => 'ai.conversations.index'],
            ['key' => 'billing', 'label' => 'Billing', 'route' => 'company.plan.show'],
            ['key' => 'platform', 'label' => 'Platform', 'route' => 'platform.dashboard'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function platformMetrics(): array
    {
        return [
            'onboarding_in_progress' => $this->repository->countRunsByStatus(OnboardingRunStatus::InProgress),
            'onboarding_completed' => $this->repository->countRunsByStatus(OnboardingRunStatus::Completed),
            'onboarding_avg_hours' => $this->repository->averageCompletionHours(),
            'onboarding_stuck' => $this->repository->stuckCompaniesCount(),
            'onboarding_completion_rate' => $this->repository->completionRate(),
        ];
    }
}
