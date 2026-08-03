@extends('layouts.app')

@section('title', 'Usuários')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Usuários</h1>
        @can('create', App\Domains\Company\Models\User::class)
            <a class="btn btn-primary" href="{{ route('users.create') }}">Novo usuário</a>
        @endcan
    </div>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Perfil</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->role?->name }}</td>
                    <td>
                        <span class="badge {{ $user->status === 'active' ? 'badge-success' : 'badge-warning' }}">
                            {{ $user->status }}
                        </span>
                    </td>
                    <td class="actions">
                        @can('update', $user)
                            <a class="btn btn-ghost" href="{{ route('users.edit', $user) }}">Editar</a>
                        @endcan
                        @can('toggleStatus', $user)
                            <form method="POST" action="{{ route('users.toggle-status', $user) }}">
                                @csrf
                                <button class="btn btn-danger" type="submit">
                                    {{ $user->status === 'active' ? 'Desativar' : 'Ativar' }}
                                </button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Nenhum usuário encontrado.</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div style="margin-top:1rem;">
            {{ $users->links() }}
        </div>
    </div>
@endsection
