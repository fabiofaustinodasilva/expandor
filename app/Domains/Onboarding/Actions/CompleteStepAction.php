<?php

namespace App\Domains\Onboarding\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\Enums\OnboardingStepStatus;
use App\Domains\Onboarding\Models\OnboardingProgress;
use App\Domains\Onboarding\Models\OnboardingStep;
use App\Domains\Onboarding\Repositories\OnboardingRepository;
use App\Domains\Onboarding\Services\ChecklistService;
use Illuminate\Validation\ValidationException;

class CompleteStepAction
{
    public function __construct(
        protected OnboardingRepository $repository,
        protected ChecklistService $checklist,
        protected InitializeOnboardingAction $initialize,
    ) {}

    public function execute(Company $company, string $stepKey, ?User $actor = null, array $metadata = []): OnboardingProgress
    {
        $this->initialize->execute($company);

        $step = $this->repository->findStepByKey($stepKey);

        if ($step === null) {
            throw ValidationException::withMessages([
                'step' => ['Etapa de onboarding inválida.'],
            ]);
        }

        $progress = $this->repository->progressForStep($company, $step);

        if ($progress === null) {
            throw ValidationException::withMessages([
                'step' => ['Progresso da etapa não encontrado.'],
            ]);
        }

        $progress->forceFill([
            'status' => OnboardingStepStatus::Completed,
            'percent' => 100,
            'completed_by' => $actor?->id,
            'started_at' => $progress->started_at ?? now(),
            'completed_at' => now(),
            'metadata' => array_merge($progress->metadata ?? [], $metadata),
        ])->save();

        $this->checklist->refreshRunPercent($company);

        return $progress->fresh('step');
    }
}
