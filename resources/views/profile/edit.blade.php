@extends('layouts.operational')

@section('title', 'Meu perfil')

@section('page')
    <div style="margin-bottom:1rem;">
        <a href="{{ route('operations.settings') }}" class="header-meta" style="text-decoration:none;">← Configurações</a>
    </div>

    <h1 class="page-title">Meu perfil</h1>
    <p class="header-meta" style="margin-bottom:1.1rem;">Dados pessoais e senha. Permissões não são alteradas aqui.</p>

    @if($errors->any())
        <x-ux.alert type="error">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </x-ux.alert>
    @endif

    <div class="card op-form-readable">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="header-meta" style="margin-bottom:1rem;">
                <div>{{ $user->email }}</div>
                <div>{{ $user->role?->name ?? '—' }}</div>
            </div>

            <x-media-upload
                name="photo"
                label="Foto"
                :current-url="$user->photoUrl()"
                remove-name="remove_photo"
                accept="image/jpeg,image/png,image/webp"
                hint="JPG, PNG ou WEBP até 5MB."
            />

            <div class="form-group">
                <label for="name">Nome</label>
                <input class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label for="phone">Telefone</label>
                    <input class="form-control" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                </div>
                <div class="form-group">
                    <label for="whatsapp">WhatsApp</label>
                    <input class="form-control" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $user->whatsapp) }}">
                </div>
            </div>

            <h2 style="margin:1.25rem 0 .85rem; font-size:1rem;">Alterar senha</h2>
            <p class="header-meta" style="margin:-.4rem 0 .85rem;">Deixe em branco para manter a senha atual.</p>

            <div class="form-group">
                <label for="password">Nova senha</label>
                <input class="form-control" id="password" name="password" type="password" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirmar senha</label>
                <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
            </div>

            <div class="actions" style="margin-top:1.1rem;">
                <button class="btn btn-primary" type="submit">Salvar</button>
                <a class="btn btn-ghost" href="{{ route('operations.more') }}">Voltar</a>
            </div>
        </form>
    </div>
@endsection
