@extends('layouts.app')

@section('title', 'Novo setor')

@section('content')
    <h1 class="page-title">Novo setor</h1>
    <div class="card" style="max-width:720px;">
        <form method="POST" action="{{ route('sectors.store') }}">
            @csrf
            @include('sales.territory.sectors._form', ['sector' => null])
            <div class="actions">
                <button class="btn btn-primary" type="submit">Criar</button>
                <a class="btn btn-ghost" href="{{ route('sectors.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
