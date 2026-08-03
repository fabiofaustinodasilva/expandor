@extends('layouts.platform')

@section('title', 'Novo plano')

@section('content')
    <div style="margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Novo plano</h1>
        <div class="header-meta">Defina preços, limites e features comerciais</div>
    </div>

    <div class="card" style="max-width:820px;">
        <form method="POST" action="{{ route('platform.plans.store') }}">
            @csrf
            @include('platform.plans._form', ['plan' => null, 'featureMap' => []])
            <div class="actions">
                <button class="btn btn-primary" type="submit">Criar plano</button>
                <a class="btn btn-ghost" href="{{ route('platform.plans.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
