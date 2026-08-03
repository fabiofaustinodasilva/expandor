@extends('layouts.app')

@section('title', 'Editar usuário')

@section('content')
    <h1 class="page-title">Editar usuário</h1>

    <div class="card" style="max-width:720px;">
        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf
            @method('PUT')
            @include('company.users._form', ['user' => $user])
            <div class="actions">
                <button class="btn btn-primary" type="submit">Salvar</button>
                <a class="btn btn-ghost" href="{{ route('users.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
