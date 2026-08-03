@extends('layouts.app')

@section('title', 'Alterar status do cliente')

@section('content')
    <h1 class="page-title">Alterar status</h1>

    <div class="grid grid-2">
        <div class="card">
            <h2 style="margin-top:0;">Cliente / Ponto</h2>
            <p><strong>Endereço:</strong> {{ $property->address?->label() }}</p>
            <p><strong>Cidade:</strong> {{ $property->address?->city?->name }}/{{ $property->address?->city?->state }}</p>
            <p><strong>Tipo:</strong> {{ $property->type?->label() }}</p>
            <p><strong>Status atual:</strong> {{ $property->status ? \App\Support\CommercialTerminology::propertyStatusLabel($property->status) : '—' }}</p>

            <form method="POST" action="{{ route('properties.status.update', $property) }}" style="margin-top:1rem;">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="status">Novo status</label>
                    <select class="form-control" id="status" name="status" required>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $property->status?->value) === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Observação da alteração</label>
                    <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="form-group">
                    <label for="notes">Notas do cliente</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $property->notes) }}</textarea>
                </div>

                <div class="actions">
                    <button class="btn btn-primary" type="submit">Salvar status</button>
                    <a class="btn btn-ghost" href="{{ route('properties.index') }}">Voltar</a>
                </div>
            </form>
        </div>

        <div class="card">
            <h2 style="margin-top:0;">Histórico</h2>
            <table class="table">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>De</th>
                    <th>Para</th>
                    <th>Usuário</th>
                </tr>
                </thead>
                <tbody>
                @forelse($property->histories->sortByDesc('created_at') as $history)
                    <tr>
                        <td>{{ $history->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $history->old_status ? \App\Support\CommercialTerminology::propertyStatusLabel($history->old_status) : '—' }}</td>
                        <td>{{ $history->new_status ? \App\Support\CommercialTerminology::propertyStatusLabel($history->new_status) : '—' }}</td>
                        <td>{{ $history->user?->name ?: '—' }}</td>
                    </tr>
                    @if($history->description)
                        <tr>
                            <td colspan="4" style="color:var(--muted);">{{ $history->description }}</td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="4">Sem histórico.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
