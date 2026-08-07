@extends('layouts.app')

@section('title', 'Pontos')

@section('content')
    <x-client.page-breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Operação'],
        ['label' => 'Pontos'],
    ]" />

    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Pontos</h1>
        @can('create', App\Domains\Sales\Properties\Models\Property::class)
            <a class="btn btn-primary" href="{{ route('properties.create') }}">Novo ponto</a>
        @endcan
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <form method="GET" action="{{ route('properties.index') }}" class="actions">
            <select class="form-control" name="status" style="max-width:280px;">
                <option value="">Todos os status</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Filtrar</button>
        </form>
    </div>

    <div class="card">
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
        <div style="margin-top:1rem;">{{ $properties->links() }}</div>
    </div>
@endsection
