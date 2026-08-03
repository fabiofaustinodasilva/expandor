@extends('layouts.platform')

@section('title', 'Health Score')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; margin-bottom:1rem; flex-wrap:wrap;">
        <div>
            <h1 class="page-title" style="margin:0;">Customer Health Score</h1>
            <div class="header-meta">Saúde operacional dos clientes</div>
        </div>
        <form method="POST" action="{{ route('platform.health.recalculate-all') }}">
            @csrf
            <button class="btn btn-primary" type="submit">Recalcular todos</button>
        </form>
    </div>

    <div class="grid grid-2" style="margin-bottom:1rem;">
        <div class="card"><div class="header-meta">Média</div><div style="font-size:1.8rem; font-weight:700;">{{ $aggregate['average'] !== null ? number_format($aggregate['average'], 1, ',', '.') : '—' }}</div></div>
        <div class="card"><div class="header-meta">Saudáveis</div><div style="font-size:1.8rem; font-weight:700;">{{ $aggregate['healthy'] }}</div></div>
        <div class="card"><div class="header-meta">Atenção</div><div style="font-size:1.8rem; font-weight:700;">{{ $aggregate['medium'] }}</div></div>
        <div class="card"><div class="header-meta">Em risco / críticos</div><div style="font-size:1.8rem; font-weight:700;">{{ $aggregate['at_risk'] + $aggregate['critical'] }}</div></div>
    </div>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Empresa</th>
                <th>Score</th>
                <th>Risco</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($companies as $company)
                @php $health = $company->getRelation('healthScore'); @endphp
                <tr>
                    <td><a href="{{ route('platform.companies.show', $company) }}">{{ $company->name }}</a></td>
                    <td>{{ $health?->score ?? '—' }}</td>
                    <td>{{ $health?->risk_level->value ?? '—' }}</td>
                    <td>
                        <form method="POST" action="{{ route('platform.health.recalculate', $company) }}">
                            @csrf
                            <button class="btn btn-ghost" type="submit">Recalcular</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
