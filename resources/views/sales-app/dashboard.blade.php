@extends('layouts.sales-app')

@section('title', 'Campo')

@section('content')
    <h1 class="page-title">Olá, {{ auth()->user()?->name }}</h1>
    <p class="page-sub">Operação em campo — mobile first</p>

    <div class="stats">
        <div class="stat">
            <div class="stat-label">Campanhas</div>
            <div class="stat-value">{{ $stats['active_campaigns'] }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Visitas hoje</div>
            <div class="stat-value">{{ $stats['visits_today'] }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Retornos</div>
            <div class="stat-value">{{ $stats['pending_follow_ups'] }}</div>
        </div>
    </div>

    <div class="btn-row" style="margin-bottom:1rem;">
        <a class="btn btn-primary" href="{{ route('sales-app.campaigns.index') }}">Minhas campanhas</a>
        <a class="btn btn-ghost" href="{{ route('sales-app.follow-ups.index') }}">Retornos pendentes</a>
    </div>

    <h2 style="font-size:1rem; margin:0 0 0.65rem;">Campanhas recentes</h2>
    @forelse($campaigns as $campaign)
        <a class="card card-link" href="{{ route('sales-app.campaigns.properties', $campaign) }}">
            <div class="list-title">{{ $campaign->name }}</div>
            <div class="list-meta">
                {{ $campaign->city?->name }}/{{ $campaign->city?->state }}
                · <span class="badge {{ $campaign->status?->value === 'active' ? 'badge-success' : 'badge-warning' }}">
                    {{ $campaign->status?->label() }}
                </span>
            </div>
            <div class="list-meta" style="margin-top:0.35rem;">Suas visitas: {{ $campaign->visits_count }}</div>
        </a>
    @empty
        <div class="card empty">Nenhuma campanha atribuída.</div>
    @endforelse

    <div style="margin-top:1rem;">
        <a class="btn btn-ghost" href="{{ route('dashboard') }}">Abrir painel completo</a>
    </div>
@endsection
