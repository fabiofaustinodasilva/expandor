@extends('layouts.operational')

@section('title', 'Relatórios')

@section('page')
@php
    $reportsUser = auth()->user();
@endphp

<x-client.page-breadcrumb :items="[
    ['label' => 'Dashboard', 'href' => route('dashboard')],
    ['label' => 'Relatórios'],
]" />

<x-client.page-header
    title="Relatórios"
    description="A análise detalhada (funil, gráficos e desempenho por região) foi organizada aqui, fora do painel principal."
>
    <x-client.secondary-button :href="route('dashboard')">Voltar ao dashboard</x-client.secondary-button>
</x-client.page-header>

<div class="grid grid-3">
    @if($reportsUser && \App\Support\ClientArea\NavVisibility::can($reportsUser, 'dashboard'))
        <x-client.section-card title="Resultados comerciais">
            <p class="header-meta" style="margin:0 0 .85rem;">Funil, produtividade e ranking de vendedores dos últimos 30 dias.</p>
            <x-client.primary-button :href="route('dashboard', ['period' => '30d'])">Ver resultados (30d)</x-client.primary-button>
        </x-client.section-card>
    @endif

    @if($reportsUser && \App\Support\ClientArea\NavVisibility::can($reportsUser, 'commissions'))
        <x-client.section-card title="Comissões">
            <p class="header-meta" style="margin:0 0 .85rem;">Aprovações, pagamentos e histórico de comissões da equipe.</p>
            <x-client.primary-button :href="route('commissions.index')">Abrir comissões</x-client.primary-button>
        </x-client.section-card>
    @endif

    @if($reportsUser && \App\Support\ClientArea\NavVisibility::can($reportsUser, 'crm'))
        <x-client.section-card title="CRM / Oportunidades">
            <p class="header-meta" style="margin:0 0 .85rem;">Pipeline de oportunidades, leads e metas comerciais.</p>
            <x-client.primary-button :href="route('crm.dashboard')">Abrir CRM</x-client.primary-button>
        </x-client.section-card>
    @endif

    @if($reportsUser && \App\Support\ClientArea\NavVisibility::can($reportsUser, 'team'))
        <x-client.section-card title="Equipe">
            <p class="header-meta" style="margin:0 0 .85rem;">Desempenho individual, visitas e vendedores ativos.</p>
            <x-client.primary-button :href="route('operations.team')">Abrir equipe</x-client.primary-button>
        </x-client.section-card>
    @endif
</div>
@endsection
