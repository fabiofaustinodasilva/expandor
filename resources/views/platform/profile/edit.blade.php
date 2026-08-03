@extends('layouts.platform')

@section('title', 'Meu perfil')

@section('content')
    <div style="margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Perfil do Platform Owner</h1>
        <div class="header-meta">Nome, e-mail, senha e foto</div>
    </div>

    <div class="card" style="max-width:640px;">
        <form method="POST" action="{{ route('platform.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <x-media-upload
                name="photo"
                label="Foto"
                :current-url="$user->photoUrl()"
                remove-name="remove_photo"
                accept="image/jpeg,image/png,image/webp"
                hint="JPG, PNG ou WEBP até 5MB."
            />

            <div class="form-group" style="margin-bottom:1rem;">
                <label for="name">Nome</label>
                <input class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="form-group" style="margin-bottom:1rem;">
                <label for="email">E-mail</label>
                <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
            </div>

            <h2 style="margin:1.25rem 0 .85rem; font-size:1rem;">Alterar senha</h2>
            <p class="header-meta" style="margin:-.4rem 0 .85rem;">Deixe em branco para manter a senha atual.</p>

            <div class="form-group" style="margin-bottom:1rem;">
                <label for="password">Nova senha</label>
                <input class="form-control" id="password" name="password" type="password" autocomplete="new-password">
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label for="password_confirmation">Confirmar senha</label>
                <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit">Salvar perfil</button>
                <a class="btn btn-ghost" href="{{ route('platform.dashboard') }}">Voltar</a>
            </div>
        </form>
    </div>

    <div class="card" style="max-width:640px; margin-top:1rem;">
        <h2 style="margin-top:0; font-size:1rem;">Autenticação em dois fatores (2FA)</h2>
        <p class="header-meta" style="margin-top:0;">Estrutura preparada — ativação em sprint futura</p>
        <p>
            <strong>Status:</strong>
            {{ $user->two_factor_enabled ? 'Habilitado' : 'Desabilitado' }}
        </p>
        <p>
            <strong>Confirmado em:</strong>
            {{ optional($user->two_factor_confirmed_at)->format('d/m/Y H:i') ?: '—' }}
        </p>
    </div>
@endsection