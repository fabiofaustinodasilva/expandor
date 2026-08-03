@extends('layouts.app')

@section('title', 'Expandor AI')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <div>
            <h1 class="page-title" style="margin:0;">Expandor AI</h1>
            <div class="header-meta">Assistente sugestivo — não executa ações comerciais automaticamente.</div>
        </div>
        <div class="actions">
            @can('create', App\Domains\AI\Models\AIConversation::class)
                <a class="btn btn-primary" href="{{ route('ai.conversations.create') }}">Nova pergunta</a>
            @endcan
        </div>
    </div>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Data</th>
                <th>Contexto</th>
                <th>Pergunta</th>
                <th>Provider</th>
                <th>Usuário</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($conversations as $conversation)
                <tr>
                    <td>{{ $conversation->created_at?->format('d/m/Y H:i') }}</td>
                    <td><span class="badge">{{ $conversation->context_type?->label() }}</span></td>
                    <td>{{ \Illuminate\Support\Str::limit($conversation->question, 80) }}</td>
                    <td>{{ $conversation->provider }}</td>
                    <td>{{ $conversation->user?->name }}</td>
                    <td>
                        <a class="btn btn-ghost" href="{{ route('ai.conversations.show', $conversation) }}">Ver</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Nenhuma conversa registrada ainda.</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div style="margin-top:1rem;">
            {{ $conversations->links() }}
        </div>
    </div>
@endsection
