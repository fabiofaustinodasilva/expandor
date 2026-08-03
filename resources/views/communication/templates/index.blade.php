@extends('layouts.app')

@section('title', 'Templates WhatsApp')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Templates de mensagem</h1>
        <div class="actions">
            <a class="btn btn-ghost" href="{{ route('communication.messages.index') }}">Mensagens</a>
            @can('create', App\Domains\Communication\Models\MessageTemplate::class)
                <a class="btn btn-primary" href="{{ route('communication.templates.create') }}">Novo template</a>
            @endcan
        </div>
    </div>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Evento</th>
                <th>Conteúdo</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse($templates as $template)
                <tr>
                    <td>{{ $template->name }}</td>
                    <td>{{ $template->event?->label() }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($template->content, 70) }}</td>
                    <td>
                        <span class="badge {{ $template->active ? 'badge-success' : 'badge-warning' }}">
                            {{ $template->active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </td>
                    <td class="actions">
                        @can('update', $template)
                            <a class="btn btn-ghost" href="{{ route('communication.templates.edit', $template) }}">Editar</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhum template cadastrado.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $templates->links() }}</div>
    </div>
@endsection
