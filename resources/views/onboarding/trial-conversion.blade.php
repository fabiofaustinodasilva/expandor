@extends('layouts.app')

@section('title', 'Teste encerrado')

@section('content')
    <div class="card" style="max-width:560px; margin:0 auto; text-align:center;" data-trial-conversion="1">
        <h1 class="page-title" style="margin-top:0;">Seu teste grátis encerrou</h1>
        <p class="header-meta" style="margin-bottom:1.25rem;">
            Continue com o {{ $platformName }} escolhendo um plano para {{ $company->name }}.
        </p>

        <div class="actions" style="justify-content:center; flex-wrap:wrap;">
            @if(auth()->user()?->hasPermission('billing.view'))
                <a class="btn btn-primary" href="{{ route('company.subscription.show') }}">Ver planos e assinatura</a>
            @endif
            <a class="btn btn-ghost" href="{{ route('plans.index') }}">Ver planos públicos</a>
            <a class="btn btn-ghost" href="{{ route('dashboard') }}">Voltar ao painel</a>
        </div>
    </div>
@endsection
