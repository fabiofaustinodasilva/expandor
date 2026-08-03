@extends('layouts.app')

@section('title', 'Concluir — Onboarding')

@section('content')
    <div data-saas-onboarding="1" data-saas-step="finish">
        <div style="margin-bottom:1.15rem;">
            <h1 class="page-title" style="margin:0;" data-saas-finish-title="1">🎉 Tudo pronto!</h1>
            <p class="header-meta" style="margin:.35rem 0 0;">Sua empresa está configurada.</p>
        </div>

        @include('onboarding.partials.saas-progress', ['progress' => $progress])

        <div class="card" style="margin-bottom:1rem;">
            <p style="margin-top:0;">Você já pode:</p>
            <ul style="margin:0; padding-left:1.1rem; display:grid; gap:.35rem;">
                <li>✓ Gerenciar clientes</li>
                <li>✓ Criar vendas</li>
                <li>✓ Acompanhar resultados</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('onboarding.complete') }}">
            @csrf
            <button class="btn btn-primary" type="submit" data-saas-access-dashboard="1">Acessar painel</button>
        </form>
    </div>
@endsection
