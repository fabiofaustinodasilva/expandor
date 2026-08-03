@extends('layouts.platform')

@section('title', 'Platform Dashboard')

@section('content')
    <div style="margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Expandor Platform</h1>
        <div class="header-meta">Painel exclusivo do proprietário da plataforma</div>
    </div>

    @can('platform.manageBranding')
        @php $platformBrand = app(\App\Domains\Platform\Services\PlatformBrandingService::class)->payload(); @endphp
        <a class="card" href="{{ route('platform.branding.edit') }}" style="display:block; margin-bottom:1rem;">
            <div class="header-meta">Configurações da Plataforma</div>
            <div style="display:flex; gap:1rem; align-items:center; margin-top:.5rem;">
                @if($platformBrand->logo())
                    <img src="{{ $platformBrand->logo() }}" alt="" style="height:40px; max-width:120px; object-fit:contain;">
                @endif
                <div>
                    <strong style="font-size:1.1rem;">Identidade da Plataforma</strong>
                    <div class="header-meta">{{ $platformBrand->name() }} · logo, cores e favicon do login</div>
                </div>
                <span class="btn btn-ghost" style="margin-left:auto;">Editar</span>
            </div>
        </a>
    @endcan

    <div class="grid grid-2" style="margin-bottom:1rem;">
        <div class="card">
            <div class="header-meta">Empresas ativas</div>
            <div style="font-size:2rem; font-weight:700;">{{ number_format($metrics->activeCompanies, 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="header-meta">Usuários totais</div>
            <div style="font-size:2rem; font-weight:700;">{{ number_format($metrics->totalUsers, 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="header-meta">Pontos / clientes totais</div>
            <div style="font-size:2rem; font-weight:700;">{{ number_format($metrics->totalProperties, 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="header-meta">Consumo IA (tokens)</div>
            <div style="font-size:2rem; font-weight:700;">{{ number_format($metrics->aiTokensConsumed, 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="header-meta">Mensagens enviadas</div>
            <div style="font-size:2rem; font-weight:700;">{{ number_format($metrics->messagesSent, 0, ',', '.') }}</div>
        </div>
    </div>

    <h2>Receita e SaaS</h2>
    <div class="grid grid-2" style="margin-bottom:1rem;">
        <div class="card">
            <div class="header-meta">MRR</div>
            <div style="font-size:1.8rem; font-weight:700;">R$ {{ number_format($metrics->mrr, 2, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="header-meta">ARR</div>
            <div style="font-size:1.8rem; font-weight:700;">R$ {{ number_format($metrics->arr, 2, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="header-meta">Receita prevista (ARR)</div>
            <div style="font-size:1.8rem; font-weight:700;">R$ {{ number_format($metrics->arr ?: ($metrics->mrr * 12), 2, ',', '.') }}</div>
            <div class="header-meta">MRR × 12</div>
        </div>
        <div class="card">
            <div class="header-meta">Receita mensal</div>
            <div style="font-size:1.8rem; font-weight:700;">R$ {{ number_format($metrics->monthlyRevenue, 2, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="header-meta">Receita anual</div>
            <div style="font-size:1.8rem; font-weight:700;">R$ {{ number_format($metrics->yearlyRevenue, 2, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="header-meta">Clientes trial</div>
            <div style="font-size:1.8rem; font-weight:700;">{{ number_format($metrics->trialClients, 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="header-meta">Clientes suspensos</div>
            <div style="font-size:1.8rem; font-weight:700;">{{ number_format($metrics->suspendedClients, 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="header-meta">Clientes inadimplentes</div>
            <div style="font-size:1.8rem; font-weight:700;">{{ number_format($metrics->pastDueClients, 0, ',', '.') }}</div>
        </div>
        @isset($metrics->totalCompanies)
            <div class="card">
                <div class="header-meta">Empresas totais</div>
                <div style="font-size:1.8rem; font-weight:700;">{{ number_format($metrics->totalCompanies, 0, ',', '.') }}</div>
            </div>
        @endisset
        @isset($metrics->cancelledClients)
            <div class="card">
                <div class="header-meta">Clientes cancelados</div>
                <div style="font-size:1.8rem; font-weight:700;">{{ number_format($metrics->cancelledClients, 0, ',', '.') }}</div>
            </div>
        @endisset
        @isset($metrics->churnRate)
            <div class="card">
                <div class="header-meta">Churn</div>
                <div style="font-size:1.8rem; font-weight:700;">{{ number_format($metrics->churnRate, 1, ',', '.') }}%</div>
            </div>
        @endisset
        @isset($metrics->trialConversionRate)
            <div class="card">
                <div class="header-meta">Conversão de trial</div>
                <div style="font-size:1.8rem; font-weight:700;">{{ number_format($metrics->trialConversionRate, 1, ',', '.') }}%</div>
            </div>
        @endisset
    </div>

    @php
        $planRows = collect($metrics->clientsByPlan);
        $maxClients = max(1, (int) $planRows->max(fn ($row) => (int) ($row['count'] ?? 0)));
        $maxPlanMrr = max(1.0, (float) $planRows->max(fn ($row) => (float) ($row['mrr'] ?? 0)));
    @endphp

    <div class="card" style="margin-bottom:1rem;">
        <h2 style="margin-top:0;">Gráficos — clientes e receita por plano</h2>
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
                    <div class="header-meta">Sem dados de planos.</div>
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
    </div>

    <div class="card">
        <h2 style="margin-top:0;">Clientes e receita por plano</h2>
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
    <div class="card">
        <h2 style="margin-top:0;">Onboarding / Setup</h2>
        <div class="grid grid-2">
            <div>
                <div class="header-meta">Clientes em setup</div>
                <div style="font-size:1.5rem; font-weight:700;">{{ number_format($metrics->onboardingInProgress, 0, ',', '.') }}</div>
            </div>
            <div>
                <div class="header-meta">Clientes concluídos</div>
                <div style="font-size:1.5rem; font-weight:700;">{{ number_format($metrics->onboardingCompleted, 0, ',', '.') }}</div>
            </div>
            <div>
                <div class="header-meta">Tempo médio (horas)</div>
                <div style="font-size:1.5rem; font-weight:700;">{{ $metrics->onboardingAvgHours !== null ? number_format($metrics->onboardingAvgHours, 1, ',', '.') : '—' }}</div>
            </div>
            <div>
                <div class="header-meta">Clientes travados</div>
                <div style="font-size:1.5rem; font-weight:700;">{{ number_format($metrics->onboardingStuck, 0, ',', '.') }}</div>
            </div>
            <div>
                <div class="header-meta">Taxa de conclusão</div>
                <div style="font-size:1.5rem; font-weight:700;">{{ number_format($metrics->onboardingCompletionRate, 1, ',', '.') }}%</div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:1rem;">
        <h2 style="margin-top:0;">Customer Health & Console</h2>
        <div class="grid grid-2">
            <div>
                <div class="header-meta">Health médio</div>
                <div style="font-size:1.5rem; font-weight:700;">{{ $metrics->averageHealthScore !== null ? number_format($metrics->averageHealthScore, 1, ',', '.') : '—' }}</div>
            </div>
            <div>
                <div class="header-meta">Empresas saudáveis</div>
                <div style="font-size:1.5rem; font-weight:700;">{{ number_format($metrics->healthyCompanies, 0, ',', '.') }}</div>
            </div>
            <div>
                <div class="header-meta">Atenção / risco</div>
                <div style="font-size:1.5rem; font-weight:700;">{{ number_format($metrics->atRiskCompanies, 0, ',', '.') }}</div>
            </div>
            <div>
                <div class="header-meta">Críticas</div>
                <div style="font-size:1.5rem; font-weight:700;">{{ number_format($metrics->criticalCompanies, 0, ',', '.') }}</div>
            </div>
            <div>
                <div class="header-meta">Impersonações ativas</div>
                <div style="font-size:1.5rem; font-weight:700;">{{ number_format($metrics->activeImpersonations, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
@endsection
