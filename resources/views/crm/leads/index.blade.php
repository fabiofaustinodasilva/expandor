@extends('layouts.app')

@section('title', 'Leads')

@section('content')
    <x-client.page-breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Operação'],
        ['label' => 'CRM'],
        ['label' => 'Leads'],
    ]" />

    <x-client.page-header title="Leads" description="Pipeline de leads comerciais — origem, status e conversão.">
        @can('create', App\Domains\CRM\Models\Lead::class)
            <x-client.primary-button :href="route('crm.leads.create')">Novo lead</x-client.primary-button>
        @endcan
    </x-client.page-header>

    <x-client.crud-toolbar>
        <x-slot:filters>
            <form method="GET" style="display:flex; gap:0.75rem; flex-wrap:wrap; align-items:end;">
                <div>
                    <label class="header-meta" for="lead-status">Status</label>
                    <select id="lead-status" name="status">
                        <option value="">Todos</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-ghost client-btn" type="submit">Filtrar</button>
            </form>
        </x-slot:filters>
    </x-client.crud-toolbar>

    <div class="card client-data-table">
        <table class="table">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Contato</th>
                <th>Origem</th>
                <th>Status</th>
                <th>Responsável</th>
                <th>Campanha</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($leads as $lead)
                <tr>
                    <td>{{ $lead->name }}</td>
                    <td>{{ $lead->email ?: '—' }}<br><span class="header-meta">{{ $lead->phone }}</span></td>
                    <td>{{ $lead->source?->label() }}</td>
                    <td><span class="badge">{{ $lead->status?->label() }}</span></td>
                    <td>{{ $lead->assignee?->name ?: '—' }}</td>
                    <td>{{ $lead->campaign?->name ?: '—' }}</td>
                    <td class="actions">
                        @can('update', $lead)
                            <a class="btn btn-ghost" href="{{ route('crm.leads.edit', $lead) }}">Editar</a>
                            @if($lead->status !== App\Domains\CRM\Enums\LeadStatus::CONVERTED && $lead->status !== App\Domains\CRM\Enums\LeadStatus::DISQUALIFIED)
                                <form method="POST" action="{{ route('crm.leads.convert', $lead) }}">
                                    @csrf
                                    <button class="btn btn-primary" type="submit">Converter</button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Nenhum lead cadastrado.</td></tr>
            @endforelse
            </tbody>
        </table>
        <x-client.pagination-bar :paginator="$leads" />
    </div>
@endsection
