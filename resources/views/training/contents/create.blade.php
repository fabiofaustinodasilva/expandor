@extends('layouts.app')

@section('title', 'Novo conteúdo')

@section('content')
    <h1 class="page-title">Novo conteúdo</h1>
    <div class="card" style="max-width:780px;">
        <form method="POST" action="{{ route('training.contents.store') }}">
            @csrf
            @include('training.contents._form')
            <div class="actions">
                <button class="btn btn-primary" type="submit">Salvar</button>
                <a class="btn btn-ghost" href="{{ route('training.contents.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
