@extends('layouts.app')

@section('title', 'Novo morador')

@section('content')
    <h1 class="page-title">Novo morador</h1>
    <p class="header-meta" style="margin-top:-0.5rem; margin-bottom:1rem;">
        Cliente: {{ $property->address?->label() }}
    </p>

    <div class="card" style="max-width:780px;">
        <form method="POST" action="{{ route('properties.residents.store', $property) }}">
            @csrf
            @include('sales.residents._form', ['resident' => null, 'statuses' => $statuses, 'showStatus' => true])
            <div class="actions">
                <button class="btn btn-primary" type="submit">Cadastrar</button>
                <a class="btn btn-ghost" href="{{ route('properties.residents.index', $property) }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
