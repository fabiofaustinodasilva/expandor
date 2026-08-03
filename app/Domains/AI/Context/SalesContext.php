<?php

namespace App\Domains\AI\Context;

use App\Domains\AI\Context\Contracts\ContextBuilder;
use App\Domains\AI\DTOs\AIContextPayload;
use App\Domains\AI\Enums\AIContextType;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use App\Tenancy\TenantContext;
use RuntimeException;

class SalesContext implements ContextBuilder
{
    public function __construct(
        protected TenantContext $tenant,
    ) {}

    public function type(): AIContextType
    {
        return AIContextType::SALES;
    }

    public function build(array $options = []): AIContextPayload
    {
        $company = $this->tenant->company();

        if ($company === null) {
            throw new RuntimeException('Tenant company is required to build AI sales context.');
        }

        $properties = Property::query()->count();
        $residents = Resident::query()->count();
        $visits = Visit::query()->count();
        $campaigns = Campaign::query()->count();
        $pendingFollowUps = FollowUp::query()->whereNull('completed_at')->count();

        $visitsByStatus = Visit::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $statusLines = empty($visitsByStatus)
            ? ['- nenhuma visita registrada']
            : collect($visitsByStatus)
                ->map(fn ($total, $status) => "- {$status}: {$total}")
                ->values()
                ->all();

        $body = implode("\n", [
            'Contexto: Vendas (somente dados do tenant atual).',
            'Company ID: '.$company->id,
            'Imóveis: '.$properties,
            'Moradores: '.$residents,
            'Visitas: '.$visits,
            'Campanhas: '.$campaigns,
            'Retornos pendentes: '.$pendingFollowUps,
            'Visitas por status:',
            ...$statusLines,
            'Regra: sugestões apenas; não execute ações comerciais.',
        ]);

        return new AIContextPayload(
            type: $this->type(),
            body: $body,
            companyId: (int) $company->id,
        );
    }
}
