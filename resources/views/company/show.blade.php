@extends('layouts.operational')

@section('title', 'Empresa')

@section('page')
    <div style="margin-bottom:1rem;">
        <a href="{{ route('operations.settings') }}" class="header-meta" style="text-decoration:none;">← Configurações</a>
    </div>

    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem; flex-wrap:wrap;">
        <h1 class="page-title" style="margin:0;">Empresa</h1>
        <div class="actions">
            <a class="btn btn-primary" href="{{ route('company.edit', $company) }}">Editar</a>
            @can('create', \App\Domains\Branding\Models\Brand::class)
                <a class="btn btn-ghost" href="{{ route('company.branding.edit') }}">Identidade visual</a>
            @endcan
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h2 style="margin-top:0;">Dados cadastrais</h2>
            <p><strong>Nome fantasia:</strong> {{ $company->name }}</p>
            <p><strong>Razão social:</strong> {{ $company->legal_name ?: '—' }}</p>
            <p><strong>CNPJ:</strong> {{ $company->document ?: '—' }}</p>
            <p><strong>E-mail:</strong> {{ $company->email ?: '—' }}</p>
            <p><strong>Telefone:</strong> {{ $company->phone ?: '—' }}</p>
            <p><strong>WhatsApp comercial:</strong> {{ $company->whatsapp ?: '—' }}</p>
            <p><strong>Endereço:</strong> {{ $company->address ?: '—' }}</p>
            <p><strong>Segmento:</strong>
                @php
                    $segment = $company->segment
                        ? \App\Domains\Company\Enums\CompanySegment::tryFrom($company->segment)?->label()
                        : null;
                @endphp
                {{ $segment ?: '—' }}
            </p>
            <p><strong>Status:</strong> <span class="badge badge-success">{{ $company->status }}</span></p>
        </div>

        <div class="card">
            <h2 style="margin-top:0;">Assinatura</h2>
            @if($subscription)
                <p><strong>Plano:</strong> {{ $subscription->plan?->name ?? '—' }}</p>
                <p><strong>Status:</strong> {{ $subscription->status }}</p>
                <p><strong>Início:</strong> {{ optional($subscription->starts_at)->format('d/m/Y') ?: '—' }}</p>
                @can('billing.view', $company)
                    <p style="margin-top:1rem;">
                        <a class="btn btn-ghost" href="{{ route('company.plan.show') }}">Ver limites e consumo</a>
                    </p>
                @endcan
            @else
                <p>Nenhuma assinatura ativa.</p>
            @endif
        </div>
    </div>
@endsection
