@extends('layouts.operational')

@section('title', 'Campanhas')

@section('page')
    <x-client.page-breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Operação'],
        ['label' => 'Campanhas'],
    ]" />

    <x-client.page-header title="Campanhas" description="Organize equipes, metas e período de cada campanha." />

    <x-client.crud-toolbar>
        <x-slot:actions>
            @can('create', App\Domains\Campaigns\Models\Campaign::class)
                <x-client.primary-button :href="route('campaigns.create')">Nova campanha</x-client.primary-button>
            @endcan
        </x-slot:actions>
    </x-client.crud-toolbar>

    <div class="card client-data-table">
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
                        <div class="client-row-actions">
                            @can('viewAny', App\Domains\Visits\Models\Visit::class)
                                <a class="btn btn-ghost client-btn" href="{{ route('campaigns.visits.index', $campaign) }}">Visitas</a>
                            @endcan
                            @can('update', $campaign)
                                <details class="client-overflow">
                                    <summary class="client-overflow__trigger btn btn-ghost client-btn" aria-label="Mais ações">⋯</summary>
                                    <div class="client-overflow__menu" role="menu">
                                        <a class="client-overflow__item" role="menuitem" href="{{ route('campaigns.edit', $campaign) }}">Editar</a>

                                        @if($campaign->status === App\Domains\Campaigns\Enums\CampaignStatus::DRAFT || $campaign->status === App\Domains\Campaigns\Enums\CampaignStatus::PAUSED)
                                            <form method="POST" action="{{ route('campaigns.activate', $campaign) }}">
                                                @csrf
                                                <button class="client-overflow__item" type="submit" role="menuitem">Ativar</button>
                                            </form>
                                        @endif

                                        @if($campaign->status === App\Domains\Campaigns\Enums\CampaignStatus::ACTIVE)
                                            <form method="POST" action="{{ route('campaigns.pause', $campaign) }}">
                                                @csrf
                                                <button class="client-overflow__item" type="submit" role="menuitem">Pausar</button>
                                            </form>
                                        @endif

                                        @if($campaign->status !== App\Domains\Campaigns\Enums\CampaignStatus::FINISHED)
                                            <form method="POST" action="{{ route('campaigns.finish', $campaign) }}">
                                                @csrf
                                                <button class="client-overflow__item client-overflow__item--danger" type="submit" role="menuitem">Finalizar</button>
                                            </form>
                                        @endif
                                    </div>
                                </details>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <x-client.empty-state
                            title="Nenhuma campanha ativa"
                            description="Crie uma campanha para organizar a equipe na rua."
                            icon="megaphone"
                        >
                            @can('create', App\Domains\Campaigns\Models\Campaign::class)
                                <a class="btn btn-primary" href="{{ route('campaigns.create') }}">Nova campanha</a>
                            @endcan
                        </x-client.empty-state>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        <x-client.pagination-bar :paginator="$campaigns" />
    </div>
@endsection
