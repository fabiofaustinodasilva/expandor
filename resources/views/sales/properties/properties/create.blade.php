@extends('layouts.app')

@section('title', 'Novo cliente')

@section('content')
    <h1 class="page-title">Novo cliente / ponto</h1>
    <div class="card" style="max-width:780px;">
        <form method="POST" action="{{ route('properties.store') }}">
            @csrf

            <div class="form-group">
                <label for="address_id">Endereço</label>
                <select class="form-control" id="address_id" name="address_id" required>
                    <option value="">Selecione</option>
                    @foreach($addresses as $address)
                        <option value="{{ $address->id }}" @selected(old('address_id') == $address->id)>
                            {{ $address->label() }} — {{ $address->city?->name }}/{{ $address->city?->state }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="type">Tipo</label>
                <select class="form-control" id="type" name="type" required>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', 'house') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="status">Status inicial</label>
                <select class="form-control" id="status" name="status" required>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', 'new') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
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
                <button class="btn btn-primary" type="submit">Cadastrar</button>
                <a class="btn btn-ghost" href="{{ route('properties.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
