@extends('layouts.app')

@section('title', 'Nova cidade')

@section('content')
    <h1 class="page-title">Nova cidade</h1>
    <div class="card" style="max-width:720px;">
        <form method="POST" action="{{ route('cities.store') }}">
            @csrf
            @include('sales.territory.cities._form', ['city' => null])
            <div class="actions">
                <button class="btn btn-primary" type="submit">Criar</button>
                <a class="btn btn-ghost" href="{{ route('cities.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
