@extends('layouts.app')

@section('title', 'Categorias de treinamento')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Categorias — Academia</h1>
        <div class="actions">
            <a class="btn btn-ghost" href="{{ route('training.contents.index') }}">Conteúdos</a>
            @can('create', App\Domains\Training\Models\TrainingCategory::class)
                <a class="btn btn-primary" href="{{ route('training.categories.create') }}">Nova categoria</a>
            @endcan
        </div>
    </div>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Descrição</th>
                <th>Conteúdos</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($category->description, 60) ?: '—' }}</td>
                    <td>{{ $category->contents_count }}</td>
                    <td>
                        <span class="badge {{ $category->active ? 'badge-success' : 'badge-warning' }}">
                            {{ $category->active ? 'Ativa' : 'Inativa' }}
                        </span>
                    </td>
                    <td class="actions">
                        @can('update', $category)
                            <a class="btn btn-ghost" href="{{ route('training.categories.edit', $category) }}">Editar</a>
                            <form method="POST" action="{{ route('training.categories.toggle', $category) }}">
                                @csrf
                                <button class="btn btn-danger" type="submit">
                                    {{ $category->active ? 'Desativar' : 'Ativar' }}
                                </button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhuma categoria cadastrada.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $categories->links() }}</div>
    </div>
@endsection
