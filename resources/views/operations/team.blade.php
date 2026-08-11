@extends('layouts.operational')

@section('title', 'Equipe')

@section('page')
@php
    use App\Domains\Company\Models\User;
    use App\Domains\Company\Support\CommercialProfileCatalog;
    use App\Support\ClientArea\NavVisibility;
    $statusLabels = [
        User::STATUS_ACTIVE => 'Ativo',
        User::STATUS_INACTIVE => 'Inativo',
        User::STATUS_BLOCKED => 'Bloqueado',
    ];
    $hubUser = auth()->user();
    $canSeeGoals = $hubUser && NavVisibility::can($hubUser, 'crm');
    $canSeeCommissions = $hubUser && NavVisibility::can($hubUser, 'commissions');
@endphp

<div class="team-hub">
    <x-client.page-header
        title="Equipe Comercial"
        description="Crie, acompanhe e libere sua equipe de vendas."
    >
      @if($canManage)
        <button type="button" class="team-btn-primary" id="btn-open-create">
          <i data-lucide="user-plus" class="w-5 h-5"></i>
          Novo vendedor
        </button>
      @endif
    </x-client.page-header>

    <nav class="client-hub-tabs" aria-label="Seções da equipe">
        <a class="client-hub-tab is-active" href="{{ route('operations.team') }}" aria-current="page">
            <i data-lucide="users" class="w-4 h-4"></i> Membros
        </a>
        <a class="client-hub-tab" href="#team-roles">
            <i data-lucide="shield-check" class="w-4 h-4"></i> Funções
        </a>
        <a class="client-hub-tab" href="#team-grid">
            <i data-lucide="lock" class="w-4 h-4"></i> Permissões
        </a>
        @if($canSeeGoals)
            <a class="client-hub-tab" href="{{ route('crm.goals.index') }}">
                <i data-lucide="target" class="w-4 h-4"></i> Metas
            </a>
        @endif
        @if($canSeeCommissions)
            <a class="client-hub-tab" href="{{ route('commissions.index') }}">
                <i data-lucide="wallet" class="w-4 h-4"></i> Comissões
            </a>
        @endif
        @if($hubUser && \App\Support\ClientArea\NavVisibility::can($hubUser, 'stock'))
            <a class="client-hub-tab" id="team-shortcut-products" href="{{ route('commissions.products.index') }}">
                <i data-lucide="package" class="w-4 h-4"></i> Produtos
            </a>
        @endif
    </nav>

    <x-client.section-card title="Funções da equipe" description="O que cada perfil pode fazer na operação.">
        <div id="team-roles" class="client-quick-actions">
            @foreach($profiles as $profile)
                <x-client.status-badge tone="primary">{{ CommercialProfileCatalog::labelForSlug($profile->slug) }}</x-client.status-badge>
            @endforeach
        </div>
    </x-client.section-card>

    @if(session('temporary_password'))
        <div class="alert alert-success" style="display:flex;justify-content:space-between;gap:1rem;align-items:center;flex-wrap:wrap;">
            <div>
                <strong>Senha temporária:</strong>
                <code style="font-size:1.05rem;letter-spacing:.04em;">{{ session('temporary_password') }}</code>
                <div class="header-meta" style="margin-top:.25rem;">Compartilhe com o vendedor e peça para trocar no próximo acesso.</div>
            </div>
        </div>
    @endif

    <div class="team-presence-filters" role="navigation" aria-label="Filtro de presença">
        <a class="team-filter-chip {{ empty($presenceFilter) ? 'is-active' : '' }}" href="{{ route('operations.team') }}">Todos</a>
        <a class="team-filter-chip {{ ($presenceFilter ?? null) === 'online' ? 'is-active' : '' }}" href="{{ route('operations.team', ['presence' => 'online']) }}">Online</a>
        <a class="team-filter-chip {{ ($presenceFilter ?? null) === 'offline' ? 'is-active' : '' }}" href="{{ route('operations.team', ['presence' => 'offline']) }}">Offline</a>
    </div>

    @if($errors->any())
        <div class="alert alert-error">
            <ul style="margin:0;padding-left:1.1rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div id="team-grid" class="team-grid">
        @forelse($cards as $card)
            @php $u = $card['user']; @endphp
            <article class="team-card {{ $u->status !== User::STATUS_ACTIVE ? 'is-inactive' : '' }}" data-member-id="{{ $u->id }}" data-online="{{ !empty($card['online']) ? '1' : '0' }}">
                <div class="team-card-top">
                    @if(!empty($card['photo_url']))
                        <img class="team-avatar team-avatar-img" src="{{ $card['photo_url'] }}" alt="" width="48" height="48" loading="lazy">
                    @else
                        <div class="team-avatar" aria-hidden="true">{{ $card['initials'] ?? strtoupper(mb_substr($u->name, 0, 1)) }}</div>
                    @endif
                    <div class="team-card-id">
                        <h2>{{ $u->name }}</h2>
                        <div class="team-meta-line">{{ $card['profile'] }} · {{ $statusLabels[$u->status] ?? $u->status }}</div>
                        <div class="team-presence-line">
                            <span class="team-dot {{ !empty($card['online']) ? 'is-online' : 'is-offline' }}" aria-hidden="true"></span>
                            <span>{{ $card['presence_label'] ?? 'Offline' }}</span>
                            <span class="team-access-hint">· {{ $card['access_label'] ?? '—' }}</span>
                        </div>
                        @php $sum = $card['permission_summary'] ?? ['mode' => 'role_default', 'label' => 'Perfil padrão', 'overrides_count' => 0]; @endphp
                        <div class="team-perm-badge {{ $sum['mode'] === 'customized' ? 'is-custom' : 'is-default' }}">
                            {{ $sum['label'] }}
                            @if(($sum['overrides_count'] ?? 0) > 0)
                                · {{ $sum['overrides_count'] }} {{ $sum['overrides_count'] === 1 ? 'ajuste' : 'ajustes' }}
                            @endif
                        </div>
                    </div>
                </div>

                @if(!empty($card['last_visit']))
                    <div class="team-activity-block">
                        <div class="team-activity-label">Última atividade</div>
                        <div class="team-activity-title">{{ $card['last_visit']['client'] }}</div>
                        <div class="team-activity-meta">
                            {{ optional($card['last_visit']['at'])->timezone(\App\Support\AppTime::zone())->format('H:i') ?? '—' }}
                            · Visita
                            · {{ $card['last_visit']['result'] }}
                        </div>
                    </div>
                @endif

                @if(!empty($card['last_sale']))
                    <div class="team-activity-block">
                        <div class="team-activity-label">Última venda</div>
                        <div class="team-activity-title">{{ $card['last_sale']['client'] }}</div>
                        <div class="team-activity-meta">
                            {{ optional($card['last_sale']['at'])->timezone(\App\Support\AppTime::zone())->format('H:i') ?? '—' }}
                            · {{ $card['last_sale']['product'] }}
                        </div>
                    </div>
                @endif

                <dl class="team-facts">
                    <div><dt>Cidade</dt><dd>{{ $card['city'] }}</dd></div>
                    <div><dt>Região</dt><dd>{{ $card['region'] }}</dd></div>
                    <div><dt>Campanha</dt><dd>{{ $card['campaign'] }}</dd></div>
                </dl>

                <div class="team-metrics">
                    <div><span>{{ $card['visits_today'] }}</span><small>Visitas hoje</small></div>
                    <div><span>{{ $card['interested_today'] }}</span><small>Interessados</small></div>
                    <div><span>{{ $card['contracts_today'] }}</span><small>{{ $commercial::sales() }}</small></div>
                </div>

                <div class="team-actions">
                    <a class="team-btn-ghost" href="{{ route('operations.team', ['member' => $u->id, 'panel' => 'performance']) }}">Ver desempenho</a>
                    @if(!empty($canEditMembers))
                        <button type="button" class="team-btn-ghost js-open-edit" data-member="{{ $u->id }}">Editar</button>
                    @endif
                    @if(!empty($canManagePermissions))
                        <button type="button" class="team-btn-ghost js-open-perms" data-member="{{ $u->id }}">Permissões</button>
                    @endif
                    @if(!empty($canToggleStatus) && $u->id !== auth()->id())
                        @if($u->status === User::STATUS_ACTIVE)
                            <form method="POST" action="{{ route('operations.team.deactivate', $u) }}" onsubmit="return confirm('Bloquear o acesso deste vendedor? O histórico será preservado.');">
                                @csrf
                                <button type="submit" class="team-btn-danger">Inativar</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('operations.team.activate', $u) }}">
                                @csrf
                                <button type="submit" class="team-btn-ghost">Reativar</button>
                            </form>
                        @endif
                    @endif
                </div>

                @php
                    $memberPayload = [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'phone' => $u->phone,
                        'role_id' => $u->role_id,
                        'status' => $u->status,
                        'campaign_id' => $card['campaign_id'],
                        'profile' => $card['profile'],
                        'permissions' => $card['permissions'],
                        'permission_summary' => $card['permission_summary'] ?? null,
                        'permissions_url' => $card['permissions_url'],
                        'update_url' => route('operations.team.update', $u),
                        'reset_url' => route('operations.team.reset-password', $u),
                        'can_reset' => !empty($canResetPassword),
                    ];
                @endphp
                <script type="application/json" class="member-json" id="member-data-{{ $u->id }}">{!! json_encode($memberPayload, JSON_UNESCAPED_UNICODE) !!}</script>
            </article>
        @empty
            <div class="team-empty">
                <div style="font-size:1.15rem;font-weight:700;">Nenhum vendedor na equipe ainda</div>
                <p class="header-meta">Cadastre o primeiro com <strong>+ Novo vendedor</strong>.</p>
                @if($canManage)
                    <button type="button" class="team-btn-primary" id="btn-open-create-empty">+ Novo vendedor</button>
                @endif
            </div>
        @endforelse
    </div>
</div>

{{-- Drawer: criar --}}
@if($canManage)
<div id="drawer-create" class="team-drawer" hidden>
    <div class="team-drawer-backdrop" data-close></div>
    <div class="team-drawer-panel" role="dialog" aria-labelledby="create-title">
        <header>
            <h2 id="create-title">Novo vendedor</h2>
            <button type="button" class="team-icon-btn" data-close aria-label="Fechar"><i data-lucide="x" class="w-4 h-4"></i></button>
        </header>
        <form method="POST" action="{{ route('operations.team.store') }}" class="team-form">
            @csrf
            <label>Nome<input name="name" required value="{{ old('name') }}" class="form-control"></label>
            <label>Telefone<input name="phone" value="{{ old('phone') }}" class="form-control"></label>
            <label>E-mail<input type="email" name="email" required value="{{ old('email') }}" class="form-control"></label>
            <label>Senha<input type="password" name="password" required minlength="8" class="form-control"></label>
            <label>Perfil
                <select name="role_id" required class="form-control">
                    @foreach($profiles as $profile)
                        <option value="{{ $profile->id }}" @selected(old('role_id') == $profile->id)>
                            {{ CommercialProfileCatalog::labelForSlug($profile->slug) }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label>Campanha (cidade/região derivadas)
                <select name="campaign_id" class="form-control">
                    <option value="">— Sem campanha —</option>
                    @foreach($campaignOptions as $campaign)
                        <option value="{{ $campaign->id }}" @selected(old('campaign_id') == $campaign->id)>{{ $campaign->name }}</option>
                    @endforeach
                </select>
            </label>
            <p class="header-meta" style="margin:0;">Cidade e região vêm da campanha ativa. Supervisor fica preparado para evolução com Teams.</p>
            <div class="team-form-actions">
                <button type="button" class="team-btn-ghost" data-close>Cancelar</button>
                <button type="submit" class="team-btn-primary">Salvar vendedor</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Drawer: editar --}}
<div id="drawer-edit" class="team-drawer" hidden>
    <div class="team-drawer-backdrop" data-close></div>
    <div class="team-drawer-panel" role="dialog" aria-labelledby="edit-title">
        <header>
            <h2 id="edit-title">Editar vendedor</h2>
            <button type="button" class="team-icon-btn" data-close aria-label="Fechar"><i data-lucide="x" class="w-4 h-4"></i></button>
        </header>
        <form id="form-edit" method="POST" action="#" class="team-form">
            @csrf
            @method('PUT')
            <label>Nome<input name="name" id="edit-name" required class="form-control"></label>
            <label>Telefone<input name="phone" id="edit-phone" class="form-control"></label>
            <label>E-mail<input type="email" name="email" id="edit-email" required class="form-control"></label>
            <label>Nova senha (opcional)<input type="password" name="password" minlength="8" class="form-control" placeholder="Deixe em branco para manter"></label>
            <label>Perfil
                <select name="role_id" id="edit-role" required class="form-control">
                    @foreach($profiles as $profile)
                        <option value="{{ $profile->id }}">{{ CommercialProfileCatalog::labelForSlug($profile->slug) }}</option>
                    @endforeach
                </select>
            </label>
            <label>Campanha
                <select name="campaign_id" id="edit-campaign" class="form-control">
                    <option value="">— Sem alteração —</option>
                    @foreach($campaignOptions as $campaign)
                        <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Status
                <select name="status" id="edit-status" required class="form-control">
                    <option value="{{ User::STATUS_ACTIVE }}">Ativo</option>
                    <option value="{{ User::STATUS_INACTIVE }}">Inativo</option>
                    <option value="{{ User::STATUS_BLOCKED }}">Bloqueado</option>
                </select>
            </label>
            <div class="team-form-actions" style="justify-content:space-between;">
                @if(!empty($canResetPassword))
                    <button type="submit" form="form-reset" class="team-btn-ghost" id="btn-reset-password">Nova senha</button>
                @else
                    <span></span>
                @endif
                <div style="display:flex;gap:.5rem;">
                    <button type="button" class="team-btn-ghost" data-close>Cancelar</button>
                    <button type="submit" class="team-btn-primary">Salvar</button>
                </div>
            </div>
        </form>
        <form id="form-reset" method="POST" action="#">
            @csrf
        </form>
    </div>
</div>

{{-- Drawer: permissões individuais (overrides sobre o perfil) --}}
<div id="drawer-perms" class="team-drawer" hidden>
    <div class="team-drawer-backdrop" data-close></div>
    <div class="team-drawer-panel team-drawer-wide" role="dialog" aria-labelledby="perms-title">
        <header>
            <h2 id="perms-title">Permissões</h2>
            <button type="button" class="team-icon-btn" data-close aria-label="Fechar"><i data-lucide="x" class="w-4 h-4"></i></button>
        </header>
        <p class="header-meta" id="perms-subtitle" style="margin-top:0;"></p>
        <p class="header-meta" id="perms-mode" style="margin-top:0;"></p>
        <p class="header-meta">Somente poderes administrativos. O fluxo de campo do vendedor é regra da operação e não aparece aqui.</p>
        <form id="form-perms" method="POST" action="#" class="team-form">
            @csrf
            @method('PUT')
            <div id="perms-body" class="team-perms"></div>
            @if(!empty($canManagePermissions))
                <div class="team-form-actions">
                    <button type="button" class="team-btn-ghost" data-close>Cancelar</button>
                    <button type="submit" class="team-btn-primary">Salvar permissões</button>
                </div>
            @endif
        </form>
    </div>
</div>

{{-- Drawer: desempenho / presença / atividade --}}
@if(!empty($performance))
<div id="drawer-performance" class="team-drawer is-open">
    <div class="team-drawer-backdrop" onclick="window.location='{{ route('operations.team') }}'"></div>
    <div class="team-drawer-panel" role="dialog" aria-labelledby="perf-title">
        <header class="team-perf-header">
            <div class="team-perf-identity">
                @if(!empty($performance['photo_url']))
                    <img class="team-avatar team-avatar-img team-avatar-lg" src="{{ $performance['photo_url'] }}" alt="" width="56" height="56">
                @else
                    <div class="team-avatar team-avatar-lg" aria-hidden="true">{{ $performance['initials'] ?? '?' }}</div>
                @endif
                <div>
                    <h2 id="perf-title" style="margin:0;">{{ $performance['user']->name }}</h2>
                    <div class="team-meta-line">{{ $performance['profile'] ?? 'Vendedor' }}</div>
                    <div class="team-presence-line">
                        <span class="team-dot {{ !empty($performance['online']) ? 'is-online' : 'is-offline' }}" aria-hidden="true"></span>
                        <span>{{ $performance['presence_label'] ?? 'Offline' }}</span>
                    </div>
                </div>
            </div>
            <a class="team-icon-btn" href="{{ route('operations.team') }}" aria-label="Fechar"><i data-lucide="x" class="w-4 h-4"></i></a>
        </header>

        <dl class="team-facts" style="margin-top:.75rem;">
            <div><dt>Última atividade</dt><dd>{{ $performance['access_label'] ?? '—' }}</dd></div>
            <div><dt>Último login</dt><dd>{{ $performance['last_login_at']?->timezone(\App\Support\AppTime::zone())->format('d/m/Y H:i') ?? '—' }}</dd></div>
        </dl>

        <h3 class="team-section-title">Resumo de hoje</h3>
        <div class="team-metrics team-metrics-lg">
            <div><span>{{ $performance['visits_today'] }}</span><small>Visitas</small></div>
            <div><span>{{ $performance['interested'] }}</span><small>Interessados</small></div>
            <div><span>{{ $performance['contracts'] }}</span><small>{{ $commercial::sales() }}</small></div>
            <div><span>{{ number_format($performance['conversion'], 1) }}%</span><small>Conversão (semana)</small></div>
        </div>

        @if(!empty($performance['last_visit']))
            <h3 class="team-section-title">Última visita</h3>
            <div class="team-activity-block is-solid">
                <div class="team-activity-title">{{ $performance['last_visit']['client'] }}</div>
                <div class="team-activity-meta">
                    {{ optional($performance['last_visit']['at'])->timezone(\App\Support\AppTime::zone())->format('d/m/Y H:i') ?? '—' }}
                    · {{ $performance['last_visit']['result'] }}
                </div>
            </div>
        @endif

        @if(!empty($performance['last_sale']))
            <h3 class="team-section-title">Última venda</h3>
            <div class="team-activity-block is-solid">
                <div class="team-activity-title">{{ $performance['last_sale']['client'] }}</div>
                <div class="team-activity-meta">
                    {{ optional($performance['last_sale']['at'])->timezone(\App\Support\AppTime::zone())->format('d/m/Y H:i') ?? '—' }}
                    · {{ $performance['last_sale']['product'] }}
                    @if(!empty($performance['last_sale']['amount']))
                        · {{ $performance['last_sale']['amount'] }}
                    @endif
                </div>
            </div>
        @endif

        <h3 class="team-section-title">Atividade recente</h3>
        <div class="team-timeline">
            @forelse(($performance['timeline'] ?? []) as $event)
                <div class="team-timeline-item">
                    <div class="team-timeline-time">{{ optional($event['at'])->timezone(\App\Support\AppTime::zone())->format('H:i') ?? '—' }}</div>
                    <div>
                        <div class="team-timeline-kind">{{ $event['kind_label'] }}</div>
                        <div class="team-activity-title">{{ $event['client'] }}</div>
                        @if(!empty($event['detail']))
                            <div class="team-activity-meta">{{ $event['detail'] }}</div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="header-meta">Nenhuma atividade recente.</p>
            @endforelse
        </div>

        <h3 class="team-section-title">Conexões</h3>
        <div class="team-timeline">
            @forelse(($performance['connections'] ?? []) as $conn)
                <div class="team-timeline-item">
                    <div class="team-timeline-time">{{ $conn['at']->timezone(\App\Support\AppTime::zone())->format('d/m H:i') }}</div>
                    <div class="team-activity-meta">{{ $conn['label'] }}</div>
                </div>
            @empty
                <p class="header-meta">Nenhuma sessão registrada no histórico.</p>
            @endforelse
        </div>

        <a class="team-btn-primary" style="margin-top:1rem;width:100%;" href="{{ $performance['map_url'] }}">Abrir mapa</a>
    </div>
</div>
@endif

<style>
    .team-hub { max-width: 1080px; }
    .team-hub-header { display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; flex-wrap:wrap; margin-bottom:1.25rem; }
    .team-eyebrow { margin:0; text-transform:uppercase; letter-spacing:.08em; font-size:.72rem; color:#64748b; font-weight:700; }
    .team-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:1rem; }
    .team-card {
        background: linear-gradient(180deg, rgba(30,41,59,.95), rgba(15,23,42,.98));
        border: 1px solid rgba(51,65,85,.9);
        border-radius: 1.15rem;
        padding: 1rem 1.05rem 1.05rem;
        display:flex; flex-direction:column; gap:.85rem;
    }
    .team-card.is-inactive { opacity: .72; }
    .team-card-top { display:flex; gap:.75rem; align-items:center; }
    .team-avatar {
        width:3rem; height:3rem; border-radius:999px;
        background: rgba(56,189,248,.18); color:#e0f2fe;
        display:grid; place-items:center; font-weight:800; font-size:1.1rem;
        border:1px solid rgba(56,189,248,.35);
        overflow:hidden; flex-shrink:0; object-fit:cover;
    }
    .team-avatar-img { padding:0; display:block; }
    .team-avatar-lg { width:3.5rem; height:3.5rem; font-size:1.2rem; }
    .team-presence-filters { display:flex; flex-wrap:wrap; gap:.45rem; margin:0 0 1rem; }
    .team-filter-chip {
        display:inline-flex; align-items:center; padding:.45rem .85rem; border-radius:999px;
        border:1px solid #334155; color:#cbd5e1; text-decoration:none; font-size:.82rem; font-weight:700;
        background: rgba(15,23,42,.6);
    }
    .team-filter-chip.is-active { border-color:#38bdf8; color:#e0f2fe; background:rgba(56,189,248,.12); }
    .team-presence-line { display:flex; align-items:center; gap:.35rem; flex-wrap:wrap; margin-top:.2rem; font-size:.8rem; color:#94a3b8; }
    .team-dot { width:.55rem; height:.55rem; border-radius:999px; display:inline-block; }
    .team-dot.is-online { background:#34d399; box-shadow:0 0 0 3px rgba(52,211,153,.2); }
    .team-dot.is-offline { background:#64748b; }
    .team-access-hint { color:#64748b; font-weight:500; }
    .team-activity-block { border-top:1px solid #1e293b; padding-top:.65rem; }
    .team-activity-block.is-solid {
        border:1px solid #1e293b; border-radius:.85rem; padding:.7rem .8rem; background:rgba(15,23,42,.55);
    }
    .team-activity-label { font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#64748b; font-weight:700; margin-bottom:.2rem; }
    .team-activity-title { font-weight:700; font-size:.92rem; color:#f1f5f9; }
    .team-activity-meta { color:#94a3b8; font-size:.8rem; margin-top:.15rem; }
    .team-section-title { margin:1.1rem 0 .55rem; font-size:.78rem; text-transform:uppercase; letter-spacing:.06em; color:#64748b; }
    .team-perf-header { display:flex; justify-content:space-between; gap:.75rem; align-items:flex-start; }
    .team-perf-identity { display:flex; gap:.75rem; align-items:center; min-width:0; }
    .team-timeline { display:grid; gap:.65rem; }
    .team-timeline-item { display:grid; grid-template-columns:3.6rem 1fr; gap:.55rem; align-items:start; }
    .team-timeline-time { font-size:.75rem; color:#64748b; font-weight:700; padding-top:.15rem; }
    .team-timeline-kind { font-size:.68rem; font-weight:800; letter-spacing:.05em; color:#38bdf8; }
    .team-card-id h2 { margin:0; font-size:1.05rem; }
    .team-meta-line { color:#94a3b8; font-size:.82rem; margin-top:.15rem; }
    .team-facts { display:grid; gap:.35rem; margin:0; }
    .team-facts > div { display:flex; justify-content:space-between; gap:.75rem; font-size:.86rem; }
    .team-facts dt { color:#64748b; margin:0; }
    .team-facts dd { margin:0; text-align:right; color:#e2e8f0; max-width:65%; }
    .team-metrics { display:grid; grid-template-columns:repeat(3,1fr); gap:.4rem; }
    .team-metrics-lg { grid-template-columns:repeat(2,1fr); }
    .team-metrics > div {
        background: rgba(15,23,42,.75); border:1px solid #1e293b; border-radius:.85rem;
        padding:.55rem .4rem; text-align:center;
    }
    .team-metrics span { display:block; font-weight:800; font-size:1.15rem; color:#f8fafc; }
    .team-metrics small { color:#94a3b8; font-size:.68rem; }
    .team-actions { display:flex; flex-wrap:wrap; gap:.4rem; }
    .team-btn-primary, .team-btn-ghost, .team-btn-danger {
        display:inline-flex; align-items:center; justify-content:center; gap:.4rem;
        border-radius:.85rem; padding:.65rem .95rem; font-weight:700; cursor:pointer; border:0; text-decoration:none;
        font-size:.86rem;
    }
    .team-btn-primary { background:#38bdf8; color:#0f172a; }
    .team-btn-ghost { background:transparent; color:#e2e8f0; border:1px solid #334155; }
    .team-btn-danger { background:rgba(239,68,68,.12); color:#fecaca; border:1px solid rgba(239,68,68,.35); }
    .team-empty {
        grid-column:1/-1; text-align:center; padding:2.5rem 1rem;
        border:1px dashed #334155; border-radius:1.15rem; background:rgba(15,23,42,.6);
        display:flex; flex-direction:column; align-items:center; gap:.75rem;
    }
    .team-drawer { position:fixed; inset:0; z-index:70; display:none; }
    .team-drawer.is-open, .team-drawer:not([hidden]) { display:block; }
    .team-drawer[hidden] { display:none !important; }
    .team-drawer-backdrop { position:absolute; inset:0; background:rgba(2,6,23,.62); }
    .team-drawer-panel {
        position:absolute; top:0; right:0; height:100%; width:min(420px,100%);
        background:#0f172a; border-left:1px solid #334155; padding:1rem 1.1rem 1.5rem;
        overflow:auto; box-shadow:-20px 0 50px rgba(0,0,0,.35);
    }
    .team-drawer-panel header { display:flex; justify-content:space-between; align-items:center; margin-bottom:.75rem; }
    .team-drawer-panel h2 { margin:0; font-size:1.15rem; }
    .team-icon-btn { border:0; background:transparent; color:#94a3b8; cursor:pointer; padding:.4rem; border-radius:.5rem; }
    .team-form { display:flex; flex-direction:column; gap:.75rem; }
    .team-form label { display:flex; flex-direction:column; gap:.35rem; font-size:.82rem; color:#94a3b8; }
    .team-form-actions { display:flex; justify-content:flex-end; gap:.5rem; margin-top:.5rem; }
    .team-drawer-wide { width: min(480px, 100%); }
    .team-perms { display:flex; flex-direction:column; gap:1rem; max-height: calc(100dvh - 220px); overflow:auto; padding-right:.25rem; }
    .team-perms-group h3 { margin:0 0 .55rem; font-size:.72rem; letter-spacing:.08em; color:#64748b; }
    .team-check {
        display:flex; align-items:flex-start; gap:.7rem; padding:.55rem .65rem;
        border:1px solid #1e293b; border-radius:.85rem; margin-bottom:.4rem;
        background: rgba(15,23,42,.55); cursor:pointer;
    }
    .team-check input { width:1.15rem; height:1.15rem; margin-top:.15rem; accent-color:#38bdf8; }
    .team-check span { color:#e2e8f0; font-size:.92rem; font-weight:600; }
    .team-check small { display:block; color:#64748b; font-size:.72rem; font-weight:500; margin-top:.15rem; }
    .team-perm-badge {
        display:inline-flex; align-items:center; gap:.35rem; margin-top:.4rem;
        font-size:.72rem; font-weight:700; letter-spacing:.02em; padding:.2rem .55rem; border-radius:999px;
    }
    .team-perm-badge.is-default { background:#0f766e33; color:#5eead4; border:1px solid #0f766e66; }
    .team-perm-badge.is-custom { background:#1e3a5f; color:#93c5fd; border:1px solid #3b82f666; }
    .team-perm-row { display:flex; justify-content:space-between; gap:1rem; padding:.4rem 0; border-bottom:1px solid #1e293b; font-size:.9rem; }
    .team-perm-on { color:#86efac; }
    .team-perm-off { color:#64748b; }
</style>
@endsection

@push('scripts')
<script>
(() => {
    const open = (el) => { if (!el) return; el.hidden = false; el.classList.add('is-open'); };
    const close = (el) => { if (!el) return; el.hidden = true; el.classList.remove('is-open'); };

    const createDrawer = document.getElementById('drawer-create');
    const editDrawer = document.getElementById('drawer-edit');
    const permsDrawer = document.getElementById('drawer-perms');

    document.getElementById('btn-open-create')?.addEventListener('click', () => open(createDrawer));
    document.getElementById('btn-open-create-empty')?.addEventListener('click', () => open(createDrawer));

    document.querySelectorAll('[data-close]').forEach((btn) => {
        btn.addEventListener('click', () => {
            close(btn.closest('.team-drawer'));
        });
    });

    function memberData(id) {
        const node = document.getElementById('member-data-' + id);
        return node ? JSON.parse(node.textContent) : null;
    }

    document.querySelectorAll('.js-open-edit').forEach((btn) => {
        btn.addEventListener('click', () => {
            const data = memberData(btn.dataset.member);
            if (!data) return;
            const form = document.getElementById('form-edit');
            form.action = data.update_url;
            document.getElementById('edit-name').value = data.name || '';
            document.getElementById('edit-phone').value = data.phone || '';
            document.getElementById('edit-email').value = data.email || '';
            document.getElementById('edit-role').value = String(data.role_id);
            document.getElementById('edit-status').value = data.status;
            document.getElementById('edit-campaign').value = data.campaign_id ? String(data.campaign_id) : '';
            const reset = document.getElementById('form-reset');
            reset.action = data.reset_url;
            open(editDrawer);
        });
    });

    document.getElementById('btn-reset-password')?.addEventListener('click', (e) => {
        if (!confirm('Gerar uma nova senha temporária para este vendedor?')) {
            e.preventDefault();
        }
    });

    document.querySelectorAll('.js-open-perms').forEach((btn) => {
        btn.addEventListener('click', () => {
            const data = memberData(btn.dataset.member);
            if (!data) return;
            document.getElementById('perms-subtitle').textContent =
                data.name + ' · perfil ' + data.profile;
            const modeEl = document.getElementById('perms-mode');
            if (modeEl && data.permission_summary) {
                const s = data.permission_summary;
                modeEl.textContent = s.overrides_count > 0
                    ? (s.label + ' (' + s.overrides_count + ' ajuste' + (s.overrides_count === 1 ? '' : 's') + ' ativo' + (s.overrides_count === 1 ? '' : 's') + ')')
                    : s.label;
            }
            const form = document.getElementById('form-perms');
            form.action = data.permissions_url;
            const body = document.getElementById('perms-body');
            body.innerHTML = '';
            const canEdit = {{ !empty($canManagePermissions) ? 'true' : 'false' }};
            Object.entries(data.permissions || {}).forEach(([group, items]) => {
                const wrap = document.createElement('div');
                wrap.className = 'team-perms-group';
                wrap.innerHTML = '<h3>' + group + '</h3>';
                items.forEach((item) => {
                    const label = document.createElement('label');
                    label.className = 'team-check';
                    const effectLabel = item.effect === 'grant' ? 'Liberado sob medida'
                        : (item.effect === 'deny' ? 'Bloqueado sob medida' : 'Do perfil');
                    label.innerHTML =
                        '<input type="checkbox" name="permissions[' + item.permission + ']" value="1"' +
                        (item.granted ? ' checked' : '') +
                        (canEdit ? '' : ' disabled') + '>' +
                        '<div><span>' + item.label + '</span><small>' + effectLabel + '</small></div>';
                    wrap.appendChild(label);
                });
                body.appendChild(wrap);
            });
            open(permsDrawer);
        });
    });

    @if($openPanel === 'permissions' && $focusId)
        document.querySelector('.js-open-perms[data-member="{{ $focusId }}"]')?.click();
    @endif

    @if($openPanel === 'edit' && $focusId)
        document.querySelector('.js-open-edit[data-member="{{ $focusId }}"]')?.click();
    @endif

    if (window.lucide) window.lucide.createIcons();
})();
</script>
@endpush
