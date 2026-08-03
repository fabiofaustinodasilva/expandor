@extends('layouts.app')

@section('title', 'Alterar status do morador')

@section('content')
    <h1 class="page-title">Alterar status do morador</h1>

    <div class="grid grid-2">
        <div class="card">
            <h2 style="margin-top:0;">{{ $resident->name }}</h2>
            <p><strong>Cliente:</strong> {{ $resident->property?->address?->label() }}</p>
            <p><strong>Status atual:</strong> {{ $resident->status?->label() }}</p>

            <form method="POST" action="{{ route('residents.status.update', $resident) }}" style="margin-top:1rem;">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="status">Novo status</label>
                    <select class="form-control" id="status" name="status" required>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $resident->status?->value) === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Observação</label>
                    <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="form-group">
                    <label for="notes">Notas do morador</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $resident->notes) }}</textarea>
                </div>

                <div class="actions">
                    <button class="btn btn-primary" type="submit">Salvar status</button>
                    <a class="btn btn-ghost" href="{{ route('properties.residents.index', $resident->property_id) }}">Voltar</a>
                </div>
            </form>
        </div>

        <div class="card">
            <h2 style="margin-top:0;">Histórico</h2>
            <table class="table">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Evento</th>
                    <th>Descrição</th>
                </tr>
                </thead>
                <tbody>
                @forelse($resident->histories->sortByDesc('created_at') as $history)
                    <tr>
                        <td>{{ $history->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $history->event?->label() }}</td>
                        <td>{{ $history->description ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Sem histórico.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
