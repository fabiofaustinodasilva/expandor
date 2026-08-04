@if(!empty($activationGuidance))
    <div class="card" style="margin-bottom:1rem;" data-activation-guidance="1" data-activation-score="{{ $activationGuidance->score }}">
        <div style="display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; align-items:flex-start;">
            <div>
                <strong>Progresso de ativação</strong>
                <div class="header-meta">
                    {{ $activationGuidance->score }}/100 {{ $activationGuidance->status->emoji() }} {{ $activationGuidance->status->label() }}
                    · {{ $activationGuidance->activationPercent }}%
                </div>
            </div>
            @if($activationGuidance->nextSteps[0]['url'] ?? null)
                <a class="btn btn-primary" href="{{ $activationGuidance->nextSteps[0]['url'] }}">Continuar</a>
            @endif
        </div>
        <div style="margin-top:.75rem; background:var(--bg-soft); border-radius:999px; overflow:hidden; height:10px;">
            <div style="width:{{ $activationGuidance->activationPercent }}%; height:100%; background:var(--accent);"></div>
        </div>
        <ul style="list-style:none; padding:0; margin:1rem 0 0; display:grid; gap:.4rem;" data-activation-next-steps="1">
            @foreach($activationGuidance->nextSteps as $step)
                <li>
                    {{ $step['label'] }}
                    @if(!empty($step['url']))
                        <a href="{{ $step['url'] }}" style="margin-left:.35rem;">Abrir</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
