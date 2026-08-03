@extends('layouts.app')

@section('title', 'Moradores')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <div>
            <h1 class="page-title" style="margin:0;">Moradores</h1>
            <div class="header-meta">
                Cliente: {{ $property->address?->label() }}
                — {{ $property->address?->city?->name }}/{{ $property->address?->city?->state }}
            </div>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="{{ route('properties.index') }}">Voltar aos clientes</a>
            @can('create', App\Domains\Sales\Residents\Models\Resident::class)
                <a class="btn btn-primary" href="{{ route('properties.residents.create', $property) }}">Novo morador</a>
            @endcan
        </div>
    </div>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Telefone</th>
                <th>E-mail</th>
                <th>Contato principal</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($residents as $resident)
                <tr>
                    <td>{{ $resident->name }}</td>
                    <td>{{ $resident->phone ?: '—' }}</td>
                    <td>{{ $resident->email ?: '—' }}</td>
                    <td>{{ $resident->is_primary_contact ? 'Sim' : 'Não' }}</td>
                    <td><span class="badge">{{ $resident->status?->label() }}</span></td>
                    <td class="actions">
                        @can('update', $resident)
                            <a class="btn btn-ghost" href="{{ route('residents.edit', $resident) }}">Editar</a>
                            <a class="btn btn-ghost" href="{{ route('residents.status.edit', $resident) }}">Status</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">Nenhum contato cadastrado neste cliente.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $residents->links() }}</div>
    </div>
@endsection
