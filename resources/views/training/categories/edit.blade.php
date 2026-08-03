@extends('layouts.app')

@section('title', 'Editar categoria')

@section('content')
    <h1 class="page-title">Editar categoria</h1>
    <div class="card" style="max-width:640px;">
        <form method="POST" action="{{ route('training.categories.update', $category) }}">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="name">Nome</label>
                <input class="form-control" id="name" name="name" value="{{ old('name', $category->name) }}" required>
            </div>
            <div class="form-group">
                <label for="description">Descrição</label>
                <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $category->description) }}</textarea>
            </div>
            <label style="display:flex; gap:0.5rem; align-items:center; margin-bottom:1rem;">
                <input type="checkbox" name="active" value="1" @checked(old('active', $category->active))>
                Ativa
            </label>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Salvar</button>
                <a class="btn btn-ghost" href="{{ route('training.categories.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
