@extends('layouts.app')

@section('title', 'Novo usuário')

@section('content')
    <h1 class="page-title">Novo usuário</h1>

    <div class="card" style="max-width:720px;">
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            @include('company.users._form', ['user' => null])
            <div class="actions">
                <button class="btn btn-primary" type="submit">Criar</button>
                <a class="btn btn-ghost" href="{{ route('users.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
