@php
    $lead = $item->lead;
    $score = $lead?->score;
    $origin = $item->origin ?? ['origin' => 'Direto', 'campaign' => null];
@endphp
<article class="funnel-card">
    <strong>{{ $lead?->name ?: 'Lead' }}</strong>
    <div class="meta">{{ $lead?->company_name ?: '—' }} · {{ $lead?->cityState() }}</div>
    <div class="meta">
        @if($lead?->phone)
            <a href="{{ $item->tel_url }}">{{ \App\Domains\Marketplace\Growth\Support\BrazilianPhone::format($lead->phone) }}</a>
        @else
            Sem WhatsApp
        @endif
        @if($lead?->email)
            · {{ $lead->email }}
        @endif
        · {{ $lead?->sellers_count !== null ? $lead->sellers_count.' vend.' : '—' }}
        @if($lead?->customers_count !== null)
            · {{ number_format((int) $lead->customers_count, 0, ',', '.') }} cli.
        @endif
    </div>
    <div class="meta">
        Score {{ $score?->score ?? '—' }}
        @if($score?->temperature)
            · {{ $score->temperature->label() }}
        @endif
        · Origem: {{ $origin['origin'] }}
        @if($origin['campaign'])
            · Campanha: {{ $origin['campaign'] }}
        @endif
    </div>
    <div class="meta">{{ $lead?->created_at?->diffForHumans(short: true) }}</div>
    @if($item->demo_scheduled_at)
        <div class="meta"><strong>Demonstração</strong> {{ $item->demo_scheduled_at->timezone(config('app.timezone'))->format('d/m/Y \à\s H:i') }}</div>
    @elseif($item->next_action_at)
        <div class="meta">{{ $item->next_action_label ?: 'Próxima ação' }} {{ $item->next_action_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</div>
    @endif

    <div class="funnel-actions">
        @if($lead?->phone)
            <a class="btn funnel-wa" href="{{ route('platform.marketplace.pipeline.whatsapp', $item) }}">WhatsApp</a>
        @endif
        <button class="btn btn-ghost" type="button" onclick="this.closest('.funnel-card').querySelector('.funnel-schedule-form').classList.toggle('is-open')">Agendar</button>
        @if($lead)
            <a class="btn btn-ghost" href="{{ route('platform.marketplace.leads.show', $lead) }}">Detalhes</a>
        @endif
        @if($item->schedule_url)
            <a class="btn btn-ghost" href="{{ $item->schedule_url }}" target="_blank" rel="noopener">Abrir WhatsApp</a>
        @endif
    </div>

    <form class="funnel-schedule-form" method="POST" action="{{ route('platform.marketplace.pipeline.schedule', $item) }}">
        @csrf
        <div class="form-group">
            <label>Data</label>
            <input class="form-control" type="date" name="demo_date" required value="{{ old('demo_date', now()->toDateString()) }}">
        </div>
        <div class="form-group">
            <label>Hora</label>
            <input class="form-control" type="time" name="demo_time" required value="{{ old('demo_time', '15:00') }}">
        </div>
        <div class="form-group">
            <label>Observação</label>
            <input class="form-control" type="text" name="observation" maxlength="2000" placeholder="Opcional">
        </div>
        <button class="btn btn-primary" type="submit">Salvar agendamento</button>
    </form>

    <form method="POST" action="{{ route('platform.marketplace.pipeline.update', $item) }}">
        @csrf
        @method('PUT')
        <select class="form-control funnel-stage" name="stage" onchange="this.form.submit()">
            @foreach($stages as $stage)
                <option value="{{ $stage->value }}" @selected($item->stage === $stage)>{{ $stage->label() }}</option>
            @endforeach
        </select>
    </form>
</article>
