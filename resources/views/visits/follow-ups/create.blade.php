@extends('layouts.app')

@section('title', 'Agendar retorno')

@section('content')
@php
    $oldDate = old('scheduled_date');
    $oldTime = old('scheduled_time');
    if (! $oldDate && old('scheduled_at')) {
        try {
            $parsed = \Carbon\Carbon::parse(old('scheduled_at'));
            $oldDate = $parsed->format('Y-m-d');
            $oldTime = ($parsed->hour || $parsed->minute) ? $parsed->format('H:i') : '';
        } catch (\Throwable) {
            $oldDate = null;
            $oldTime = null;
        }
    }
@endphp
    <h1 class="page-title">Agendar retorno</h1>
    <p class="header-meta" style="margin-top:-0.5rem;">
        Visita #{{ $visit->id }} — {{ $visit->property?->address?->label() }}
    </p>

    <div class="card" style="max-width:640px;">
        <form method="POST" action="{{ route('visits.follow-ups.store', $visit) }}">
            @csrf

            <div class="grid grid-2" style="margin-bottom:0;">
                <div class="form-group">
                    <label for="scheduled_date">Data do retorno *</label>
                    <input class="form-control" type="date" id="scheduled_date" name="scheduled_date"
                           value="{{ $oldDate ?: now()->addDay()->format('Y-m-d') }}"
                           min="{{ now()->toDateString() }}" required>
                    @error('scheduled_date')<div class="alert alert-error" style="margin-top:.5rem;">{{ $message }}</div>@enderror
                    @error('scheduled_at')<div class="alert alert-error" style="margin-top:.5rem;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="scheduled_time">Horário <span style="font-weight:400;opacity:.7;">(opcional)</span></label>
                    <input class="form-control" type="time" id="scheduled_time" name="scheduled_time"
                           value="{{ $oldTime }}">
                    @error('scheduled_time')<div class="alert alert-error" style="margin-top:.5rem;">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Observações</label>
                <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit">Agendar</button>
                <a class="btn btn-ghost" href="{{ route('visits.show', $visit) }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
