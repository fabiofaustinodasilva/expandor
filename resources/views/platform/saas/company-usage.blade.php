@extends('layouts.platform')

@section('title', 'Uso do plano')

@section('content')
    <div style="margin-bottom:1rem;">
        <a href="{{ route('platform.companies.show', $company) }}" class="header-meta" style="text-decoration:none;">← {{ $company->name }}</a>
        <h1 class="page-title" style="margin:0.35rem 0 0;">Uso do plano</h1>
        <div class="header-meta">Plano: {{ $snapshot->planName ?: '—' }}</div>
    </div>

    @if(count($alerts) > 0)
        <div class="card" style="margin-bottom:1rem;">
            <strong>Alertas de limite</strong>
            <ul>
                @foreach($alerts as $alert)
                    <li>{{ $alert['message'] }} ({{ $alert['metric'] }} · {{ number_format($alert['percent'], 0) }}%)</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(count($recommendations) > 0)
        <div class="card" style="margin-bottom:1rem;">
            <strong>Recomendações de upgrade</strong>
            <ul>
                @foreach($recommendations as $rec)
                    <li>{{ $rec['message'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <table class="table">
            <thead>
            <tr><th>Métrica</th><th>Uso</th><th>Limite</th><th>%</th></tr>
            </thead>
            <tbody>
            @foreach($snapshot->metrics as $key => $row)
                <tr>
                    <td>{{ $key }}</td>
                    <td>{{ $row['value'] }}</td>
                    <td>{{ $row['limit'] ?? '∞' }}</td>
                    <td>{{ $row['percent'] !== null ? number_format($row['percent'], 1, ',', '.').'%' : '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
