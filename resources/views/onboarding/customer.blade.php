@extends('layouts.app')

@section('title', 'Cliente — Onboarding')

@section('content')
    <div data-saas-onboarding="1" data-saas-step="customer">
        <div style="margin-bottom:1.15rem;">
            <h1 class="page-title" style="margin:0;">Primeiro cliente</h1>
            <p class="header-meta" style="margin:.35rem 0 0;">Cadastre o primeiro contato no CRM.</p>
        </div>

        @include('onboarding.partials.saas-progress', ['progress' => $progress])

        <div class="card">
            <form method="POST" action="{{ route('onboarding.customer.store') }}">
                @csrf

                <div class="grid grid-2" style="gap:1rem;">
                    <div>
                        <label for="name">Nome</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                        @error('name') <div class="header-meta" style="color:var(--accent-2);">{{ $message }}</div> @enderror
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
                        @error('city') <div class="header-meta" style="color:var(--accent-2);">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="state">Estado (UF)</label>
                        <input id="state" name="state" type="text" maxlength="2" placeholder="SP" value="{{ old('state', 'SP') }}">
                    </div>
                </div>

                <div style="margin-top:1.25rem; display:flex; gap:.75rem; flex-wrap:wrap;">
                    <button class="btn btn-primary" type="submit">Salvar cliente</button>
                    <a class="btn btn-ghost" href="{{ route('onboarding.team') }}">Voltar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
