@extends('layouts.app')

@section('title', 'Editar endereço')

@section('content')
    <h1 class="page-title">Editar endereço</h1>
    <div class="card" style="max-width:780px;">
        <form method="POST" action="{{ route('addresses.update', $address) }}">
            @csrf
            @method('PUT')
            @include('sales.properties.addresses._form', ['address' => $address])
            <div class="actions">
                <button class="btn btn-primary" type="submit">Salvar</button>
                <a class="btn btn-ghost" href="{{ route('addresses.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
