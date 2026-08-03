@extends('layouts.sales-app')

@section('title', 'Meus retornos')

@section('content')
    <h1 class="page-title">Retornos pendentes</h1>
    <p class="page-sub">Somente os seus retornos</p>

    @forelse($followUps as $followUp)
        <div class="card">
            <div class="list-title">{{ $followUp->visit?->property?->address?->label() ?: 'Visita #'.$followUp->visit_id }}</div>
            <div class="list-meta">
                {{ $followUp->visit?->campaign?->name }}
                · {{ $followUp->scheduleLabel() }}
                @if($followUp->scheduleTimeHint())
                    · {{ $followUp->scheduleTimeHint() }}
                @endif
            </div>
            @if($followUp->notes)
                <div class="list-meta" style="margin-top:0.35rem;">{{ $followUp->notes }}</div>
            @endif
            <div class="btn-row">
                <form method="POST" action="{{ route('sales-app.follow-ups.complete', $followUp) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Concluir retorno</button>
                </form>
            </div>
        </div>
    @empty
        <div class="card empty">Nenhum retorno pendente.</div>
    @endforelse

    <div style="margin-top:0.5rem;">{{ $followUps->links() }}</div>
@endsection
