@extends('layouts.app')

@section('title', 'Onboarding')

@section('content')
    <div data-saas-onboarding="1" data-saas-step="index">
        <div style="margin-bottom:1.15rem;">
            <h1 class="page-title" style="margin:0;" data-saas-welcome="1">Bem-vindo ao Expandor 🚀</h1>
            <p class="header-meta" style="margin:.35rem 0 0;">Vamos configurar sua empresa.</p>
        </div>

        @include('onboarding.partials.saas-progress', ['progress' => $progress])

        @if($progress->continueUrl)
            <a class="btn btn-primary" href="{{ $progress->continueUrl }}" data-saas-continue="1">
                Continuar configuração
            </a>
        @endif
    </div>
@endsection
