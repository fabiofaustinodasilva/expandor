@extends('layouts.guest')

@section('title', 'Checkout cancelado')

@section('content')
    <div class="card" style="max-width:560px;">
        <h1 style="margin-top:0;">Checkout cancelado</h1>
        <p class="muted">Nenhuma cobrança foi concluída. Você pode escolher um plano novamente quando quiser.</p>
        <a class="btn" href="{{ route('plans.index') }}">Ver planos</a>
    </div>
@endsection
