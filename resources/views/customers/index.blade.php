@extends('layouts.operational')

@section('title', 'Clientes')

@section('page')
    <x-client.page-breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Operação'],
        ['label' => 'Clientes'],
    ]" />

    <x-client.page-header title="Clientes" description="CRM comercial — histórico, vendas e retornos em um só lugar.">
        <x-client.secondary-button :href="route('map.index')">Abrir mapa</x-client.secondary-button>
    </x-client.page-header>

    <x-client.crud-toolbar>
        <x-slot:search>
            <form method="GET" action="{{ route('customers.index') }}" style="display:flex; gap:.65rem; flex-wrap:wrap; width:100%;">
                <label class="sr-only" for="customer-q">Buscar clientes</label>
                <input id="customer-q" class="form-control" type="search" name="q" value="{{ $q }}"
                       placeholder="Nome, telefone, CPF, rua, bairro, cidade ou produto…"
                       style="flex:1; min-width:220px;">
                <button class="btn btn-primary client-btn" type="submit">Buscar</button>
                @if($q !== '')
                    <a class="btn btn-ghost client-btn" href="{{ route('customers.index') }}">Limpar</a>
                @endif
            </form>
        </x-slot:search>
    </x-client.crud-toolbar>

    @if($customers->isEmpty())
        <div class="card">
            <div class="empty-friendly">
                <div style="font-weight:700; font-size:1.05rem;">
                    {{ $q !== '' ? 'Nenhum cliente encontrado' : 'Ainda não há clientes' }}
                </div>
                <p>
                    {{ $q !== ''
                        ? 'Tente outro termo ou limpe a busca.'
                        : 'Registre atendimentos no mapa — cada residência visitada aparece aqui.' }}
                </p>
                <a class="btn btn-primary" href="{{ route('map.index') }}">Ir para o mapa</a>
            </div>
        </div>
    @else
        <div class="customer-grid" style="display:grid; gap:.85rem; grid-template-columns:repeat(auto-fill,minmax(280px,1fr));">
            @foreach($customers as $card)
                <a href="{{ $card['show_url'] }}" class="card customer-card"
                   style="padding:1rem 1.05rem; text-decoration:none; color:inherit; display:block; transition:.15s ease;">
                    <div style="display:flex; justify-content:space-between; gap:.5rem; align-items:flex-start; margin-bottom:.45rem;">
                        <h2 style="margin:0; font-size:1.05rem; font-weight:700; line-height:1.3;">{{ $card['name'] }}</h2>
                        <span class="badge" style="white-space:nowrap;">{{ $card['situation'] }}</span>
                    </div>
                    @if($card['phone'] || $card['whatsapp'])
                        <p class="header-meta" style="margin:0 0 .25rem;">
                            {{ $card['phone'] ?: $card['whatsapp'] }}
                            @if($card['whatsapp'] && $card['phone'] && $card['whatsapp'] !== $card['phone'])
                                · WhatsApp {{ $card['whatsapp'] }}
                            @endif
                        </p>
                    @endif
                    <p class="header-meta" style="margin:0 0 .2rem;">{{ $card['address'] }}</p>
                    @if($card['neighborhood'] || $card['city'])
                        <p class="header-meta" style="margin:0 0 .45rem;">
                            {{ implode(' · ', array_filter([$card['neighborhood'], $card['city']])) }}
                        </p>
                    @endif
                    <div style="display:flex; justify-content:space-between; gap:.5rem; flex-wrap:wrap; margin-top:.55rem; font-size:.82rem; color:#94a3b8;">
                        <span>Última visita: <strong style="color:#cbd5e1; font-weight:600;">{{ $card['last_visit_at'] ?: '—' }}</strong></span>
                        <span>{{ $card['seller'] }}</span>
                    </div>
                </a>
            @endforeach
        </div>
        <x-client.pagination-bar :paginator="$paginator" />
    @endif

    <style>
        .customer-card:hover { border-color: rgba(56,189,248,.45); background:#0f172a; }
    </style>
@endsection
