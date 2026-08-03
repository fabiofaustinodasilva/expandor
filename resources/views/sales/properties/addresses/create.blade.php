@extends('layouts.app')

@section('title', 'Novo endereço')

@section('content')
    <h1 class="page-title">Novo endereço</h1>
    <div class="card" style="max-width:780px;">
        <form method="POST" action="{{ route('addresses.store') }}">
            @csrf
            @include('sales.properties.addresses._form', ['address' => null])
            <div class="actions">
                <button class="btn btn-primary" type="submit">Cadastrar</button>
                <a class="btn btn-ghost" href="{{ route('addresses.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
