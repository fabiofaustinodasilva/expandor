@extends('layouts.app')

@section('title', 'Histórico da visita')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <div>
            <h1 class="page-title" style="margin:0;">Histórico da visita #{{ $visit->id }}</h1>
            <div class="header-meta">
                {{ $visit->campaign?->name }} —
                {{ $visit->property?->address?->label() }}
            </div>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="{{ route('campaigns.visits.index', $visit->campaign_id) }}">Voltar</a>
            @can('manageFollowUps', App\Domains\Visits\Models\Visit::class)
                <a class="btn btn-primary" href="{{ route('visits.follow-ups.create', $visit) }}">Agendar retorno</a>
            @endcan
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h2 style="margin-top:0; font-size:1.05rem;">Dados da visita</h2>
            <p><strong>Status:</strong> {{ $visit->status ? \App\Support\CommercialTerminology::visitResult($visit->status) : '—' }}</p>
            <p><strong>Vendedor:</strong> {{ $visit->user?->name }}</p>
            <p><strong>Visitado em:</strong> {{ $visit->visited_at ? \App\Support\AppTime::formatInstant($visit->visited_at) : null }}</p>
            <p><strong>Observações:</strong> {{ $visit->notes ?: '—' }}</p>
            <p><strong>Coordenadas:</strong>
                {{ $visit->latitude ?: '—' }}, {{ $visit->longitude ?: '—' }}
            </p>
        </div>

        <div class="card">
            <h2 style="margin-top:0; font-size:1.05rem;">Retornos</h2>
            <table class="table">
                <thead>
                <tr>
                    <th>Agendado</th>
                    <th>Status</th>
                    <th>Responsável</th>
                    <th>Notas</th>
                </tr>
                </thead>
                <tbody>
                @forelse($visit->followUps as $followUp)
                    <tr>
                        <td>
                            {{ $followUp->scheduleLabel() }}
                            @if($followUp->scheduleTimeHint())
                                <div class="header-meta">{{ $followUp->scheduleTimeHint() }}</div>
                            @endif
                        </td>
                        <td><span class="badge">{{ $followUp->status?->label() }}</span></td>
                        <td>{{ $followUp->user?->name }}</td>
                        <td>{{ $followUp->notes ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Nenhum retorno vinculado.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:1rem;">
        <h2 style="margin-top:0; font-size:1.05rem;">Histórico recente do cliente</h2>
        <table class="table">
            <thead>
            <tr>
                <th>Data</th>
                <th>De</th>
                <th>Para</th>
                <th>Descrição</th>
            </tr>
            </thead>
            <tbody>
            @forelse($visit->property?->histories ?? [] as $history)
                <tr>
                    <td>{{ $history->created_at ? \App\Support\AppTime::formatInstant($history->created_at) : null }}</td>
                    <td>{{ $history->old_status ? \App\Support\CommercialTerminology::propertyStatusLabel($history->old_status) : '—' }}</td>
                    <td>{{ $history->new_status ? \App\Support\CommercialTerminology::propertyStatusLabel($history->new_status) : '—' }}</td>
                    <td>{{ $history->description ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Sem histórico do cliente.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if(!empty($officeHandoff))
        <div class="card" style="margin-top:1rem;" data-sale-handoff="1">
            <h2 style="margin-top:0; font-size:1.05rem;">Encaminhamento ao escritório</h2>
            <p class="header-meta">{{ $officeHandoff->toArray()['sale_label'] }}</p>
            @if($officeHandoff->commissionLabel)
                <p><strong>Comissão:</strong> {{ $officeHandoff->commissionLabel }}
                    @if($officeHandoff->commissionStatus)
                        ({{ $officeHandoff->commissionStatus }})
                    @endif
                </p>
            @endif
            <div class="actions" style="flex-wrap:wrap; gap:.5rem;">
                @if($officeHandoff->whatsappEnabled && $officeHandoff->whatsappUrl)
                    <a class="btn btn-primary" href="{{ $officeHandoff->whatsappUrl }}" target="_blank" rel="noopener"
                       data-handoff-open="{{ $visit->sale?->id }}">Enviar no WhatsApp</a>
                @endif
                <button class="btn btn-ghost" type="button" data-handoff-copy="{{ $visit->sale?->id }}">Copiar mensagem</button>
                <button class="btn btn-ghost" type="button" data-handoff-view>Ver mensagem</button>
            </div>
            <pre id="sale-handoff-message" style="display:none; white-space:pre-wrap; margin-top:1rem; font-size:.9rem;">{{ $officeHandoff->message }}</pre>
        </div>
        <script>
            (function () {
                var msg = document.getElementById('sale-handoff-message');
                var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                document.querySelector('[data-handoff-view]')?.addEventListener('click', function () {
                    if (msg) msg.style.display = msg.style.display === 'none' ? 'block' : 'none';
                });
                document.querySelector('[data-handoff-copy]')?.addEventListener('click', function () {
                    var text = msg ? msg.textContent : '';
                    var saleId = this.getAttribute('data-handoff-copy');
                    var done = function () {
                        fetch('/vendas/' + saleId + '/handoff/copiar', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        alert('Mensagem copiada.');
                    };
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(done).catch(function () {
                            if (msg) { msg.style.display = 'block'; msg.focus(); }
                        });
                    } else if (msg) {
                        msg.style.display = 'block';
                    }
                });
                document.querySelector('[data-handoff-open]')?.addEventListener('click', function () {
                    var saleId = this.getAttribute('data-handoff-open');
                    fetch('/vendas/' + saleId + '/handoff/abrir', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                });
            })();
        </script>
    @endif
@endsection
