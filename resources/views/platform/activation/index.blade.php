@extends('layouts.platform')

@section('title', 'SaaS Health')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; margin-bottom:1rem; flex-wrap:wrap;">
        <div>
            <h1 class="page-title" style="margin:0;" data-saas-health="1">SaaS Health</h1>
            <div class="header-meta">Inteligência de ativação e customer success</div>
        </div>
        <a class="btn btn-ghost" href="{{ route('platform.dashboard') }}">Voltar ao dashboard</a>
    </div>

    <div class="grid grid-2" style="margin-bottom:1rem;">
        <div class="card" data-saas-metric-card="activation">
            <div class="header-meta">Ativação</div>
            <div style="font-size:2rem; font-weight:700;">{{ number_format($health->activationRate, 0, ',', '.') }}%</div>
            <div class="header-meta">Clientes que completaram configuração inicial</div>
            <p style="margin:.75rem 0 0;">
                {{ number_format($health->activatedCompanies, 0, ',', '.') }}
                / {{ number_format($health->registeredCompanies, 0, ',', '.') }} empresas ativadas
            </p>
        </div>
        <div class="card" data-saas-metric-card="stuck">
            <div class="header-meta">Onboarding parado</div>
            <div style="font-size:2rem; font-weight:700;">{{ number_format($health->stuckCompaniesCount, 0, ',', '.') }} empresas</div>
            <div class="header-meta">Sem atividade há mais de 7 dias</div>
            <p style="margin:.75rem 0 0;">
                Em onboarding: {{ number_format($health->companiesInOnboarding, 0, ',', '.') }}
                · Tempo médio até ativação:
                {{ $health->averageActivationHours !== null ? number_format($health->averageActivationHours, 1, ',', '.').'h' : '—' }}
            </p>
        </div>
    </div>

    <div class="grid grid-2" style="margin-bottom:1rem;">
        <div class="card">
            <h2 style="margin-top:0;">Empresas paradas</h2>
            <table>
                <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Score</th>
                    <th>Última atividade</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($health->stuckCompanies as $item)
                    <tr>
                        <td>{{ $item->companyName }}</td>
                        <td>{{ $item->score }}/100 {{ $item->status->emoji() }}</td>
                        <td>{{ $item->lastActivityAt ? \Illuminate\Support\Carbon::parse($item->lastActivityAt)->diffForHumans() : '—' }}</td>
                        <td><a href="{{ route('platform.companies.show', $item->companyId) }}">Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4">Nenhuma empresa parada no momento.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2 style="margin-top:0;">Alertas de Customer Success</h2>
            <ul style="list-style:none; padding:0; margin:0; display:grid; gap:.65rem;" data-saas-cs-alerts="1">
                @forelse($health->alerts as $alert)
                    <li style="border:1px solid var(--border); border-radius:10px; padding:.65rem .8rem;">
                        <strong>{{ $alert['company_name'] }}</strong>
                        <div class="header-meta">{{ strtoupper($alert['severity']) }} · {{ $alert['code'] }}</div>
                        <div>{{ $alert['message'] }}</div>
                        <a href="{{ route('platform.companies.show', $alert['company_id']) }}">Abrir empresa</a>
                    </li>
                @empty
                    <li>Nenhum alerta no momento.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
