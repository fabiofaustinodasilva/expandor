@extends('layouts.app')

@section('title', 'Equipe — Onboarding')

@section('content')
    <div data-saas-onboarding="1" data-saas-step="team">
        <div style="margin-bottom:1.15rem;">
            <h1 class="page-title" style="margin:0;">Adicionar equipe</h1>
            <p class="header-meta" style="margin:.35rem 0 0;">Convide quem vai operar o Expandor com você.</p>
        </div>

        @include('onboarding.partials.saas-progress', ['progress' => $progress])

        <div class="card">
            <form method="POST" action="{{ route('onboarding.team.store') }}">
                @csrf

                <div class="grid grid-2" style="gap:1rem;">
                    <div>
                        <label for="name">Nome</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                        @error('name') <div class="header-meta" style="color:var(--accent-2);">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                        @error('email') <div class="header-meta" style="color:var(--accent-2);">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="role">Perfil</label>
                        <select id="role" name="role" required>
                            @foreach($roles as $slug => $label)
                                <option value="{{ $slug }}" @selected(old('role', 'seller') === $slug)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('role') <div class="header-meta" style="color:var(--accent-2);">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="password">Senha inicial (opcional)</label>
                        <input id="password" name="password" type="password" autocomplete="new-password">
                    </div>
                </div>

                <div style="margin-top:1.25rem; display:flex; gap:.75rem; flex-wrap:wrap;">
                    <button class="btn btn-primary" type="submit">Salvar membro</button>
                    <a class="btn btn-ghost" href="{{ route('onboarding.company') }}">Voltar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
