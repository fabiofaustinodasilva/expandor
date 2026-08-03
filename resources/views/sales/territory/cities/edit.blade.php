@extends('layouts.app')

@section('title', 'Editar cidade')

@section('content')
    <h1 class="page-title">Editar cidade</h1>
    <div class="card" style="max-width:720px;">
        <form method="POST" action="{{ route('cities.update', $city) }}">
            @csrf
            @method('PUT')
            @include('sales.territory.cities._form', ['city' => $city])
            <div class="actions">
                <button class="btn btn-primary" type="submit">Salvar</button>
                <a class="btn btn-ghost" href="{{ route('cities.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
