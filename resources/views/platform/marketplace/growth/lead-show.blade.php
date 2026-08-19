@extends('layouts.platform')

@section('title', 'Detalhes do lead')

@section('content')
    <div style="margin-bottom:1rem;">
        <a href="{{ route('platform.marketplace.pipeline.index') }}" class="header-meta" style="text-decoration:none;">← Central Comercial</a>
    </div>

    <h1 class="page-title" style="margin:0;">{{ $lead->name }}</h1>
    <div class="header-meta" style="margin-bottom:1rem;">{{ $lead->company_name ?: 'Provedor não informado' }}</div>

    <div class="funnel-actions" style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem;">
        @if($outreachUrl)
            <a class="btn btn-primary" href="{{ route('platform.marketplace.leads.whatsapp', $lead) }}">WhatsApp</a>
        @endif
        @if($telUrl)
            <a class="btn btn-ghost" href="{{ $telUrl }}">Ligar</a>
        @endif
        @if($lead->pipeline)
            <a class="btn btn-ghost" href="{{ route('platform.marketplace.pipeline.index') }}">Agendar no funil</a>
        @endif
        @if($scheduleUrl)
            <a class="btn btn-ghost" href="{{ $scheduleUrl }}" target="_blank" rel="noopener">Abrir WhatsApp da demonstração</a>
        @endif
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h2 style="margin-top:0;">Dados do provedor</h2>
            <p><strong>Provedor:</strong> {{ $lead->company_name ?: '—' }}</p>
            <p><strong>Cidade/UF:</strong> {{ $lead->cityState() }}</p>
            <p><strong>Vendedores:</strong> {{ $lead->sellers_count ?? '—' }}</p>
            <p><strong>Clientes:</strong> {{ $lead->customers_count !== null ? number_format((int) $lead->customers_count, 0, ',', '.') : '—' }}</p>
            <p><strong>Segmento:</strong> {{ $lead->segment ?: '—' }}</p>
        </div>
        <div class="card">
            <h2 style="margin-top:0;">Contato</h2>
            <p><strong>WhatsApp:</strong>
                @if($telUrl)
                    <a href="{{ $telUrl }}">{{ \App\Domains\Marketplace\Growth\Support\BrazilianPhone::format($lead->phone) }}</a>
                @else
                    —
                @endif
            </p>
            <p><strong>E-mail:</strong> {{ $lead->email ?: '—' }}</p>
            <p><strong>Score:</strong> {{ $lead->score?->score ?? '—' }} {{ $lead->score?->temperature?->label() }}</p>
            <p><strong>Origem:</strong> {{ $origin['origin'] }}</p>
            <p><strong>Campanha:</strong> {{ $origin['campaign'] ?: '—' }}</p>
            <p class="header-meta">Parâmetros técnicos ficam no histórico interno, não nesta capa.</p>
        </div>
    </div>

    <div class="grid grid-2" style="margin-top:1rem;">
        <div class="card">
            <h2 style="margin-top:0;">Agendamento / próxima ação</h2>
            @if($lead->pipeline?->demo_scheduled_at)
                <p><strong>Demonstração</strong> {{ $lead->pipeline->demo_scheduled_at->timezone(config('app.timezone'))->format('d/m/Y \à\s H:i') }}</p>
            @else
                <p class="header-meta">Nenhuma demonstração agendada.</p>
            @endif
            @if($lead->pipeline?->next_action_at)
                <p>{{ $lead->pipeline->next_action_label }} {{ $lead->pipeline->next_action_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
            @endif
            <p><strong>Estágio:</strong> {{ $lead->pipeline?->stage?->label() ?: '—' }}</p>
            <p><strong>Notas:</strong> {{ $lead->pipeline?->notes ?: ($lead->notes ?: '—') }}</p>
        </div>
        <div class="card">
            <h2 style="margin-top:0;">Histórico</h2>
            <ul style="padding-left:1.1rem;">
                @forelse($lead->activities as $activity)
                    <li style="margin-bottom:.45rem;">
                        {{ $activity->created_at?->timezone(config('app.timezone'))->format('d/m H:i') }}
                        — {{ $activity->label }}
                        @if($activity->detail)
                            <span class="header-meta">{{ $activity->detail }}</span>
                        @endif
                    </li>
                @empty
                    <li class="header-meta">Nenhuma ação registrada ainda.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
