@extends('layouts.app')

@section('title', 'Editar template')

@section('content')
    <h1 class="page-title">Editar template</h1>
    <div class="card" style="max-width:720px;">
        <form method="POST" action="{{ route('communication.templates.update', $template) }}">
            @csrf
            @method('PUT')
            @include('communication.templates._form')
            <div class="actions">
                <button class="btn btn-primary" type="submit">Salvar</button>
                <a class="btn btn-ghost" href="{{ route('communication.templates.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
