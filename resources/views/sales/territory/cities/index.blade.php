@extends('layouts.app')

@section('title', 'Cidades')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Cidades</h1>
        @can('create', App\Domains\Sales\Territory\Models\City::class)
            <a class="btn btn-primary" href="{{ route('cities.create') }}">Nova cidade</a>
        @endcan
    </div>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Nome</th>
                <th>UF</th>
                <th>IBGE</th>
                <th>Setores</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($cities as $city)
                <tr>
                    <td>{{ $city->name }}</td>
                    <td>{{ $city->state }}</td>
                    <td>{{ $city->ibge_code ?: '—' }}</td>
                    <td>{{ $city->sectors_count }}</td>
                    <td>
                        <span class="badge {{ $city->active ? 'badge-success' : 'badge-warning' }}">
                            {{ $city->active ? 'Ativa' : 'Inativa' }}
                        </span>
                    </td>
                    <td class="actions">
                        @can('update', $city)
                            <a class="btn btn-ghost" href="{{ route('cities.edit', $city) }}">Editar</a>
                            <form method="POST" action="{{ route('cities.toggle-status', $city) }}">
                                @csrf
                                <button class="btn btn-danger" type="submit">
                                    {{ $city->active ? 'Desativar' : 'Ativar' }}
                                </button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">Nenhuma cidade cadastrada.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $cities->links() }}</div>
    </div>
@endsection
