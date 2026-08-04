@extends('layouts.app')

@section('title', 'Concluir — Onboarding')

@section('content')
    <x-saas-onboarding-shell :progress="$progress" step-key="finish">
        <div style="margin-bottom:1rem;">
            <h1 class="page-title" style="margin:0;" data-saas-finish-title="1">🎉 Tudo pronto!</h1>
            <p class="header-meta" style="margin:.35rem 0 0;">Sua empresa está configurada.</p>
        </div>

        @include('onboarding.partials.saas-progress', ['progress' => $progress])

        <div class="saas-onb-card" style="margin-bottom:1rem;">
            <p style="margin-top:0;">Você já pode:</p>
            <ul style="margin:0; padding-left:1.1rem; display:grid; gap:.35rem;">
                <li>✓ Gerenciar clientes</li>
                <li>✓ Criar vendas</li>
                <li>✓ Acompanhar resultados</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('onboarding.complete') }}">
            @csrf
            <div class="saas-onb-actions">
                <button class="btn btn-primary" type="submit" data-saas-access-dashboard="1">Acessar painel</button>
                <a class="btn btn-ghost" href="{{ route('onboarding.branding') }}">Voltar</a>
            </div>
        </form>
    </x-saas-onboarding-shell>
@endsection
