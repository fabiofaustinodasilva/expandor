@php
    /** @var \App\Domains\Onboarding\DTOs\SaasOnboardingProgress|null $progress */
    $progress = $progress ?? null;
@endphp

@if($progress)
    <div class="card" style="margin-bottom:1rem;" data-saas-checklist-card="1" data-saas-percent="{{ $progress->percent }}">
        <div style="display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:.65rem;">
            <strong>{{ $progress->percent }}% concluído</strong>
            <span class="header-meta">Checklist</span>
        </div>
        <div style="background:var(--bg-soft); border-radius:999px; overflow:hidden; height:12px;" aria-hidden="true">
            <div style="width:{{ $progress->percent }}%; height:100%; background:var(--accent); transition:width .25s ease;"></div>
        </div>
        <ul style="list-style:none; padding:0; margin:1rem 0 0; display:grid; gap:.45rem;" data-saas-checklist="1">
            @foreach($progress->checklist as $item)
                <li style="display:flex; align-items:center; gap:.55rem; color:{{ $item['done'] ? 'var(--success, #22C55E)' : 'var(--muted)' }};">
                    <span aria-hidden="true">{{ $item['done'] ? '✓' : '○' }}</span>
                    <span>{{ $item['label'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
