@extends('layouts.sales-app')

@section('title', 'Minhas campanhas')

@section('content')
    <h1 class="page-title">Minhas campanhas</h1>
    <p class="page-sub">Somente campanhas atribuídas a você</p>

    @forelse($campaigns as $campaign)
        <a class="card card-link" href="{{ route('sales-app.campaigns.properties', $campaign) }}">
            <div class="list-title">{{ $campaign->name }}</div>
            <div class="list-meta">
                {{ $campaign->city?->name }}/{{ $campaign->city?->state }}
                · {{ $campaign->status?->label() }}
            </div>
            <div class="list-meta" style="margin-top:0.35rem;">
                {{ $campaign->start_date?->format('d/m/Y') ?: '—' }}
                —
                {{ $campaign->end_date?->format('d/m/Y') ?: '—' }}
                · Visitas: {{ $campaign->visits_count }}
            </div>
        </a>
    @empty
        <div class="card empty">Você ainda não foi atribuído a nenhuma campanha ativa.</div>
    @endforelse
@endsection
