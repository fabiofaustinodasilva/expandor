<?php

namespace App\Domains\Onboarding\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanySetting;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\Lead;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\Onboarding\DTOs\SaasOnboardingProgress;

class SaasOnboardingService
{
    public const STEP_COMPANY = 1;

    public const STEP_TEAM = 2;

    public const STEP_CUSTOMER = 3;

    public const STEP_SALES_SETUP = 4;

    public const STEP_BRANDING = 5;

    public const STEP_FINISH = 6;

    public const STEP_DONE = 7;

    public const DISMISS_KEY_PREFIX = 'onboarding.activation_dismissed.user.';

    public const EMPTY_DISMISS_KEY_PREFIX = 'onboarding.workspace_ready_dismissed.user.';

    /**
     * @return array<string, string> slug => label
     */
    public function allowedRoles(): array
    {
        return [
            Role::ADMINISTRATOR => 'Administrador',
            Role::SELLER => 'Vendedor',
            Role::SUPERVISOR => 'Operador',
            Role::MANAGER => 'Financeiro',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function dealStatusOptions(): array
    {
        return [
            'novo' => 'Novo',
            'qualificacao' => 'Qualificação',
            'proposta' => 'Proposta',
            'negociacao' => 'Negociação',
        ];
    }

    public function progress(Company $company): SaasOnboardingProgress
    {
        $status = (string) ($company->onboarding_status ?? Company::ONBOARDING_PENDING);
        $step = max(1, (int) ($company->onboarding_step ?? 1));
        $completed = $status === Company::ONBOARDING_COMPLETED;

        $checklist = [
            ['key' => 'provisioned', 'label' => 'Empresa criada', 'done' => true],
            ['key' => 'company', 'label' => 'Dados da empresa', 'done' => $completed || $step >= self::STEP_TEAM],
            ['key' => 'team', 'label' => 'Usuários da equipe', 'done' => $completed || $step >= self::STEP_CUSTOMER],
            ['key' => 'customer', 'label' => 'Primeiro cliente cadastrado', 'done' => $completed || $step >= self::STEP_SALES_SETUP],
            ['key' => 'deal', 'label' => 'Primeiro negócio criado', 'done' => $completed || $step >= self::STEP_BRANDING],
            ['key' => 'branding', 'label' => 'Configurar identidade visual', 'done' => $completed || $step >= self::STEP_FINISH],
            ['key' => 'finish', 'label' => 'Finalizar configuração', 'done' => $completed],
        ];

        $percent = match (true) {
            $completed => 100,
            $step >= self::STEP_FINISH => 90,
            $step >= self::STEP_BRANDING => 80,
            $step >= self::STEP_SALES_SETUP => 65,
            $step >= self::STEP_CUSTOMER => 50,
            $step >= self::STEP_TEAM => 35,
            default => 20,
        };

        $continueUrl = $completed ? null : match (true) {
            $step <= self::STEP_COMPANY => route('onboarding.company'),
            $step === self::STEP_TEAM => route('onboarding.team'),
            $step === self::STEP_CUSTOMER => route('onboarding.customer'),
            $step === self::STEP_SALES_SETUP => route('onboarding.deal'),
            $step === self::STEP_BRANDING => route('onboarding.branding'),
            default => route('onboarding.finish'),
        };

        return new SaasOnboardingProgress(
            status: $status,
            step: $step,
            percent: $percent,
            checklist: $checklist,
            completed: $completed,
            continueUrl: $continueUrl,
        );
    }

    public function markStarted(Company $company): Company
    {
        if ($company->onboarding_status === Company::ONBOARDING_COMPLETED) {
            return $company;
        }

        if ($company->onboarding_status === Company::ONBOARDING_PENDING) {
            $company->forceFill([
                'onboarding_status' => Company::ONBOARDING_IN_PROGRESS,
            ])->save();
        }

        return $company->refresh();
    }

    public function advanceTo(Company $company, int $step, string $status = Company::ONBOARDING_IN_PROGRESS): Company
    {
        $company->forceFill([
            'onboarding_status' => $status,
            'onboarding_step' => max((int) $company->onboarding_step, $step),
        ])->save();

        return $company->refresh();
    }

    public function complete(Company $company): Company
    {
        $company->forceFill([
            'onboarding_status' => Company::ONBOARDING_COMPLETED,
            'onboarding_step' => self::STEP_DONE,
            'onboarding_completed_at' => now(),
        ])->save();

        return $company->refresh();
    }

    public function dismissActivationCard(Company $company, User $user): void
    {
        $progress = $this->progress($company);

        CompanySetting::query()->withoutGlobalScopes()->updateOrCreate(
            [
                'company_id' => $company->id,
                'key' => self::DISMISS_KEY_PREFIX.$user->id,
            ],
            [
                'value' => json_encode([
                    'step' => $progress->step,
                    'percent' => $progress->percent,
                    'dismissed_at' => now()->toIso8601String(),
                ], JSON_THROW_ON_ERROR),
            ]
        );
    }

    public function shouldShowActivationCard(Company $company, User $user): bool
    {
        if (! $company->needsSaasOnboarding()) {
            return false;
        }

        if (! $user->hasPermission('onboarding.manage')) {
            return false;
        }

        $setting = CompanySetting::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('key', self::DISMISS_KEY_PREFIX.$user->id)
            ->value('value');

        if ($setting === null) {
            return true;
        }

        $payload = json_decode((string) $setting, true);
        $dismissedStep = (int) ($payload['step'] ?? 0);
        $currentStep = (int) ($company->onboarding_step ?? 1);

        // Reaparece se avançou para etapa crítica nova após o dismiss.
        return $currentStep > $dismissedStep;
    }

    public function dismissWorkspaceReady(Company $company, User $user): void
    {
        CompanySetting::query()->withoutGlobalScopes()->updateOrCreate(
            [
                'company_id' => $company->id,
                'key' => self::EMPTY_DISMISS_KEY_PREFIX.$user->id,
            ],
            [
                'value' => now()->toIso8601String(),
            ]
        );
    }

    public function shouldShowWorkspaceReady(Company $company, User $user): bool
    {
        if (! $company->hasCompletedSaasOnboarding()) {
            return false;
        }

        if ($company->onboarding_completed_at === null) {
            return false;
        }

        // Mostra por até 14 dias após conclusão.
        if ($company->onboarding_completed_at->lt(now()->subDays(14))) {
            return false;
        }

        $dismissed = CompanySetting::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('key', self::EMPTY_DISMISS_KEY_PREFIX.$user->id)
            ->exists();

        return ! $dismissed;
    }

    public function findExistingOnboardingLead(Company $company, ?string $email, ?string $phone): ?Lead
    {
        $query = Lead::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id);

        if (filled($email)) {
            $byEmail = (clone $query)->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();
            if ($byEmail) {
                return $byEmail;
            }
        }

        if (filled($phone)) {
            return (clone $query)->where('phone', $phone)->first();
        }

        return null;
    }

    /**
     * @return list<Lead>
     */
    public function companyLeads(Company $company): array
    {
        return Lead::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->all();
    }

    public function findOnboardingDeal(Company $company): ?Opportunity
    {
        return Opportunity::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('notes', 'like', '[Onboarding]%')
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array{onboarding_started: int, onboarding_completed: int, activation_rate: float, average_activation_time_hours: ?float}
     */
    public function activationMetrics(): array
    {
        $base = Company::query()->where('is_system', false);

        $created = (clone $base)->count();
        $started = (clone $base)
            ->whereIn('onboarding_status', [
                Company::ONBOARDING_IN_PROGRESS,
                Company::ONBOARDING_COMPLETED,
            ])
            ->count();
        $completed = (clone $base)
            ->where('onboarding_status', Company::ONBOARDING_COMPLETED)
            ->count();

        $completedCompanies = Company::query()
            ->where('is_system', false)
            ->where('onboarding_status', Company::ONBOARDING_COMPLETED)
            ->whereNotNull('onboarding_completed_at')
            ->get(['created_at', 'onboarding_completed_at']);

        $avgHours = null;
        if ($completedCompanies->isNotEmpty()) {
            $totalHours = $completedCompanies->sum(function (Company $company): float {
                return $company->created_at->diffInSeconds($company->onboarding_completed_at) / 3600;
            });
            $avgHours = round($totalHours / $completedCompanies->count(), 1);
        }

        return [
            'onboarding_started' => $started,
            'onboarding_completed' => $completed,
            'activation_rate' => round($completed / max($created, 1) * 100, 1),
            'average_activation_time_hours' => $avgHours,
        ];
    }
}
