<?php

namespace App\Domains\Onboarding\Services;

use App\Domains\Branding\Models\Brand;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\DTOs\EnvironmentProgressDTO;
use App\Domains\Onboarding\DTOs\EnvironmentProgressItem;
use App\Domains\Onboarding\Enums\OnboardingStepStatus;
use App\Domains\Onboarding\Models\OnboardingProgress;
use App\Domains\Onboarding\Models\OnboardingStep;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\Visit;

/**
 * Progresso visual do ambiente Trial (Sprint 5.5.4) — independente do percent legado do wizard.
 */
class EnvironmentProgressService
{
    public function forCompany(Company $company): EnvironmentProgressDTO
    {
        $brandingDone = $this->brandingDone($company);
        $productDone = Product::query()->withoutGlobalScopes()->where('company_id', $company->id)->exists();
        $clientDone = Property::query()->withoutGlobalScopes()->where('company_id', $company->id)->exists();
        $teamDone = User::query()->withoutGlobalScopes()->where('company_id', $company->id)->count() > 1;
        $saleDone = $this->saleDone($company);

        $companyCreated = true;

        // Empresa = 20% base; demais 5 itens = 16% cada (total 100).
        $percent = 20;
        foreach ([$brandingDone, $productDone, $clientDone, $teamDone, $saleDone] as $done) {
            if ($done) {
                $percent += 16;
            }
        }

        $items = [
            new EnvironmentProgressItem('company', 'Empresa criada', $companyCreated),
            new EnvironmentProgressItem('branding', 'Configurar identidade', $brandingDone, 'company.branding.edit'),
            new EnvironmentProgressItem('product', 'Criar primeiro produto', $productDone, 'commissions.products.create'),
            new EnvironmentProgressItem('client', 'Cadastrar primeiro cliente/ponto', $clientDone, 'properties.create'),
            new EnvironmentProgressItem('team', 'Criar primeiro usuário da equipe', $teamDone, 'operations.team'),
            new EnvironmentProgressItem('sale', 'Fazer primeira venda', $saleDone, 'map.index'),
        ];

        return new EnvironmentProgressDTO(
            percent: min(100, $percent),
            items: $items,
            companyCreated: $companyCreated,
            brandingDone: $brandingDone,
            productDone: $productDone,
            clientDone: $clientDone,
            teamDone: $teamDone,
            saleDone: $saleDone,
        );
    }

    protected function brandingDone(Company $company): bool
    {
        if ($this->stepCompleted($company, 'branding')) {
            return true;
        }

        $brand = Brand::query()->withoutGlobalScopes()->where('company_id', $company->id)->first();

        if ($brand === null) {
            return false;
        }

        return filled($brand->logo)
            || filled($brand->logo_mark)
            || filled($brand->favicon)
            || filled($brand->slogan);
    }

    protected function saleDone(Company $company): bool
    {
        if (Sale::query()->withoutGlobalScopes()->where('company_id', $company->id)->exists()) {
            return true;
        }

        return Visit::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', VisitStatus::INSTALLATION_REQUESTED->value)
            ->exists();
    }

    protected function stepCompleted(Company $company, string $stepKey): bool
    {
        $stepId = OnboardingStep::query()->where('key', $stepKey)->value('id');

        if ($stepId === null) {
            return false;
        }

        $status = OnboardingProgress::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('onboarding_step_id', $stepId)
            ->value('status');

        if ($status instanceof OnboardingStepStatus) {
            return in_array($status, [OnboardingStepStatus::Completed, OnboardingStepStatus::Skipped], true);
        }

        return in_array((string) $status, [
            OnboardingStepStatus::Completed->value,
            OnboardingStepStatus::Skipped->value,
        ], true);
    }
}
