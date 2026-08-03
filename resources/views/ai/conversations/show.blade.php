@extends('layouts.app')

@section('title', 'Sugestão IA')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <div>
            <h1 class="page-title" style="margin:0;">Sugestão IA</h1>
            <div class="header-meta">
                {{ $conversation->context_type?->label() }}
                · {{ $conversation->provider }}
                @if($conversation->tokens_used)
                    · {{ $conversation->tokens_used }} tokens
                @endif
                · {{ $conversation->created_at?->format('d/m/Y H:i') }}
            </div>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="{{ route('ai.conversations.index') }}">Histórico</a>
            @can('create', App\Domains\AI\Models\AIConversation::class)
                <a class="btn btn-primary" href="{{ route('ai.conversations.create') }}">Nova pergunta</a>
            @endcan
        </div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <div class="header-meta" style="margin-bottom:0.35rem;">Pergunta · {{ $conversation->user?->name }}</div>
        <div style="white-space:pre-wrap;">{{ $conversation->question }}</div>
    </div>

    <div class="card">
        <div class="header-meta" style="margin-bottom:0.35rem;">Resposta (sugestão)</div>
        <div style="white-space:pre-wrap;">{{ $conversation->answer }}</div>
    </div>
@endsection
