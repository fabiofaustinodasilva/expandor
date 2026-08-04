@extends('layouts.platform')

@section('title', 'SaaS Intelligence')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1rem;">
        <div>
            <h1 class="page-title" style="margin:0;">SaaS Intelligence</h1>
            <div class="header-meta">Crescimento, trials, saúde e MRR estimado da plataforma.</div>
        </div>
        <form method="POST" action="{{ route('platform.saas.intelligence.recalculate-health') }}">
            @csrf
            <button class="btn btn-ghost" type="submit">Recalcular health</button>
        </form>
    </div>

    <div class="grid grid-2" style="margin-bottom:1rem;">
        <div class="card"><div class="header-meta">Total empresas</div><strong style="font-size:1.6rem;">{{ $metrics->totalCompanies }}</strong></div>
        <div class="card"><div class="header-meta">Empresas ativas</div><strong style="font-size:1.6rem;">{{ $metrics->activeCompanies }}</strong></div>
        <div class="card"><div class="header-meta">Trials ativos</div><strong style="font-size:1.6rem;">{{ $metrics->activeTrials }}</strong></div>
        <div class="card"><div class="header-meta">Trials convertidos</div><strong style="font-size:1.6rem;">{{ $metrics->convertedTrials }}</strong></div>
        <div class="card"><div class="header-meta">Taxa conversão trial</div><strong style="font-size:1.6rem;">{{ number_format($metrics->trialConversionRate, 1, ',', '.') }}%</strong></div>
        <div class="card"><div class="header-meta">Health médio</div><strong style="font-size:1.6rem;">{{ number_format($metrics->averageHealth, 1, ',', '.') }}</strong></div>
        <div class="card"><div class="header-meta">Empresas em risco</div><strong style="font-size:1.6rem;">{{ $metrics->companiesAtRisk }}</strong></div>
        <div class="card"><div class="header-meta">MRR estimado</div><strong style="font-size:1.6rem;">R$ {{ number_format($metrics->estimatedMrr, 2, ',', '.') }}</strong></div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h2 style="margin-top:0;">Empresas em risco</h2>
            <table class="table">
                <thead><tr><th>Empresa</th><th>Score</th><th>Classificação</th></tr></thead>
                <tbody>
                @forelse($metrics->atRisk as $row)
                    <tr>
                        <td><a href="{{ route('platform.companies.show', $row['company_id']) }}">{{ $row['name'] }}</a></td>
                        <td>{{ $row['score'] }}</td>
                        <td>{{ $row['classification'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Nenhuma empresa em risco.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card">
            <h2 style="margin-top:0;">Upgrade recomendado</h2>
            <ul style="margin:0; padding-left:1.1rem;">
                @forelse($metrics->upgradeHints as $hint)
                    <li style="margin-bottom:0.55rem;">
                        <strong>{{ $hint['name'] }}</strong>
                        <div class="header-meta">{{ $hint['message'] }}</div>
                    </li>
                @empty
                    <li>Sem recomendações no momento.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
