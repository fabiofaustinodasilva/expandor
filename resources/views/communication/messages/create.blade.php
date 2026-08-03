@extends('layouts.app')

@section('title', 'Registrar mensagem')

@section('content')
    <h1 class="page-title">Registrar mensagem</h1>
    <p class="header-meta" style="margin-top:-0.5rem;">Base de comunicação — envio real via provedor virá depois</p>

    <div class="card" style="max-width:720px;">
        <form method="POST" action="{{ route('communication.messages.store') }}">
            @csrf
            <div class="form-group">
                <label for="resident_id">Morador</label>
                <select class="form-control" id="resident_id" name="resident_id" required>
                    <option value="">Selecione</option>
                    @foreach($residents as $resident)
                        <option value="{{ $resident->id }}" @selected(old('resident_id') == $resident->id)>
                            {{ $resident->name }} {{ $resident->phone ? '· '.$resident->phone : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="message">Mensagem</label>
                <textarea class="form-control" id="message" name="message" rows="5" required>{{ old('message') }}</textarea>
            </div>
            <label style="display:flex; gap:0.5rem; align-items:center; margin-bottom:1rem;">
                <input type="checkbox" name="queue_send" value="1" @checked(old('queue_send', true))>
                Preparar para envio futuro (fila)
            </label>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Salvar</button>
                <a class="btn btn-ghost" href="{{ route('communication.messages.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
