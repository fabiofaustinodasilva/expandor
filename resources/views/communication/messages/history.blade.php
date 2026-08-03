@extends('layouts.app')

@section('title', 'Histórico do morador')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <div>
            <h1 class="page-title" style="margin:0;">Histórico — {{ $resident->name }}</h1>
            <div class="header-meta">{{ $resident->phone ?: 'Sem telefone' }} · {{ $resident->email ?: 'Sem e-mail' }}</div>
        </div>
        <a class="btn btn-ghost" href="{{ route('communication.messages.index', ['resident_id' => $resident->id]) }}">Voltar</a>
    </div>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Data</th>
                <th>Direção</th>
                <th>Status</th>
                <th>Mensagem</th>
                <th>Usuário</th>
            </tr>
            </thead>
            <tbody>
            @forelse($messages as $message)
                <tr>
                    <td>{{ $message->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $message->direction?->label() }}</td>
                    <td>{{ $message->status?->label() }}</td>
                    <td>{{ $message->message }}</td>
                    <td>{{ $message->user?->name ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Sem mensagens para este morador.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $messages->links() }}</div>
    </div>
@endsection
