@extends('layouts.platform')

@section('title', 'Empresas clientes')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem; flex-wrap:wrap;">
        <div>
            <h1 class="page-title" style="margin:0;">Empresas clientes</h1>
            <div class="header-meta">Gestão avançada — trials, status e health</div>
        </div>
        @can('platform.manageCompanies')
            <a class="btn btn-primary" href="{{ route('platform.companies.create') }}">Nova empresa</a>
        @endcan
    </div>

    <form method="GET" class="card" style="margin-bottom:1rem; display:grid; gap:0.75rem; grid-template-columns:2fr 1fr 1fr 1fr auto;">
        <input class="form-control" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar nome, e-mail, WhatsApp ou documento">
        <select class="form-control" name="status">
            <option value="">Status empresa</option>
            <option value="active" @selected(($filters['status'] ?? '') === 'active')>active</option>
            <option value="suspended" @selected(($filters['status'] ?? '') === 'suspended')>suspended</option>
            <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>cancelled</option>
        </select>
        <select class="form-control" name="subscription_status">
            <option value="">Assinatura</option>
            <option value="trial" @selected(($filters['subscription_status'] ?? '') === 'trial')>trial</option>
            <option value="active" @selected(($filters['subscription_status'] ?? '') === 'active')>active</option>
            <option value="past_due" @selected(($filters['subscription_status'] ?? '') === 'past_due')>past_due</option>
            <option value="cancelled" @selected(($filters['subscription_status'] ?? '') === 'cancelled')>cancelled</option>
        </select>
        <select class="form-control" name="archived">
            <option value="" @selected(($filters['archived'] ?? '') === '')>Ativas no sistema</option>
            <option value="1" @selected(($filters['archived'] ?? '') === '1')>Excluídas</option>
        </select>
        <button class="btn btn-primary" type="submit">Filtrar</button>
    </form>

    <div class="card">
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
        <div style="margin-top:1rem;">{{ $companies->withQueryString()->links() }}</div>
    </div>
@endsection
