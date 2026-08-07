@extends('layouts.operational')

@section('title', 'Campanhas')

@section('page')
    <x-client.page-breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Operação'],
        ['label' => 'Campanhas'],
    ]" />

    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Campanhas</h1>
        @can('create', App\Domains\Campaigns\Models\Campaign::class)
            <a class="btn btn-primary" href="{{ route('campaigns.create') }}">Nova campanha</a>
        @endcan
    </div>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Cidade</th>
                <th>Status</th>
                <th>Período</th>
                <th>Meta visitas</th>
                <th>Vendedores</th>
                <th>Setores</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($campaigns as $campaign)
                <tr>
                    <td>{{ $campaign->name }}</td>
                    <td>{{ $campaign->city?->name }}/{{ $campaign->city?->state }}</td>
                    <td><span class="badge">{{ $campaign->status?->label() }}</span></td>
                    <td>
                        {{ $campaign->start_date?->format('d/m/Y') ?: '—' }}
                        —
                        {{ $campaign->end_date?->format('d/m/Y') ?: '—' }}
                    </td>
                    <td>{{ $campaign->goal_visits }}</td>
                    <td>{{ $campaign->users_count }}</td>
                    <td>{{ $campaign->sectors_count }}</td>
                    <td class="actions">
                        @can('viewAny', App\Domains\Visits\Models\Visit::class)
                            <a class="btn btn-ghost" href="{{ route('campaigns.visits.index', $campaign) }}">Visitas</a>
                        @endcan
                        @can('update', $campaign)
                            <a class="btn btn-ghost" href="{{ route('campaigns.edit', $campaign) }}">Editar</a>

                            @if($campaign->status === App\Domains\Campaigns\Enums\CampaignStatus::DRAFT || $campaign->status === App\Domains\Campaigns\Enums\CampaignStatus::PAUSED)
                                <form method="POST" action="{{ route('campaigns.activate', $campaign) }}">
                                    @csrf
                                    <button class="btn btn-primary" type="submit">Ativar</button>
                                </form>
                            @endif

                            @if($campaign->status === App\Domains\Campaigns\Enums\CampaignStatus::ACTIVE)
                                <form method="POST" action="{{ route('campaigns.pause', $campaign) }}">
                                    @csrf
                                    <button class="btn btn-ghost" type="submit">Pausar</button>
                                </form>
                            @endif

                            @if($campaign->status !== App\Domains\Campaigns\Enums\CampaignStatus::FINISHED)
                                <form method="POST" action="{{ route('campaigns.finish', $campaign) }}">
                                    @csrf
                                    <button class="btn btn-danger" type="submit">Finalizar</button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-friendly">
                            <div style="font-weight:700;">Nenhuma campanha ativa</div>
                            <p>Crie uma campanha para organizar a equipe na rua.</p>
                            @can('create', App\Domains\Campaigns\Models\Campaign::class)
                                <a class="btn btn-primary" href="{{ route('campaigns.create') }}">Nova campanha</a>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $campaigns->links() }}</div>
    </div>
@endsection
