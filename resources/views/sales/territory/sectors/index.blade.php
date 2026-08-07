@extends('layouts.app')

@section('title', 'Setores')

@section('content')
    <x-client.page-breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Operação'],
        ['label' => 'Território'],
        ['label' => 'Setores'],
    ]" />

    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Setores</h1>
        @can('create', App\Domains\Sales\Territory\Models\Sector::class)
            <a class="btn btn-primary" href="{{ route('sectors.create') }}">Novo setor</a>
        @endcan
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <form method="GET" action="{{ route('sectors.index') }}" class="actions">
            <select class="form-control" name="city_id" style="max-width:280px;">
                <option value="">Todas as cidades</option>
                @foreach($cities as $city)
                    <option value="{{ $city->id }}" @selected($selectedCityId == $city->id)>
                        {{ $city->name }}/{{ $city->state }}
                    </option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Filtrar</button>
        </form>
    </div>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Cidade</th>
                <th>Descrição</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($sectors as $sector)
                <tr>
                    <td>{{ $sector->name }}</td>
                    <td>{{ $sector->city?->name }}/{{ $sector->city?->state }}</td>
                    <td>{{ $sector->description ?: '—' }}</td>
                    <td>
                        <span class="badge {{ $sector->active ? 'badge-success' : 'badge-warning' }}">
                            {{ $sector->active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </td>
                    <td class="actions">
                        @can('update', $sector)
                            <a class="btn btn-ghost" href="{{ route('sectors.edit', $sector) }}">Editar</a>
                            <form method="POST" action="{{ route('sectors.toggle-status', $sector) }}">
                                @csrf
                                <button class="btn btn-danger" type="submit">
                                    {{ $sector->active ? 'Desativar' : 'Ativar' }}
                                </button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhum setor cadastrado.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $sectors->links() }}</div>
    </div>
@endsection
