@extends('layouts.platform')

@section('title', 'Marketplace — Analytics')

@section('content')
    <div style="margin-bottom:1rem;">
        <a href="{{ route('platform.dashboard') }}" class="header-meta" style="text-decoration:none;">← Dashboard</a>
    </div>

    <div style="margin-bottom:1.1rem;">
        <h1 class="page-title" style="margin:0;">Analytics de Growth</h1>
        <div class="header-meta">Visitas, conversões e funil do marketplace público.</div>
    </div>

    <div class="grid grid-2" style="margin-bottom:1rem;">
        <div class="card">
            <div class="header-meta">Visitas hoje</div>
            <strong style="font-size:1.75rem;">{{ number_format($metrics->visitsToday, 0, ',', '.') }}</strong>
        </div>
        <div class="card">
            <div class="header-meta">Visitas na semana</div>
            <strong style="font-size:1.75rem;">{{ number_format($metrics->visitsWeek, 0, ',', '.') }}</strong>
        </div>
        <div class="card">
            <div class="header-meta">Visitas no mês</div>
            <strong style="font-size:1.75rem;">{{ number_format($metrics->visitsMonth, 0, ',', '.') }}</strong>
        </div>
        <div class="card">
            <div class="header-meta">Total de visitas</div>
            <strong style="font-size:1.75rem;">{{ number_format($metrics->totalVisits, 0, ',', '.') }}</strong>
        </div>
    </div>

    <div class="grid grid-2" style="margin-bottom:1rem;">
        <div class="card">
            <div class="header-meta">Visitantes únicos</div>
            <strong style="font-size:1.75rem;">{{ number_format($metrics->uniqueVisitors, 0, ',', '.') }}</strong>
        </div>
        <div class="card">
            <div class="header-meta">Leads capturados</div>
            <strong style="font-size:1.75rem;">{{ number_format($metrics->totalLeads, 0, ',', '.') }}</strong>
        </div>
        <div class="card">
            <div class="header-meta">Testes iniciados</div>
            <strong style="font-size:1.75rem;">{{ number_format($metrics->trialsStarted, 0, ',', '.') }}</strong>
        </div>
        <div class="card">
            <div class="header-meta">Clientes convertidos</div>
            <strong style="font-size:1.75rem;">{{ number_format($metrics->customersConverted, 0, ',', '.') }}</strong>
        </div>
    </div>

    <div class="grid grid-2" style="margin-bottom:1rem;">
        <div class="card">
            <div class="header-meta">Taxa lead / visitante</div>
            <strong style="font-size:1.75rem;">{{ number_format($metrics->conversionRate, 2, ',', '.') }}%</strong>
        </div>
        <div class="card">
            <div class="header-meta">Taxa teste / visitante</div>
            <strong style="font-size:1.75rem;">{{ number_format($metrics->signupConversion, 2, ',', '.') }}%</strong>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h2 style="margin-top:0;">Funil de conversão</h2>
            <table class="table">
                <thead>
                <tr>
                    <th>Etapa</th>
                    <th>Quantidade</th>
                    <th>Barra</th>
                </tr>
                </thead>
                <tbody>
                @php $maxFunnel = max(array_column($metrics->funnel, 'value')) ?: 1; @endphp
                @foreach($metrics->funnel as $step)
                    <tr>
                        <td>{{ $step['label'] }}</td>
                        <td>{{ number_format($step['value'], 0, ',', '.') }}</td>
                        <td>
                            <div style="background:var(--bg-soft);border-radius:999px;height:8px;overflow:hidden;">
                                <div style="width:{{ min(100, round(($step['value'] / $maxFunnel) * 100)) }}%;background:var(--accent);height:100%;"></div>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2 style="margin-top:0;">Principais fontes (UTM)</h2>
            <table class="table">
                <thead>
                <tr><th>Fonte</th><th>Eventos</th></tr>
                </thead>
                <tbody>
                @forelse($metrics->topSources as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ number_format($row['value'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">Sem dados de UTM ainda.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-2" style="margin-top:1rem;">
        <div class="card">
            <h2 style="margin-top:0;">Principais campanhas</h2>
            <table class="table">
                <thead>
                <tr><th>Campanha</th><th>Eventos</th></tr>
                </thead>
                <tbody>
                @forelse($metrics->topCampaigns as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ number_format($row['value'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">Sem campanhas registradas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2 style="margin-top:0;">Páginas mais visitadas</h2>
            <table class="table">
                <thead>
                <tr><th>URL</th><th>Visitas</th></tr>
                </thead>
                <tbody>
                @forelse($metrics->topPages as $row)
                    <tr>
                        <td style="word-break:break-all;font-size:0.88rem;">{{ $row['label'] }}</td>
                        <td>{{ number_format($row['value'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">Sem page views registrados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
