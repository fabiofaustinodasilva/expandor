@extends('layouts.app')

@section('title', 'Nova campanha')

@section('content')
    <h1 class="page-title">Nova campanha</h1>
    <div class="card" style="max-width:820px;">
        <form method="POST" action="{{ route('campaigns.store') }}">
            @csrf
            @include('campaigns._form')
            <div class="actions">
                <button class="btn btn-primary" type="submit">Cadastrar</button>
                <a class="btn btn-ghost" href="{{ route('campaigns.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
