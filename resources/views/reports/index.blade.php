@extends('layouts.operational')

@section('title', 'Atalhos de análise')

@section('page')
@php
    $reportsUser = auth()->user();
@endphp

<x-client.page-breadcrumb :items="[
    ['label' => 'Dashboard', 'href' => route('dashboard')],
    ['label' => 'Atalhos de análise'],
]" />

<x-client.page-header
    title="Atalhos de análise"
    description="Atalhos para os painéis e módulos onde a análise acontece hoje. Relatórios gráficos dedicados ainda não estão nesta tela."
>
    <x-client.secondary-button :href="route('dashboard')">Voltar ao dashboard</x-client.secondary-button>
</x-client.page-header>

<x-client.alert type="info" style="margin-bottom:1rem;">
    Esta página organiza o acesso rápido. Funil, gráficos e exportações detalhadas serão adicionados em uma sprint futura — não há análise embutida aqui.
</x-client.alert>

<div class="grid grid-3">
    @if($reportsUser && \App\Support\ClientArea\NavVisibility::can($reportsUser, 'dashboard'))
        <x-client.section-card title="Resultados comerciais">
            <p class="header-meta" style="margin:0 0 .85rem;">KPIs e ranking de vendedores no Dashboard (últimos 30 dias).</p>
            <x-client.primary-button :href="route('dashboard', ['period' => '30d'])">Abrir dashboard (30d)</x-client.primary-button>
        </x-client.section-card>
    @endif

    @if($reportsUser && \App\Support\ClientArea\NavVisibility::can($reportsUser, 'commissions'))
        <x-client.section-card title="Financeiro">
            <p class="header-meta" style="margin:0 0 .85rem;">Aprovações, pagamentos e histórico de comissões da equipe.</p>
            <x-client.primary-button :href="route('commissions.index')">Abrir financeiro</x-client.primary-button>
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
            <p class="header-meta" style="margin:0 0 .85rem;">Membros, funções e desempenho operacional.</p>
            <x-client.primary-button :href="route('operations.team')">Abrir equipe</x-client.primary-button>
        </x-client.section-card>
    @endif
</div>
@endsection
