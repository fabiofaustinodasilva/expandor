@extends('layouts.app')

@section('title', 'Nova oportunidade')

@section('content')
    <h1 class="page-title">Nova oportunidade</h1>
    <div class="card">
        <form method="POST" action="{{ route('crm.opportunities.store') }}">
            @csrf
            @include('crm.opportunities._fields', ['opportunity' => null])
            <div class="actions" style="margin-top:1rem;">
                <button class="btn btn-primary" type="submit">Salvar</button>
                <a class="btn btn-ghost" href="{{ route('crm.opportunities.kanban') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
