@extends('layouts.sales-app')

@section('title', 'Registrar abordagem')

@section('content')
    <h1 class="page-title">Registrar abordagem</h1>
    <p class="page-sub">{{ $property->address?->label() }}</p>

    <div class="card">
        <div class="list-meta">Campanha: {{ $campaign->name }}</div>
        <div class="list-meta">Status atual do cliente: {{ $property->status ? \App\Support\CommercialTerminology::propertyStatusLabel($property->status) : '—' }}</div>
        @if($property->residents->isNotEmpty())
            <div class="list-meta" style="margin-top:0.35rem;">
                Morador: {{ $property->residents->first()->name }}
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('sales-app.campaigns.visits.store', [$campaign, $property]) }}" class="card">
        @csrf

        <div class="form-group">
            <label>Resultado da abordagem</label>
            <div class="status-grid">
                @foreach($statuses as $value => $label)
                    <label class="status-option">
                        <input type="radio" name="status" value="{{ $value }}"
                            @checked(old('status', 'interested') === $value) required>
                        <div>{{ $label }}</div>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="form-group">
            <label for="notes">Observações</label>
            <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="schedule_follow_up" value="1" id="schedule_follow_up"
                    @checked(old('schedule_follow_up'))>
                Agendar retorno
            </label>
        </div>

        <div id="follow-up-fields" style="display:none;">
            <div class="form-group">
                <label for="follow_up_at">Data/hora do retorno</label>
                <input class="form-control" type="datetime-local" id="follow_up_at" name="follow_up_at"
                       value="{{ old('follow_up_at', now()->addDay()->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="form-group">
                <label for="follow_up_notes">Notas do retorno</label>
                <textarea class="form-control" id="follow_up_notes" name="follow_up_notes" rows="2">{{ old('follow_up_notes') }}</textarea>
            </div>
        </div>

        <div class="btn-row">
            <button class="btn btn-primary" type="submit">Salvar abordagem</button>
            <a class="btn btn-ghost" href="{{ route('sales-app.campaigns.properties', $campaign) }}">Cancelar</a>
        </div>
    </form>

    <script>
        (function () {
            const checkbox = document.getElementById('schedule_follow_up');
            const fields = document.getElementById('follow-up-fields');
            function sync() {
                fields.style.display = checkbox.checked ? 'block' : 'none';
            }
            checkbox.addEventListener('change', sync);
            sync();

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (pos) {
                    const form = checkbox.form;
                    let lat = form.querySelector('input[name="latitude"]');
                    let lng = form.querySelector('input[name="longitude"]');
                    if (!lat) {
                        lat = document.createElement('input');
                        lat.type = 'hidden';
                        lat.name = 'latitude';
                        form.appendChild(lat);
                    }
                    if (!lng) {
                        lng = document.createElement('input');
                        lng.type = 'hidden';
                        lng.name = 'longitude';
                        form.appendChild(lng);
                    }
                    lat.value = pos.coords.latitude;
                    lng.value = pos.coords.longitude;
                });
            }
        })();
    </script>
@endsection
