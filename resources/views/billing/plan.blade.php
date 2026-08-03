@extends('layouts.app')

@section('title', 'Plano e uso')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <div>
            <h1 class="page-title" style="margin:0;">Plano e consumo</h1>
            <div class="header-meta">Período {{ $overview->period }} · {{ $company->name }}</div>
        </div>
        @if(auth()->user()?->hasPermission('company.manage'))
            <a class="btn btn-ghost" href="{{ route('company.show', $company) }}">Dados da empresa</a>
        @endif
    </div>

    <div class="grid grid-2" style="margin-bottom:1rem;">
        <div class="card">
            <h2 style="margin-top:0;">Plano atual</h2>
            @if($overview->plan)
                <p><strong>Nome:</strong> {{ $overview->plan->name }}</p>
                <p><strong>Preço:</strong>
                    @if((float) $overview->plan->price <= 0)
                        Gratuito
                    @else
                        R$ {{ number_format((float) $overview->plan->price, 2, ',', '.') }}/mês
                    @endif
                </p>
                <p><strong>Status da assinatura:</strong> {{ $overview->subscription?->status ?? '—' }}</p>
                <p><strong>Início:</strong> {{ optional($overview->subscription?->starts_at)->format('d/m/Y') ?: '—' }}</p>
                <p class="header-meta">{{ $overview->plan->description }}</p>
            @else
                <p>Nenhum plano ativo encontrado.</p>
            @endif
        </div>

        <div class="card">
            <h2 style="margin-top:0;">Recursos do plano</h2>
            @forelse($overview->features as $key => $value)
                <p>
                    <strong>{{ $key }}:</strong>
                    {{ $value === null || $value === 'unlimited' ? 'Ilimitado' : $value }}
                </p>
            @empty
                <p>Sem features cadastradas.</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">Limites e consumo</h2>
        <table class="table">
            <thead>
            <tr>
                <th>Métrica</th>
                <th>Consumo</th>
                <th>Limite</th>
                <th>Restante</th>
                <th>Uso</th>
                <th>Registrado no período</th>
            </tr>
            </thead>
            <tbody>
            @foreach($overview->metrics as $metric)
                <tr>
                    <td>{{ $metric->metric->label() }}</td>
                    <td>{{ number_format($metric->current, 0, ',', '.') }}</td>
                    <td>{{ $metric->isUnlimited() ? 'Ilimitado' : number_format($metric->limit, 0, ',', '.') }}</td>
                    <td>{{ $metric->isUnlimited() ? '—' : number_format($metric->remaining() ?? 0, 0, ',', '.') }}</td>
                    <td>
                        @if($metric->usagePercent() === null)
                            —
                        @else
                            <span class="badge">{{ $metric->usagePercent() }}%</span>
                        @endif
                    </td>
                    <td>{{ number_format($metric->recorded, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
