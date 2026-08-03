@extends('layouts.app')

@section('title', 'Editar setor')

@section('content')
    <h1 class="page-title">Editar setor</h1>
    <div class="card" style="max-width:720px;">
        <form method="POST" action="{{ route('sectors.update', $sector) }}">
            @csrf
            @method('PUT')
            @include('sales.territory.sectors._form', ['sector' => $sector])
            <div class="actions">
                <button class="btn btn-primary" type="submit">Salvar</button>
                <a class="btn btn-ghost" href="{{ route('sectors.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
