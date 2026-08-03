@extends('layouts.app')

@section('title', 'Editar lead')

@section('content')
    <h1 class="page-title">Editar lead</h1>

    <div class="card">
        <form method="POST" action="{{ route('crm.leads.update', $lead) }}">
            @csrf
            @method('PUT')
            @include('crm.leads._fields', ['lead' => $lead])
            <div class="actions" style="margin-top:1rem;">
                <button class="btn btn-primary" type="submit">Salvar</button>
                <a class="btn btn-ghost" href="{{ route('crm.leads.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
