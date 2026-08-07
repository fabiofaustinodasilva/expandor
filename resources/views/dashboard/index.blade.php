@extends('layouts.operational')

@section('title', 'Dashboard')

@section('page')
@php
    $teamView = $metrics->team_view;
    $productivity = $metrics->productivity;
@endphp

<x-client.page-header
    title="Dashboard"
    description="{{ $company->name }} · {{ $teamView ? 'painel comercial do gestor' : 'seus resultados comerciais' }} · {{ $plan_name }}"
>
    <x-client.quick-actions>
        <x-client.secondary-button :href="route('dashboard', array_filter(['period' => 'today', 'user_id' => $isSeller ? null : $filters->user_id]))" class="{{ ($period ?? '') === 'today' ? 'btn-primary' : '' }}">Hoje</x-client.secondary-button>
        <x-client.secondary-button :href="route('dashboard', array_filter(['period' => '7d', 'user_id' => $isSeller ? null : $filters->user_id]))" class="{{ ($period ?? '') === '7d' ? 'btn-primary' : '' }}">Últimos 7 dias</x-client.secondary-button>
        <x-client.secondary-button :href="route('dashboard', array_filter(['period' => '30d', 'user_id' => $isSeller ? null : $filters->user_id]))" class="{{ ($period ?? '') === '30d' ? 'btn-primary' : '' }}">Últimos 30 dias</x-client.secondary-button>
        <x-client.secondary-button :href="route('map.index')">Abrir mapa</x-client.secondary-button>
        @if(!empty($canViewCommissions))
            <x-client.secondary-button :href="$commissionsUrl">{{ $isSeller ? 'Minha comissão' : 'Comissões' }}</x-client.secondary-button>
        @endif
        <x-client.primary-button :href="route('reports.index')">Ver relatórios</x-client.primary-button>
    </x-client.quick-actions>
</x-client.page-header>

@if(isset($onboarding) && $onboarding && ! $onboarding->isCompleted && empty($saasWorkspaceReady))
    <x-client.section-card title="Setup">
        <div style="display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; align-items:center;">
            <div class="header-meta">{{ $onboarding->percent }}% concluído</div>
            @if(auth()->user()?->hasPermission('onboarding.manage'))
                <x-client.primary-button :href="route('setup.show')">Continuar setup</x-client.primary-button>
            @endif
        </div>
        <div style="margin-top:0.75rem; background:var(--bg-soft); border-radius:999px; overflow:hidden; height:12px;">
            <div style="width:{{ $onboarding->percent }}%; height:100%; background:var(--accent);"></div>
        </div>
        @if($onboarding->alerts)
            <ul style="margin:0.85rem 0 0; padding-left:1.1rem; color:var(--warning);">
                @foreach($onboarding->alerts as $alert)
                    <li>{{ $alert }}</li>
                @endforeach
            </ul>
        @endif
    </x-client.section-card>
@endif

@include('onboarding.partials.activation-card')
@include('onboarding.partials.activation-guidance')

<details class="client-filters-collapsible">
    <summary>Filtros</summary>
    <div class="client-filters-collapsible__body">
        <form method="GET" action="{{ route('dashboard') }}" class="grid grid-4" style="align-items:end;">
            <input type="hidden" name="period" value="{{ $period }}">
            <div class="form-group" style="margin:0;">
                <label for="date_from">De</label>
                <input class="form-control" type="date" id="date_from" name="date_from" value="{{ $filters->date_from }}">
            </div>
            <div class="form-group" style="margin:0;">
                <label for="date_to">Até</label>
                <input class="form-control" type="date" id="date_to" name="date_to" value="{{ $filters->date_to }}">
            </div>
            <div class="form-group" style="margin:0;">
                <label for="city_id">Cidade</label>
                <select class="form-control" id="city_id" name="city_id">
                    <option value="">Todas</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}" @selected((string) $filters->city_id === (string) $city->id)>
                            {{ $city->name }}/{{ $city->state }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label for="sector_id">Setor</label>
                <select class="form-control" id="sector_id" name="sector_id">
                    <option value="">Todos</option>
                    @foreach($sectors as $sector)
                        <option value="{{ $sector->id }}" data-city-id="{{ $sector->city_id }}"
                            @selected((string) $filters->sector_id === (string) $sector->id)>
                            {{ $sector->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if($teamView)
                <div class="form-group" style="margin:0;">
                    <label for="user_id">Vendedor</label>
                    <select class="form-control" id="user_id" name="user_id">
                        <option value="">Todos</option>
                        @foreach($sellers as $seller)
                            <option value="{{ $seller['id'] }}" @selected((string) $filters->user_id === (string) $seller['id'])>
                                {{ $seller['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="actions" style="grid-column: 1 / -1;">
                <button class="btn btn-primary client-btn" type="submit">Aplicar filtros</button>
                <a class="btn btn-ghost client-btn" href="{{ route('dashboard') }}">Limpar</a>
            </div>
        </form>
    </div>
</details>

<div class="grid grid-4" style="margin-bottom:1rem;">
    <x-client.metric-card icon="footprints" label="Visitas" :value="$metrics->visits_total" hint="Visitas no período" />
    <x-client.metric-card icon="badge-check" label="{{ $commercial::salesWon() }}" :value="$metrics->installations_total" hint="{{ $commercial::salesRequestedMeta() }}" />
    <x-client.metric-card icon="percent" label="Conversão" value="{{ number_format($metrics->conversion_rate, 1, ',', '.') }}%" hint="{{ $commercial::salesPerVisits() }}" />
    <x-client.metric-card icon="clock" label="Retornos pendentes" :value="$metrics->pending_follow_ups" hint="Agenda a cumprir" />
</div>

<div class="grid grid-{{ $teamView ? '2' : '1' }}" style="margin-bottom:1rem;">
    <x-client.metric-card icon="users" label="Instalações / vendas pendentes" :value="$metrics->interested_total" hint="Interessados aguardando fechamento" />
    @if($teamView)
        <x-client.metric-card icon="award" label="Vendedores ativos" :value="$productivity['active_sellers']" hint="Com atividade no período" />
    @endif
</div>

@if($teamView)
    <x-client.section-card title="Ranking de vendedores" description="Top desempenho no período selecionado.">
        @if(count($metrics->seller_ranking) > 0)
            <x-client.data-table :headers="['Vendedor', 'Visitas', $commercial::sales(), 'Conversão %']">
                @foreach(array_slice($metrics->seller_ranking, 0, 5) as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['visits'] }}</td>
                        <td>{{ $row['installations'] }}</td>
                        <td>{{ number_format($row['conversion_rate'] ?? 0, 1, ',', '.') }}%</td>
                    </tr>
                @endforeach
            </x-client.data-table>
        @else
            <x-client.empty-state title="Sem dados de ranking ainda" icon="trophy" />
        @endif
    </x-client.section-card>
@endif

<p class="header-meta" style="margin-top:1rem;">
    Análises detalhadas (funil, gráficos e desempenho por região) foram movidas para
    <a href="{{ route('reports.index') }}">Relatórios</a>.
</p>
@endsection
