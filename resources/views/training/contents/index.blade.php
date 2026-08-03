@extends('layouts.app')

@section('title', 'Conteúdos de treinamento')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Conteúdos — Academia</h1>
        <div class="actions">
            <a class="btn btn-ghost" href="{{ route('training.categories.index') }}">Categorias</a>
            @can('create', App\Domains\Training\Models\TrainingContent::class)
                <a class="btn btn-primary" href="{{ route('training.contents.create') }}">Novo conteúdo</a>
            @endcan
        </div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <form method="GET" action="{{ route('training.contents.index') }}" class="actions">
            <select class="form-control" name="category_id" style="max-width:280px;">
                <option value="">Todas as categorias</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $selectedCategoryId === (string) $category->id)>
                        {{ $category->name }}
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
                <th>Título</th>
                <th>Categoria</th>
                <th>Tipo</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($contents as $content)
                <tr>
                    <td>{{ $content->title }}</td>
                    <td>{{ $content->category?->name }}</td>
                    <td>{{ $content->type?->label() }}</td>
                    <td>
                        <span class="badge {{ $content->active ? 'badge-success' : 'badge-warning' }}">
                            {{ $content->active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </td>
                    <td class="actions">
                        @can('update', $content)
                            <a class="btn btn-ghost" href="{{ route('training.contents.edit', $content) }}">Editar</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhum conteúdo cadastrado.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $contents->links() }}</div>
    </div>
@endsection
