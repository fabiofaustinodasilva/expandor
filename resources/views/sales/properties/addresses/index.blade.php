@extends('layouts.app')

@section('title', 'Endereços')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Endereços</h1>
        @can('create', App\Domains\Sales\Properties\Models\Address::class)
            <a class="btn btn-primary" href="{{ route('addresses.create') }}">Novo endereço</a>
        @endcan
    </div>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Endereço</th>
                <th>Cidade</th>
                <th>Setor</th>
                <th>Pontos</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($addresses as $address)
                <tr>
                    <td>{{ $address->label() }}</td>
                    <td>{{ $address->city?->name }}/{{ $address->city?->state }}</td>
                    <td>{{ $address->sector?->name ?: '—' }}</td>
                    <td>{{ $address->properties_count }}</td>
                    <td class="actions">
                        @can('update', $address)
                            <a class="btn btn-ghost" href="{{ route('addresses.edit', $address) }}">Editar</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhum endereço cadastrado.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $addresses->links() }}</div>
    </div>
@endsection
