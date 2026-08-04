@extends('layouts.platform')

@section('title', 'Marketplace Intelligence')

@section('content')
    <div style="margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Marketplace Intelligence</h1>
        <div class="header-meta">Conversão, leads quentes e performance de campanhas.</div>
    </div>

    @if(count($metrics->hotLeads) > 0)
        <div class="card" style="margin-bottom:1rem; border-color: color-mix(in srgb, #F59E0B 45%, var(--border));">
            <strong>Novo lead quente</strong>
            <div class="header-meta" style="margin-top:0.35rem;">{{ $metrics->hotLeadsCount }} lead(s) com score ≥ 71</div>
            <ul style="margin:0.75rem 0 0; padding-left:1.1rem;">
                @foreach($metrics->hotLeads as $hot)
                    <li>
                        <strong>{{ $hot['name'] }}</strong>
                        — score {{ $hot['score'] }}
                        @if($hot['utm']) · UTM {{ $hot['utm'] }} @endif
                        @if($hot['source']) · {{ $hot['source'] }} @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-2" style="margin-bottom:1rem;">
        <div class="card"><div class="header-meta">Visitantes</div><strong style="font-size:1.6rem;">{{ $metrics->visitors }}</strong></div>
        <div class="card"><div class="header-meta">Leads</div><strong style="font-size:1.6rem;">{{ $metrics->leads }}</strong></div>
        <div class="card"><div class="header-meta">Conversão %</div><strong style="font-size:1.6rem;">{{ number_format($metrics->conversionRate, 2, ',', '.') }}%</strong></div>
        <div class="card"><div class="header-meta">Leads quentes</div><strong style="font-size:1.6rem;">{{ $metrics->hotLeadsCount }}</strong></div>
        <div class="card"><div class="header-meta">Demonstrações</div><strong style="font-size:1.6rem;">{{ $metrics->demosRequested }}</strong></div>
        <div class="card"><div class="header-meta">Trials iniciados</div><strong style="font-size:1.6rem;">{{ $metrics->trialsStarted }}</strong></div>
        <div class="card"><div class="header-meta">Clientes convertidos</div><strong style="font-size:1.6rem;">{{ $metrics->customersConverted }}</strong></div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <h2 style="margin-top:0;">Funil</h2>
        <div class="grid grid-2">
            @foreach($metrics->funnel as $step)
                <div>
                    <div class="header-meta">{{ $step['label'] }}</div>
                    <strong>{{ $step['value'] }}</strong>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">Analytics de campanhas</h2>
        <table class="table">
            <thead>
            <tr>
                <th>Campanha</th>
                <th>Investimento</th>
                <th>Leads</th>
                <th>Clientes</th>
                <th>CAC</th>
                <th>ROI</th>
            </tr>
            </thead>
            <tbody>
            @forelse($metrics->campaigns as $row)
                <tr>
                    <td>{{ $row['campaign'] }}</td>
                    <td>R$ {{ number_format($row['investment'], 2, ',', '.') }}</td>
                    <td>{{ $row['leads'] }}</td>
                    <td>{{ $row['customers'] }}</td>
                    <td>{{ $row['cac'] !== null ? 'R$ '.number_format($row['cac'], 2, ',', '.') : '—' }}</td>
                    <td>{{ $row['roi'] !== null ? number_format($row['roi'], 1, ',', '.').'%' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Nenhuma campanha cadastrada.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
