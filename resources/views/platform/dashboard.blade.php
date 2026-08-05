@extends('layouts.platform')

@section('title', 'Painel da plataforma')

@section('content')
    <x-ux.page-header
        title="Painel Expandor"
        description="Visão geral da operação e das empresas clientes."
        :breadcrumbs="[
            ['label' => 'Platform', 'href' => route('platform.dashboard')],
            ['label' => 'Painel'],
        ]"
    />

    @can('platform.manageBranding')
        @php $platformBrand = app(\App\Domains\Platform\Services\PlatformBrandingService::class)->payload(); @endphp
        <a class="card card-link" href="{{ route('platform.branding.edit') }}" style="display:block; margin-bottom:1.25rem;">
            <div class="card-header" style="border:0; margin:0; padding:0;">
                <div>
                    <div class="header-meta">Configurações</div>
                    <strong style="font-size:1.05rem;">Identidade da plataforma</strong>
                    <div class="header-meta" style="margin-top:0.25rem;">{{ $platformBrand->name() }} · logo, cores e favicon do login</div>
                </div>
                <span class="btn btn-ghost">Editar</span>
            </div>
            @if($platformBrand->logo())
                <img src="{{ $platformBrand->logo() }}" alt="{{ $platformBrand->name() }}" style="height:36px; max-width:140px; object-fit:contain; margin-top:0.85rem;">
            @endif
        </a>
    @endcan

    <section class="panel-section">
        <div class="panel-section__head">
            <h2 class="section-title">Operação</h2>
        </div>
        <div class="grid-metrics">
            <x-ux.metric label="Empresas ativas" :value="number_format($metrics->activeCompanies, 0, ',', '.')" />
            <x-ux.metric label="Usuários totais" :value="number_format($metrics->totalUsers, 0, ',', '.')" />
            <x-ux.metric label="Pontos / clientes" :value="number_format($metrics->totalProperties, 0, ',', '.')" />
            <x-ux.metric label="Consumo IA (tokens)" :value="number_format($metrics->aiTokensConsumed, 0, ',', '.')" />
            <x-ux.metric label="Mensagens enviadas" :value="number_format($metrics->messagesSent, 0, ',', '.')" />
        </div>
    </section>

    <section class="panel-section">
        <div class="panel-section__head">
            <h2 class="section-title">Receita e operação</h2>
        </div>
        <div class="grid-metrics">
            <x-ux.metric label="MRR" :value="'R$ '.number_format($metrics->mrr, 2, ',', '.')" />
            <x-ux.metric label="ARR" :value="'R$ '.number_format($metrics->arr, 2, ',', '.')" />
            <x-ux.metric label="Receita prevista (ARR)" :value="'R$ '.number_format($metrics->arr ?: ($metrics->mrr * 12), 2, ',', '.')" hint="MRR × 12" />
            <x-ux.metric label="Receita mensal" :value="'R$ '.number_format($metrics->monthlyRevenue, 2, ',', '.')" />
            <x-ux.metric label="Receita anual" :value="'R$ '.number_format($metrics->yearlyRevenue, 2, ',', '.')" />
            <x-ux.metric label="Clientes em teste" :value="number_format($metrics->trialClients, 0, ',', '.')" />
            <x-ux.metric label="Clientes suspensos" :value="number_format($metrics->suspendedClients, 0, ',', '.')" />
            <x-ux.metric label="Clientes inadimplentes" :value="number_format($metrics->pastDueClients, 0, ',', '.')" />
            @isset($metrics->totalCompanies)
                <x-ux.metric label="Empresas totais" :value="number_format($metrics->totalCompanies, 0, ',', '.')" />
            @endisset
            @isset($metrics->cancelledClients)
                <x-ux.metric label="Clientes cancelados" :value="number_format($metrics->cancelledClients, 0, ',', '.')" />
            @endisset
            @isset($metrics->churnRate)
                <x-ux.metric label="Churn" :value="number_format($metrics->churnRate, 1, ',', '.').'%'" />
            @endisset
            @isset($metrics->trialConversionRate)
                <x-ux.metric label="Conversão de teste" :value="number_format($metrics->trialConversionRate, 1, ',', '.').'%'" />
            @endisset
        </div>
    </section>

    @php
        $planRows = collect($metrics->clientsByPlan);
        $maxClients = max(1, (int) $planRows->max(fn ($row) => (int) ($row['count'] ?? 0)));
        $maxPlanMrr = max(1.0, (float) $planRows->max(fn ($row) => (float) ($row['mrr'] ?? 0)));
    @endphp

    <section class="panel-section">
        <div class="card">
            <div class="card-header">
                <h2 class="section-title">Clientes e receita por plano</h2>
            </div>
            <div class="grid grid-2">
                <div>
                    <div class="header-meta" style="margin-bottom:0.75rem;">Clientes por plano</div>
                    @forelse($planRows as $slug => $row)
                        @php $pct = round(((int) $row['count'] / $maxClients) * 100); @endphp
                        <div style="margin-bottom:0.65rem;">
                            <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.25rem;">
                                <span>{{ $slug }}</span>
                                <span class="header-meta">{{ $row['count'] }}</span>
                            </div>
                            <div style="height:10px; background:var(--bg-soft); border-radius:999px; overflow:hidden;">
                                <div style="height:100%; width:{{ $pct }}%; background:linear-gradient(90deg,#F59E0B,#D97706);"></div>
                            </div>
                        </div>
                    @empty
                        <x-ux.empty-state title="Sem dados de planos" description="Quando houver assinaturas ativas, os gráficos aparecerão aqui." />
                    @endforelse
                </div>
                <div>
                    <div class="header-meta" style="margin-bottom:0.75rem;">MRR por plano</div>
                    @forelse($planRows as $slug => $row)
                        @php $pct = round(((float) $row['mrr'] / $maxPlanMrr) * 100); @endphp
                        <div style="margin-bottom:0.65rem;">
                            <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.25rem;">
                                <span>{{ $slug }}</span>
                                <span class="header-meta">R$ {{ number_format((float) $row['mrr'], 2, ',', '.') }}</span>
                            </div>
                            <div style="height:10px; background:var(--bg-soft); border-radius:999px; overflow:hidden;">
                                <div style="height:100%; width:{{ $pct }}%; background:linear-gradient(90deg,#22C55E,#15803D);"></div>
                            </div>
                        </div>
                    @empty
                        <div class="header-meta">Sem dados de receita.</div>
                    @endforelse
                </div>
            </div>

            <div class="table-wrap" style="margin-top:1.25rem;">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Plano</th>
                        <th>Clientes</th>
                        <th>MRR</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($metrics->clientsByPlan as $slug => $row)
                        <tr>
                            <td>{{ $slug }}</td>
                            <td>{{ $row['count'] }}</td>
                            <td>R$ {{ number_format((float) $row['mrr'], 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">Sem dados de planos.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="panel-section">
        <div class="card">
            <div class="card-header">
                <h2 class="section-title">Configuração inicial</h2>
            </div>
            <div class="grid-metrics">
                <x-ux.metric label="Clientes em setup" :value="number_format($metrics->onboardingInProgress, 0, ',', '.')" />
                <x-ux.metric label="Clientes concluídos" :value="number_format($metrics->onboardingCompleted, 0, ',', '.')" />
                <x-ux.metric label="Tempo médio (horas)" :value="$metrics->onboardingAvgHours !== null ? number_format($metrics->onboardingAvgHours, 1, ',', '.') : '—'" />
                <x-ux.metric label="Clientes travados" :value="number_format($metrics->onboardingStuck, 0, ',', '.')" />
                <x-ux.metric label="Taxa de conclusão" :value="number_format($metrics->onboardingCompletionRate, 1, ',', '.').'%'" />
                <x-ux.metric label="Configuração iniciada" :value="number_format($metrics->saasOnboardingStarted, 0, ',', '.')" data-saas-metric="started" />
                <x-ux.metric label="Configuração concluída" :value="number_format($metrics->saasOnboardingCompleted, 0, ',', '.')" data-saas-metric="completed" />
                <x-ux.metric label="Taxa de ativação" :value="number_format($metrics->activationRate, 1, ',', '.').'%'" data-saas-metric="activation-rate" />
                <x-ux.metric label="Tempo médio de ativação (h)" :value="$metrics->averageActivationTimeHours !== null ? number_format($metrics->averageActivationTimeHours, 1, ',', '.') : '—'" data-saas-metric="avg-activation" />
            </div>
        </div>
    </section>

    <section class="panel-section">
        <div class="card">
            <div class="card-header">
                <h2 class="section-title">Saúde dos clientes</h2>
            </div>
            <div class="grid-metrics">
                <x-ux.metric label="Health médio" :value="$metrics->averageHealthScore !== null ? number_format($metrics->averageHealthScore, 1, ',', '.') : '—'" />
                <x-ux.metric label="Empresas saudáveis" :value="number_format($metrics->healthyCompanies, 0, ',', '.')" />
                <x-ux.metric label="Atenção / risco" :value="number_format($metrics->atRiskCompanies, 0, ',', '.')" />
                <x-ux.metric label="Críticas" :value="number_format($metrics->criticalCompanies, 0, ',', '.')" />
                <x-ux.metric label="Impersonações ativas" :value="number_format($metrics->activeImpersonations, 0, ',', '.')" />
            </div>
        </div>
    </section>
@endsection
