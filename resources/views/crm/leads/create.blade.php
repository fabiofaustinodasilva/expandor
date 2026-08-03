@extends('layouts.app')

@section('title', 'Novo lead')

@section('content')
    <h1 class="page-title">Novo lead</h1>

    <div class="card">
        <form method="POST" action="{{ route('crm.leads.store') }}">
            @csrf
            @include('crm.leads._fields', ['lead' => null])
            <div class="actions" style="margin-top:1rem;">
                <button class="btn btn-primary" type="submit">Salvar</button>
                <a class="btn btn-ghost" href="{{ route('crm.leads.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
