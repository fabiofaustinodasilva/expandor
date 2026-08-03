<?php

namespace App\Domains\Onboarding\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\Lead;
use App\Domains\Onboarding\DTOs\SaasOnboardingProgress;
use App\Domains\Onboarding\Enums\CompanyOnboardingStatus;

class SaasOnboardingService
{
    public const STEP_COMPANY = 1;

    public const STEP_TEAM = 2;

    public const STEP_CUSTOMER = 3;

    public const STEP_FINISH = 4;

    public const STEP_DONE = 5;

    /**
     * Perfis exibidos no onboarding (mapeados para roles existentes — sem alterar permissões).
     *
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

    public function progress(Company $company): SaasOnboardingProgress
    {
        $status = (string) ($company->onboarding_status ?? Company::ONBOARDING_PENDING);
        $step = max(1, (int) ($company->onboarding_step ?? 1));
        $completed = $status === Company::ONBOARDING_COMPLETED;

        $companyDone = $completed || $step >= self::STEP_TEAM;
        $teamDone = $completed || $step >= self::STEP_CUSTOMER;
        $customerDone = $completed || $step >= self::STEP_FINISH;
        $finishDone = $completed;

        $checklist = [
            ['key' => 'provisioned', 'label' => 'Empresa criada', 'done' => true],
            ['key' => 'company', 'label' => 'Configurar dados da empresa', 'done' => $companyDone],
            ['key' => 'team', 'label' => 'Adicionar equipe', 'done' => $teamDone],
            ['key' => 'customer', 'label' => 'Criar primeiro cliente', 'done' => $customerDone],
            ['key' => 'finish', 'label' => 'Finalizar configuração', 'done' => $finishDone],
        ];

        $percent = match (true) {
            $completed => 100,
            $step >= self::STEP_FINISH => 80,
            $step >= self::STEP_CUSTOMER => 60,
            $step >= self::STEP_TEAM => 40,
            default => 20,
        };

        $continueUrl = $completed ? null : match (true) {
            $step <= self::STEP_COMPANY => route('onboarding.company'),
            $step === self::STEP_TEAM => route('onboarding.team'),
            $step === self::STEP_CUSTOMER => route('onboarding.customer'),
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

    public function hasOnboardingTeamMember(Company $company): bool
    {
        return User::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereHas('role', fn ($q) => $q->whereIn('slug', array_keys($this->allowedRoles())))
            ->where('email', 'not like', 'demo.seller.%')
            ->count() > 1; // admin + pelo menos 1 membro, ou qualquer extra além do primeiro
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
}
