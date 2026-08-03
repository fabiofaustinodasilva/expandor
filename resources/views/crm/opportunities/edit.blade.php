@extends('layouts.app')

@section('title', 'Editar oportunidade')

@section('content')
    <h1 class="page-title">Editar oportunidade</h1>
    <div class="card">
        <form method="POST" action="{{ route('crm.opportunities.update', $opportunity) }}">
            @csrf
            @method('PUT')
            @include('crm.opportunities._fields', ['opportunity' => $opportunity])
            <div class="actions" style="margin-top:1rem;">
                <button class="btn btn-primary" type="submit">Salvar</button>
                <a class="btn btn-ghost" href="{{ route('crm.opportunities.kanban') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
