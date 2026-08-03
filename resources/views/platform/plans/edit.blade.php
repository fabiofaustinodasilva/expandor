@extends('layouts.platform')

@section('title', 'Editar plano')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; margin-bottom:1rem; flex-wrap:wrap;">
        <div>
            <h1 class="page-title" style="margin:0;">Editar plano</h1>
            <div class="header-meta">{{ $plan->name }} · {{ $plan->slug }}</div>
        </div>
        <a class="btn btn-ghost" href="{{ route('platform.plans.index') }}">Voltar</a>
    </div>

    <div class="card" style="max-width:820px;">
        <form method="POST" action="{{ route('platform.plans.update', $plan) }}">
            @csrf
            @method('PUT')
            @include('platform.plans._form')
            <div class="actions">
                <button class="btn btn-primary" type="submit">Salvar alterações</button>
                <a class="btn btn-ghost" href="{{ route('platform.plans.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
