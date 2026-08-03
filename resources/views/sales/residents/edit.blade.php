@extends('layouts.app')

@section('title', 'Editar morador')

@section('content')
    <h1 class="page-title">Editar morador</h1>
    <p class="header-meta" style="margin-top:-0.5rem; margin-bottom:1rem;">
        Cliente: {{ $property->address?->label() }}
    </p>

    <div class="card" style="max-width:780px;">
        <form method="POST" action="{{ route('residents.update', $resident) }}">
            @csrf
            @method('PUT')
            @include('sales.residents._form', ['resident' => $resident, 'showStatus' => false])
            <div class="actions">
                <button class="btn btn-primary" type="submit">Salvar</button>
                <a class="btn btn-ghost" href="{{ route('properties.residents.index', $property) }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
