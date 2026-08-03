@extends('layouts.app')

@section('title', 'Visitas')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <div>
            <h1 class="page-title" style="margin:0;">Visitas</h1>
            <div class="header-meta">
                Campanha: {{ $campaign->name }}
                — {{ $campaign->city?->name }}/{{ $campaign->city?->state }}
            </div>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="{{ route('campaigns.index') }}">Voltar às campanhas</a>
            @can('create', App\Domains\Visits\Models\Visit::class)
                <a class="btn btn-primary" href="{{ route('campaigns.visits.create', $campaign) }}">Registrar visita</a>
            @endcan
        </div>
    </div>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Data</th>
                <th>Cliente / Ponto</th>
                <th>Vendedor</th>
                <th>Status</th>
                <th>Observações</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($visits as $visit)
                <tr>
                    <td>{{ $visit->visited_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $visit->property?->address?->label() ?: '—' }}</td>
                    <td>{{ $visit->user?->name }}</td>
                    <td><span class="badge">{{ $visit->status ? \App\Support\CommercialTerminology::visitResult($visit->status) : '—' }}</span></td>
                    <td>{{ \Illuminate\Support\Str::limit($visit->notes, 60) ?: '—' }}</td>
                    <td class="actions">
                        <a class="btn btn-ghost" href="{{ route('visits.show', $visit) }}">Histórico</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">Nenhuma visita registrada nesta campanha.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $visits->links() }}</div>
    </div>
@endsection
