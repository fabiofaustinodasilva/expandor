@extends('layouts.platform')

@section('title', 'Empresas clientes')

@section('content')
    <x-ux.page-header
        title="Empresas clientes"
        description="Gestão de status, assinaturas e saúde das empresas."
        :breadcrumbs="[
            ['label' => 'Platform', 'href' => route('platform.dashboard')],
            ['label' => 'Empresas'],
        ]"
    >
        @can('platform.manageCompanies')
            <a class="btn btn-primary" href="{{ route('platform.companies.create') }}">Nova empresa</a>
        @endcan
    </x-ux.page-header>

    <form method="GET" class="card filter-bar">
        <div class="form-group" style="margin:0;">
            <label for="filter-q">Buscar</label>
            <input class="form-control" id="filter-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nome, e-mail, WhatsApp ou documento">
        </div>
        <div class="form-group" style="margin:0;">
            <label for="filter-status">Status</label>
            <select class="form-control" id="filter-status" name="status">
                <option value="">Todos</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Ativa</option>
                <option value="suspended" @selected(($filters['status'] ?? '') === 'suspended')>Suspensa</option>
                <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Cancelada</option>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label for="filter-sub">Assinatura</label>
            <select class="form-control" id="filter-sub" name="subscription_status">
                <option value="">Todas</option>
                <option value="trial" @selected(($filters['subscription_status'] ?? '') === 'trial')>Teste</option>
                <option value="active" @selected(($filters['subscription_status'] ?? '') === 'active')>Ativa</option>
                <option value="past_due" @selected(($filters['subscription_status'] ?? '') === 'past_due')>Inadimplente</option>
                <option value="cancelled" @selected(($filters['subscription_status'] ?? '') === 'cancelled')>Cancelada</option>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label for="filter-archived">Visibilidade</label>
            <select class="form-control" id="filter-archived" name="archived">
                <option value="" @selected(($filters['archived'] ?? '') === '')>Ativas no sistema</option>
                <option value="1" @selected(($filters['archived'] ?? '') === '1')>Excluídas</option>
            </select>
        </div>
        <button class="btn btn-primary" type="submit">Filtrar</button>
    </form>

    <div class="card">
        <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th>Empresa</th>
                <th>Responsável / WhatsApp</th>
                <th>Plano</th>
                <th>Assinatura</th>
                <th>Trial até</th>
                <th>Cadastro</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            @forelse($companies as $company)
                @php
                    $subscription = $company->subscriptions->first();
                    $health = $company->getRelation('healthScore');
                    $admin = $company->users->first();
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('platform.companies.show', $company) }}"><strong>{{ $company->name }}</strong></a>
                        @if($company->email)
                            <div class="header-meta">{{ $company->email }}</div>
                        @endif
                    </td>
                    <td>
                        {{ $admin?->name ?? '—' }}
                        <div class="header-meta">{{ $company->whatsapp ?: ($admin?->whatsapp ?: '—') }}</div>
                    </td>
                    <td>{{ $subscription?->plan?->name ?? '—' }}</td>
                    <td><span class="badge">{{ $subscription?->status ?? '—' }}</span></td>
                    <td>{{ optional($subscription?->trial_ends_at)->format('d/m/Y H:i') ?: '—' }}</td>
                    <td>{{ $company->created_at?->format('d/m/Y') }}</td>
                    <td>
                        <span class="badge">{{ $company->status }}</span>
                        @if($company->trashed())
                            <div class="header-meta">excluída</div>
                        @endif
                        @if($health)
                            <div class="header-meta">Health {{ $health->score }}</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Nenhuma empresa encontrada.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        <div style="margin-top:1rem;">{{ $companies->withQueryString()->links() }}</div>
    </div>
@endsection
