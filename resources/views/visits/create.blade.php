@extends('layouts.app')

@section('title', 'Registrar visita')

@section('content')
    <h1 class="page-title">Registrar visita</h1>
    <p class="header-meta" style="margin-top:-0.5rem;">Campanha: {{ $campaign->name }}</p>

    <div class="card" style="max-width:780px;">
        <form method="POST" action="{{ route('campaigns.visits.store', $campaign) }}">
            @csrf

            <div class="form-group">
                <label for="property_id">Cliente / Ponto</label>
                <select class="form-control" id="property_id" name="property_id" required>
                    <option value="">Selecione</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}" @selected(old('property_id') == $property->id)>
                            #{{ $property->id }} — {{ $property->address?->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="status">Resultado da abordagem</label>
                <select class="form-control" id="status" name="status" required>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="visited_at">Data/hora da visita</label>
                <input class="form-control" type="datetime-local" id="visited_at" name="visited_at"
                       value="{{ old('visited_at', now()->format('Y-m-d\TH:i')) }}">
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label for="latitude">Latitude</label>
                    <input class="form-control" id="latitude" name="latitude" value="{{ old('latitude') }}">
                </div>
                <div class="form-group">
                    <label for="longitude">Longitude</label>
                    <input class="form-control" id="longitude" name="longitude" value="{{ old('longitude') }}">
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Observações</label>
                <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit">Salvar visita</button>
                <a class="btn btn-ghost" href="{{ route('campaigns.visits.index', $campaign) }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
