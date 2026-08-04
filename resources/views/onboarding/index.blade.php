@extends('layouts.app')

@section('title', 'Onboarding')

@section('content')
    <x-saas-onboarding-shell :progress="$progress" step-key="index">
        <div style="margin-bottom:1.15rem;">
            <h1 class="page-title" style="margin:0;" data-saas-welcome="1">Bem-vindo ao Expandor 🚀</h1>
            <p class="header-meta" style="margin:.35rem 0 0;">Vamos configurar sua empresa em poucos minutos.</p>
        </div>

        @include('onboarding.partials.saas-progress', ['progress' => $progress])

        <div class="saas-onb-actions">
            @if($progress->continueUrl)
                <a class="btn btn-primary" href="{{ $progress->continueUrl }}" data-saas-continue="1">
                    Continuar configuração
                </a>
            @endif
        </div>
    </x-saas-onboarding-shell>
@endsection
