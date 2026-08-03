@extends('layouts.app')

@section('title', 'Nova categoria')

@section('content')
    <h1 class="page-title">Nova categoria</h1>
    <div class="card" style="max-width:640px;">
        <form method="POST" action="{{ route('training.categories.store') }}">
            @csrf
            <div class="form-group">
                <label for="name">Nome</label>
                <input class="form-control" id="name" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label for="description">Descrição</label>
                <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
            </div>
            <label style="display:flex; gap:0.5rem; align-items:center; margin-bottom:1rem;">
                <input type="checkbox" name="active" value="1" @checked(old('active', true))>
                Ativa
            </label>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Salvar</button>
                <a class="btn btn-ghost" href="{{ route('training.categories.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
