@if(!empty($saasActivationCard) && $saasActivationCard['show'])
    <div class="card" style="margin-bottom:1rem;" data-saas-activation-card="1" data-saas-percent="{{ $saasActivationCard['progress']->percent }}">
        <div style="display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; align-items:flex-start;">
            <div>
                <strong>Configure sua conta</strong>
                <div class="header-meta">{{ $saasActivationCard['progress']->percent }}% concluído</div>
            </div>
            <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
                @if($saasActivationCard['progress']->continueUrl)
                    <a class="btn btn-primary" href="{{ $saasActivationCard['progress']->continueUrl }}" data-saas-continue="1">
                        Continuar configuração
                    </a>
                @endif
                <form method="POST" action="{{ route('onboarding.dismiss') }}">
                    @csrf
                    <button class="btn btn-ghost" type="submit" data-saas-dismiss="1">Minimizar</button>
                </form>
            </div>
        </div>
        <div style="margin-top:.75rem; background:var(--bg-soft); border-radius:999px; overflow:hidden; height:12px;">
            <div style="width:{{ $saasActivationCard['progress']->percent }}%; height:100%; background:var(--accent); transition:width .25s ease;"></div>
        </div>
        <ul style="list-style:none; padding:0; margin:1rem 0 0; display:grid; gap:.4rem;" data-saas-checklist="1">
            @foreach($saasActivationCard['progress']->checklist as $item)
                @if($item['key'] === 'provisioned')
                    @continue
                @endif
                <li style="display:flex; gap:.5rem; color:{{ $item['done'] ? 'var(--success, #22C55E)' : 'var(--muted)' }};">
                    <span>{{ $item['done'] ? '✓' : '○' }}</span>
                    <span>{{ $item['label'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif

@if(!empty($saasWorkspaceReady) && $saasWorkspaceReady)
    <div class="card" style="margin-bottom:1rem;" data-saas-workspace-ready="1">
        <div style="display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; align-items:flex-start;">
            <div>
                <strong>Seu workspace está pronto 🚀</strong>
                <div class="header-meta">Sugestões para começar a usar o Expandor</div>
            </div>
            <form method="POST" action="{{ route('onboarding.dismiss-ready') }}">
                @csrf
                <button class="btn btn-ghost" type="submit">Fechar</button>
            </form>
        </div>
        <div class="grid grid-2" style="margin-top:1rem;">
            <a class="card" href="{{ route('crm.leads.index') }}" style="display:block;">Cadastrar clientes</a>
            <a class="card" href="{{ route('crm.opportunities.index') }}" style="display:block;">Criar vendas</a>
            <a class="card" href="{{ route('operations.team') }}" style="display:block;">Convidar equipe</a>
            <a class="card" href="{{ route('properties.index') }}" style="display:block;">Importar / cadastrar pontos</a>
        </div>
    </div>
@endif
