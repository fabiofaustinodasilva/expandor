@extends('layouts.app')

@section('title', 'CRM Comercial')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem; flex-wrap:wrap;">
        <div>
            <h1 class="page-title" style="margin:0;">CRM Comercial</h1>
            <div class="header-meta">Leads, pipeline, metas e conversão</div>
        </div>
        <form method="GET" class="actions">
            <input type="date" name="date_from" value="{{ $filters['date_from'] }}">
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}">
            <button class="btn btn-ghost" type="submit">Filtrar</button>
        </form>
    </div>

    <div class="stats-grid" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:1rem; margin-bottom:1rem;">
        <div class="card"><div class="header-meta">Leads</div><div class="stat-value">{{ $metrics->leads_total }}</div></div>
        <div class="card"><div class="header-meta">Conv. leads</div><div class="stat-value">{{ number_format($metrics->lead_conversion_rate, 1, ',', '.') }}%</div></div>
        <div class="card"><div class="header-meta">Pipeline aberto</div><div class="stat-value">R$ {{ number_format($metrics->pipeline_amount, 2, ',', '.') }}</div></div>
        <div class="card"><div class="header-meta">Ganhas</div><div class="stat-value">{{ $metrics->opportunities_won }}</div></div>
        <div class="card"><div class="header-meta">Win rate</div><div class="stat-value">{{ number_format($metrics->opportunity_win_rate, 1, ',', '.') }}%</div></div>
        <div class="card"><div class="header-meta">{{ $commercial::salesAnalytics() }}</div><div class="stat-value">{{ $metrics->visit_installations }}</div></div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <h2 style="margin-top:0;">Ranking de vendedores</h2>
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>Vendedor</th>
                <th>Ganhas</th>
                <th>Valor</th>
                <th>Meta</th>
                <th>Progresso</th>
            </tr>
            </thead>
            <tbody>
            @forelse($ranking as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['won_count'] }}</td>
                    <td>R$ {{ number_format($row['won_amount'], 2, ',', '.') }}</td>
                    <td>R$ {{ number_format($row['goal_amount'], 2, ',', '.') }}</td>
                    <td>{{ number_format($row['goal_progress'], 1, ',', '.') }}%</td>
                </tr>
            @empty
                <tr><td colspan="6">Sem vendas no período.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="actions">
        <a class="btn btn-primary" href="{{ route('crm.opportunities.kanban') }}">Kanban</a>
        <a class="btn btn-ghost" href="{{ route('crm.leads.index') }}">Leads</a>
        <a class="btn btn-ghost" href="{{ route('crm.goals.index') }}">Metas</a>
        <a class="btn btn-ghost" href="{{ route('crm.commissions.index') }}">Comissões</a>
    </div>
@endsection
