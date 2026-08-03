@extends('layouts.sales-app')

@section('title', 'Clientes da campanha')

@section('content')
    <h1 class="page-title">{{ $campaign->name }}</h1>
    <p class="page-sub">
        {{ $campaign->city?->name }}/{{ $campaign->city?->state }}
        · {{ $campaign->status?->label() }}
    </p>

    <div class="btn-row" style="margin-bottom:1rem;">
        <a class="btn btn-ghost" href="{{ route('sales-app.campaigns.index') }}">Voltar</a>
        @if($authUser?->hasPermission('maps.view'))
            <a class="btn btn-ghost" href="{{ route('map.index') }}">Abrir mapa</a>
        @endif
    </div>

    @forelse($properties as $property)
        <div class="card">
            <div class="list-title">{{ $property->address?->label() ?: 'Cliente #'.$property->id }}</div>
            <div class="list-meta">
                Status: {{ $property->status?->label() }}
                · Abordagens na campanha: {{ $property->campaign_visits_count }}
            </div>
            @if($property->residents->isNotEmpty())
                <div class="list-meta" style="margin-top:0.35rem;">
                    Contato: {{ $property->residents->first()->name }}
                    @if($property->residents->first()->phone)
                        · {{ $property->residents->first()->phone }}
                    @endif
                </div>
            @endif
            <div class="btn-row">
                <a class="btn btn-primary"
                   href="{{ route('sales-app.campaigns.visits.create', [$campaign, $property]) }}">
                    Registrar abordagem
                </a>
            </div>
        </div>
    @empty
        <div class="card empty">Nenhum cliente disponível nesta campanha.</div>
    @endforelse

    <div style="margin-top:0.5rem;">{{ $properties->links() }}</div>
@endsection
