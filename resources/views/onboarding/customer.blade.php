@extends('layouts.app')

@section('title', 'Cliente — Onboarding')

@section('content')
    <x-saas-onboarding-shell :progress="$progress" step-key="customer">
        <div style="margin-bottom:1rem;">
            <h1 class="page-title" style="margin:0;">Primeiro cliente</h1>
            <p class="header-meta" style="margin:.35rem 0 0;">Cadastre o primeiro contato no CRM.</p>
        </div>

        <div class="saas-onb-card">
            <form method="POST" action="{{ route('onboarding.customer.store') }}">
                @csrf
                <div class="grid grid-2" style="gap:1rem;">
                    <div>
                        <label for="name">Nome</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                    </div>
                    <div>
                        <label for="phone">Telefone</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone') }}">
                    </div>
                    <div>
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}">
                    </div>
                    <div>
                        <label for="city">Cidade</label>
                        <input id="city" name="city" type="text" value="{{ old('city') }}" required>
                    </div>
                    <div>
                        <label for="state">Estado (UF)</label>
                        <input id="state" name="state" type="text" maxlength="2" value="{{ old('state', 'SP') }}">
                    </div>
                </div>
                <div class="saas-onb-actions">
                    <button class="btn btn-primary" type="submit">Salvar cliente</button>
                    <a class="btn btn-ghost" href="{{ route('onboarding.team') }}">Voltar</a>
                </div>
            </form>
            <form method="POST" action="{{ route('onboarding.skip') }}" style="margin-top:.75rem;">
                @csrf
                <input type="hidden" name="step" value="customer">
                <button class="btn btn-ghost" type="submit">Pular por agora</button>
            </form>
        </div>
    </x-saas-onboarding-shell>
@endsection
