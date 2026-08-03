@extends('layouts.app')

@section('title', 'Mensagens WhatsApp')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <div>
            <h1 class="page-title" style="margin:0;">Comunicação WhatsApp</h1>
            <div class="header-meta">
                Conexão:
                @if($connection)
                    {{ $connection->status?->label() }} · {{ $connection->provider }} · {{ $connection->phone ?: 'sem número' }}
                @else
                    Não configurada (integração futura)
                @endif
            </div>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="{{ route('communication.templates.index') }}">Templates</a>
            @can('create', App\Domains\Communication\Models\Message::class)
                <a class="btn btn-primary" href="{{ route('communication.messages.create') }}">Registrar mensagem</a>
            @endcan
        </div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <form method="GET" action="{{ route('communication.messages.index') }}" class="actions">
            <select class="form-control" name="resident_id" style="max-width:320px;">
                <option value="">Todos os moradores</option>
                @foreach($residents as $resident)
                    <option value="{{ $resident->id }}" @selected((string) $selectedResidentId === (string) $resident->id)>
                        {{ $resident->name }} {{ $resident->phone ? '· '.$resident->phone : '' }}
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
                <th>Data</th>
                <th>Morador</th>
                <th>Direção</th>
                <th>Status</th>
                <th>Mensagem</th>
                <th>Usuário</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($messages as $message)
                <tr>
                    <td>{{ $message->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $message->resident?->name }}</td>
                    <td>{{ $message->direction?->label() }}</td>
                    <td><span class="badge">{{ $message->status?->label() }}</span></td>
                    <td>{{ \Illuminate\Support\Str::limit($message->message, 80) }}</td>
                    <td>{{ $message->user?->name ?: '—' }}</td>
                    <td>
                        <a class="btn btn-ghost" href="{{ route('communication.messages.history', $message->resident_id) }}">Histórico</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Nenhuma mensagem registrada.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $messages->links() }}</div>
    </div>
@endsection
