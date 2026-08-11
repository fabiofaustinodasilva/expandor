@extends('layouts.app')

@section('title', 'Histórico da visita')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <div>
            <h1 class="page-title" style="margin:0;">Histórico da visita #{{ $visit->id }}</h1>
            <div class="header-meta">
                {{ $visit->campaign?->name }} —
                {{ $visit->property?->address?->label() }}
            </div>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="{{ route('campaigns.visits.index', $visit->campaign_id) }}">Voltar</a>
            @can('manageFollowUps', App\Domains\Visits\Models\Visit::class)
                <a class="btn btn-primary" href="{{ route('visits.follow-ups.create', $visit) }}">Agendar retorno</a>
            @endcan
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h2 style="margin-top:0; font-size:1.05rem;">Dados da visita</h2>
            <p><strong>Status:</strong> {{ $visit->status ? \App\Support\CommercialTerminology::visitResult($visit->status) : '—' }}</p>
            <p><strong>Vendedor:</strong> {{ $visit->user?->name }}</p>
            <p><strong>Visitado em:</strong> {{ $visit->visited_at ? \App\Support\AppTime::formatInstant($visit->visited_at) : null }}</p>
            <p><strong>Observações:</strong> {{ $visit->notes ?: '—' }}</p>
            <p><strong>Coordenadas:</strong>
                {{ $visit->latitude ?: '—' }}, {{ $visit->longitude ?: '—' }}
            </p>
        </div>

        <div class="card">
            <h2 style="margin-top:0; font-size:1.05rem;">Retornos</h2>
            <table class="table">
                <thead>
                <tr>
                    <th>Agendado</th>
                    <th>Status</th>
                    <th>Responsável</th>
                    <th>Notas</th>
                </tr>
                </thead>
                <tbody>
                @forelse($visit->followUps as $followUp)
                    <tr>
                        <td>
                            {{ $followUp->scheduleLabel() }}
                            @if($followUp->scheduleTimeHint())
                                <div class="header-meta">{{ $followUp->scheduleTimeHint() }}</div>
                            @endif
                        </td>
                        <td><span class="badge">{{ $followUp->status?->label() }}</span></td>
                        <td>{{ $followUp->user?->name }}</td>
                        <td>{{ $followUp->notes ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Nenhum retorno vinculado.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:1rem;">
        <h2 style="margin-top:0; font-size:1.05rem;">Histórico recente do cliente</h2>
        <table class="table">
            <thead>
            <tr>
                <th>Data</th>
                <th>De</th>
                <th>Para</th>
                <th>Descrição</th>
            </tr>
            </thead>
            <tbody>
            @forelse($visit->property?->histories ?? [] as $history)
                <tr>
                    <td>{{ $history->created_at ? \App\Support\AppTime::formatInstant($history->created_at) : null }}</td>
                    <td>{{ $history->old_status ? \App\Support\CommercialTerminology::propertyStatusLabel($history->old_status) : '—' }}</td>
                    <td>{{ $history->new_status ? \App\Support\CommercialTerminology::propertyStatusLabel($history->new_status) : '—' }}</td>
                    <td>{{ $history->description ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Sem histórico do cliente.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
