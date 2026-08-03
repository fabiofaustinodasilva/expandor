<?php

namespace App\Domains\AI\Context;

use App\Domains\AI\Context\Contracts\ContextBuilder;
use App\Domains\AI\DTOs\AIContextPayload;
use App\Domains\AI\Enums\AIContextType;
use App\Domains\Company\Models\User;
use App\Tenancy\TenantContext;
use RuntimeException;

class CompanyContext implements ContextBuilder
{
    public function __construct(
        protected TenantContext $tenant,
    ) {}

    public function type(): AIContextType
    {
        return AIContextType::COMPANY;
    }

    public function build(array $options = []): AIContextPayload
    {
        $company = $this->tenant->company();

        if ($company === null) {
            throw new RuntimeException('Tenant company is required to build AI company context.');
        }

        $usersCount = User::query()->count();
        $planName = $company->subscriptions()
            ->with('plan:id,name')
            ->latest('id')
            ->first()
            ?->plan
            ?->name
            ?? 'não informado';

        $body = implode("\n", [
            'Contexto: Empresa (somente dados do tenant atual).',
            'Company ID: '.$company->id,
            'Nome: '.$company->name,
            'Status: '.($company->status ?? 'n/a'),
            'Plano: '.$planName,
            'Usuários ativos no tenant: '.$usersCount,
            'Regra: use apenas estes dados; não invente métricas.',
        ]);

        return new AIContextPayload(
            type: $this->type(),
            body: $body,
            companyId: (int) $company->id,
        );
    }
}
