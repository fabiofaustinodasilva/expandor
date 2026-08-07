@extends('layouts.app')

@section('title', 'Pontos')

@section('content')
    <x-client.page-breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Operação'],
        ['label' => 'Pontos'],
    ]" />

    <x-client.page-header title="Pontos" description="Cadastro de pontos comerciais no território.">
        @can('create', App\Domains\Sales\Properties\Models\Property::class)
            <x-client.primary-button :href="route('properties.create')">Novo ponto</x-client.primary-button>
        @endcan
    </x-client.page-header>

    <x-client.crud-toolbar>
        <x-slot:filters>
            <form method="GET" action="{{ route('properties.index') }}" class="actions">
                <select class="form-control" name="status" style="max-width:280px;">
                    <option value="">Todos os status</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-ghost client-btn" type="submit">Filtrar</button>
            </form>
        </x-slot:filters>
    </x-client.crud-toolbar>

    <div class="card client-data-table">
        <table class="table">
            <thead>
            <tr>
                <th>Endereço</th>
                <th>Tipo</th>
                <th>Status</th>
                <th>Cidade</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($properties as $property)
                <tr>
                    <td>{{ $property->address?->label() }}</td>
                    <td>{{ $property->type?->label() }}</td>
                    <td><span class="badge">{{ $property->status ? \App\Support\CommercialTerminology::propertyStatusLabel($property->status) : '—' }}</span></td>
                    <td>{{ $property->address?->city?->name }}/{{ $property->address?->city?->state }}</td>
                    <td class="actions">
                        @can('viewAny', App\Domains\Sales\Residents\Models\Resident::class)
                            <a class="btn btn-ghost" href="{{ route('properties.residents.index', $property) }}">Contatos</a>
                        @endcan
                        @can('changeStatus', $property)
                            <a class="btn btn-ghost" href="{{ route('properties.status.edit', $property) }}">Alterar status</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhum cliente / ponto cadastrado.</td></tr>
            @endforelse
            </tbody>
        </table>
        <x-client.pagination-bar :paginator="$properties" />
    </div>
@endsection
