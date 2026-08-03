<?php

namespace App\Domains\Onboarding\Services;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\DTOs\ChecklistDTO;
use App\Domains\Onboarding\DTOs\StepDTO;
use App\Domains\Onboarding\Enums\OnboardingRunStatus;
use App\Domains\Onboarding\Enums\OnboardingStepStatus;
use App\Domains\Onboarding\Repositories\OnboardingRepository;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Training\Models\TrainingContent;
use App\Domains\Training\Repositories\TrainingRepository;
use App\Domains\Visits\Models\Visit;

class ChecklistService
{
    public function __construct(
        protected OnboardingRepository $repository,
        protected TrainingRepository $training,
    ) {}

    public function build(Company $company): ChecklistDTO
    {
        $run = $this->repository->runForCompany($company);
        $steps = [];

        foreach ($this->repository->progressForCompany($company) as $progress) {
            $step = $progress->step;
            if ($step === null) {
                continue;
            }

            $steps[] = new StepDTO(
                id: $step->id,
                key: $step->key,
                title: $step->title,
                description: $step->description,
                sortOrder: $step->sort_order,
                wizardKey: $step->wizard_key,
                status: $progress->status->value,
                percent: $progress->percent,
                completedAt: $progress->completed_at?->toIso8601String(),
                completedBy: $progress->completed_by,
                trainingSuggestions: $this->suggestionsForStep($step->training_keywords ?? []),
            );
        }

        $next = collect($steps)->first(
            fn (StepDTO $step) => ! in_array($step->status, [
                OnboardingStepStatus::Completed->value,
                OnboardingStepStatus::Skipped->value,
            ], true)
        );

        return new ChecklistDTO(
            steps: $steps,
            percent: (int) ($run?->percent ?? 0),
            status: $run?->status->value ?? OnboardingRunStatus::InProgress->value,
            alerts: $this->alerts($company),
            demoGenerated: (bool) ($run?->demo_generated ?? false),
            tourStatus: $run?->tour_status->value ?? 'pending',
            nextStepKey: $next?->key,
        );
    }

    public function refreshRunPercent(Company $company): void
    {
        $run = $this->repository->runForCompany($company);
        if ($run === null) {
            return;
        }

        $progress = $this->repository->progressForCompany($company);
        $total = max(1, $progress->count());
        $done = $progress->filter(
            fn ($item) => in_array($item->status, [OnboardingStepStatus::Completed, OnboardingStepStatus::Skipped], true)
        )->count();

        $percent = (int) round(($done / $total) * 100);

        $run->forceFill([
            'percent' => $percent,
            'status' => $percent >= 100
                ? OnboardingRunStatus::Completed
                : OnboardingRunStatus::InProgress,
            'finished_at' => $percent >= 100 ? ($run->finished_at ?? now()) : null,
        ])->save();
    }

    /**
     * @return array<int, string>
     */
    public function alerts(Company $company): array
    {
        $alerts = [];

        $sellerRoleId = Role::query()->where('slug', Role::SELLER)->value('id');
        $sellers = User::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->when($sellerRoleId, fn ($q) => $q->where('role_id', $sellerRoleId))
            ->count();

        if ($sellers === 0) {
            $alerts[] = 'Você ainda não cadastrou vendedores.';
        }

        if (Campaign::query()->withoutGlobalScopes()->where('company_id', $company->id)->count() === 0) {
            $alerts[] = 'Você ainda não criou campanhas.';
        }

        if (Property::query()->withoutGlobalScopes()->where('company_id', $company->id)->count() === 0) {
            $alerts[] = 'Você ainda não possui imóveis.';
        }

        if (City::query()->withoutGlobalScopes()->where('company_id', $company->id)->count() === 0) {
            $alerts[] = 'Você ainda não cadastrou cidades.';
        }

        if (Sector::query()->withoutGlobalScopes()->where('company_id', $company->id)->count() === 0) {
            $alerts[] = 'Você ainda não criou setores.';
        }

        if (Product::query()->withoutGlobalScopes()->where('company_id', $company->id)->count() === 0) {
            $alerts[] = 'Você ainda não cadastrou produtos.';
        }

        if (Visit::query()->withoutGlobalScopes()->where('company_id', $company->id)->count() === 0) {
            $alerts[] = 'Você ainda não registrou visitas.';
        }

        return $alerts;
    }

    /**
     * @param  array<int, string>  $keywords
     * @return array<int, array{id:int,title:string,type:string}>
     */
    protected function suggestionsForStep(array $keywords): array
    {
        if ($keywords === []) {
            return [];
        }

        $contents = TrainingContent::query()
            ->withoutGlobalScopes()
            ->where('active', true)
            ->where(function ($query) use ($keywords): void {
                foreach ($keywords as $keyword) {
                    $query->orWhere('title', 'like', '%'.$keyword.'%')
                        ->orWhere('description', 'like', '%'.$keyword.'%');
                }
            })
            ->limit(5)
            ->get(['id', 'title', 'type']);

        if ($contents->isEmpty()) {
            $contents = TrainingContent::query()
                ->where('active', true)
                ->latest('id')
                ->limit(3)
                ->get(['id', 'title', 'type']);
        }

        return $contents->map(fn (TrainingContent $content) => [
            'id' => $content->id,
            'title' => $content->title,
            'type' => (string) ($content->type ?? 'article'),
        ])->all();
    }
}
