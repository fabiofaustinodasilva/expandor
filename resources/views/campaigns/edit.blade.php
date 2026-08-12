@extends('layouts.app')

@section('title', 'Editar campanha')

@section('content')
    <h1 class="page-title">Editar campanha</h1>
    <div class="card" style="max-width:820px;">
        <form method="POST" action="{{ route('campaigns.update', $campaign) }}">
            @csrf
            @method('PUT')
            @include('campaigns._form')
            <div class="actions">
                <button class="btn btn-primary" type="submit">Salvar</button>
                <a class="btn btn-ghost" href="{{ route('campaigns.index') }}">Cancelar</a>
            </div>
            <p class="header-meta" style="margin-top:.75rem;">
                Status da campanha: <strong>{{ $campaign->status?->label() }}</strong>.
                Para liberar o EXP Vendedor use <em>Ativar</em> na listagem (status precisa ser Ativa).
            </p>
        </form>
    </div>
@endsection
