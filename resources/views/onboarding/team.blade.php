@extends('layouts.app')

@section('title', 'Equipe — Onboarding')

@section('content')
    <x-saas-onboarding-shell :progress="$progress" step-key="team">
        <div style="margin-bottom:1rem;">
            <h1 class="page-title" style="margin:0;">Adicionar equipe</h1>
            <p class="header-meta" style="margin:.35rem 0 0;">Convide quem vai operar o Expandor com você.</p>
        </div>

        <div class="saas-onb-card">
            <form method="POST" action="{{ route('onboarding.team.store') }}">
                @csrf
                <div class="grid grid-2" style="gap:1rem;">
                    <div>
                        <label for="name">Nome</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                    </div>
                    <div>
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                    </div>
                    <div>
                        <label for="role">Perfil</label>
                        <select id="role" name="role" required>
                            @foreach($roles as $slug => $label)
                                <option value="{{ $slug }}" @selected(old('role', 'seller') === $slug)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="password">Senha inicial (opcional)</label>
                        <input id="password" name="password" type="password" autocomplete="new-password">
                    </div>
                </div>
                <div class="saas-onb-actions">
                    <button class="btn btn-primary" type="submit">Salvar membro</button>
                    <a class="btn btn-ghost" href="{{ route('onboarding.company') }}">Voltar</a>
                </div>
            </form>
            <form method="POST" action="{{ route('onboarding.skip') }}" style="margin-top:.75rem;">
                @csrf
                <input type="hidden" name="step" value="team">
                <button class="btn btn-ghost" type="submit">Pular por agora</button>
            </form>
        </div>
    </x-saas-onboarding-shell>
@endsection
