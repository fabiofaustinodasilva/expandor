@extends('layouts.operational')

@section('title', 'Mapa operacional')

@section('content')
<div
    id="map-page"
    class="absolute inset-0 overflow-hidden"
    data-markers-url="{{ $markersUrl }}"
    data-visit-store-template="{{ $visitStoreUrlTemplate }}"
    data-point-store-url="{{ $pointStoreUrl }}"
    data-first-approach-url="{{ $firstApproachUrl ?? '' }}"
    data-point-show-template="{{ $pointShowUrlTemplate }}"
    data-point-adjust-template="{{ $pointAdjustUrlTemplate }}"
    data-opportunity-url="{{ $urls['opportunity_create'] }}"
    data-messages-url="{{ $urls['messages_create'] }}"
    data-follow-ups-url="{{ $urls['follow_ups_index'] }}"
    data-property-residents-template="{{ $urls['property_residents'] }}"
    data-can-visit="{{ $permissions['visits_manage'] ? '1' : '0' }}"
    data-can-whatsapp="{{ $permissions['communication_view'] ? '1' : '0' }}"
    data-can-crm="{{ $permissions['crm_manage'] ? '1' : '0' }}"
    data-can-history="{{ $permissions['properties_view'] ? '1' : '0' }}"
    data-can-create-point="{{ $permissions['properties_manage'] ? '1' : '0' }}"
    data-can-edit-point="{{ $permissions['properties_manage'] ? '1' : '0' }}"
    data-seller-name="{{ $sellerName }}"
    data-current-user-id="{{ $currentUserId }}"
    data-is-field-seller="{{ !empty($isFieldSeller) ? '1' : '0' }}"
    data-no-campaign-message="{{ $noCampaignMessage ?? '' }}"
    data-seller-campaigns='@json(($sellerCampaigns ?? collect())->map(fn ($c) => ["id" => $c->id, "name" => $c->name])->values())'
    data-sellable-products='@json($sellableProducts ?? [])'
    data-points-visibility="{{ $fieldOps['points_visibility'] ?? 'company' }}"
    data-allows-ui-filters="{{ !empty($fieldOps['allows_ui_filters']) ? '1' : '0' }}"
    data-open-new-point="{{ request('action') === 'new-point' ? '1' : '0' }}"
    data-day-visits="{{ $dayMetrics->visits_total ?? 0 }}"
    data-day-interested="{{ $dayMetrics->interested_total ?? 0 }}"
    data-day-contracts="{{ $dayMetrics->installations_total ?? 0 }}"
    data-sale-registered-toast="{{ $commercial::saleRegisteredToast() }}"
    data-sale-required-fields='@json($saleRequiredFields ?? [])'
    data-sale-field-labels='@json($saleFieldLabels ?? [])'
    data-sectors='@json($sectors->map(fn ($s) => ["id" => $s->id, "city_id" => $s->city_id, "name" => $s->name])->values())'
    data-legend='@json($legend)'
    data-commercial-legend='@json($commercialLegend)'
    data-sellers='@json($sellers->map(fn ($s) => ["id" => $s->id, "name" => $s->name])->values())'
>
    <header class="absolute top-0 inset-x-0 z-30 pointer-events-none p-3 pt-[4.25rem] md:p-4 md:pt-4 lg:pr-[316px] map-toolbar">
        <div class="pointer-events-auto flex flex-col gap-2">
            <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                <div id="map-search-wrap" class="relative flex-1 min-w-0 bg-slate-900/90 backdrop-blur-md border border-slate-700/80 rounded-2xl flex items-center gap-2 px-3 h-12 md:h-14">
                    <i data-lucide="search" class="w-5 h-5 text-slate-400 shrink-0"></i>
                    <input id="map-search" type="search" placeholder="Buscar cliente, telefone, CPF ou endereço…"
                           class="w-full bg-transparent outline-none text-base text-slate-100 placeholder:text-slate-500"
                           autocomplete="off" aria-label="Buscar no mapa" aria-controls="map-search-results" aria-expanded="false">
                    <div id="map-search-results" class="hidden absolute left-0 right-0 top-[calc(100%+0.4rem)] z-50 max-h-72 overflow-y-auto rounded-xl border border-slate-700 bg-slate-950 shadow-xl" role="listbox"></div>
                </div>
                <div class="flex gap-2 items-center shrink-0">
                    @if($permissions['properties_manage'])
                        <button id="btn-new-point" type="button"
                                class="map-btn-meu-local h-12 md:h-14 px-4 rounded-2xl bg-sky-500 hover:bg-sky-400 text-slate-950 font-bold text-sm md:text-base inline-flex items-center justify-center gap-2 flex-1 sm:flex-none min-w-[9.5rem] shadow-lg"
                                title="Usar minha localização para cadastrar">
                            <i data-lucide="map-pin" class="w-5 h-5 shrink-0" aria-hidden="true"></i>
                            <span>Meu Local</span>
                        </button>
                    @endif
                    <span id="offline-queue-badge" class="hidden h-12 md:h-14 px-3 rounded-2xl bg-amber-500/20 border border-amber-500/40 text-amber-200 text-xs font-semibold items-center gap-1 shrink-0">
                        <span id="offline-queue-count">0</span> pendente(s)
                    </span>
                    @unless(!empty($isFieldSeller))
                    <button id="toggle-filters-manager" type="button" class="h-12 md:h-14 px-3 rounded-2xl bg-slate-900/90 border border-slate-700/80 text-slate-200 inline-flex items-center gap-2" title="Filtros">
                        <i data-lucide="sliders-horizontal" class="w-5 h-5"></i>
                    </button>
                    <button id="toggle-metrics" type="button" class="h-12 md:h-14 px-3 rounded-2xl bg-slate-900/90 border border-slate-700/80 text-slate-200 inline-flex items-center gap-2 lg:hidden" title="Métricas">
                        <i data-lucide="activity" class="w-5 h-5"></i>
                    </button>
                    @endunless
                </div>
            </div>

            @unless(!empty($isFieldSeller))
            <form id="map-filters-form" class="hidden md:grid grid-cols-2 lg:grid-cols-5 gap-2 bg-slate-900/90 backdrop-blur-md border border-slate-700/80 rounded-2xl p-2">
                <select id="filter-city" name="city_id" class="h-11 rounded-xl bg-slate-800 border border-slate-700 text-sm px-2 text-slate-100">
                    <option value="">Cidade</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}">{{ $city->name }}/{{ $city->state }}</option>
                    @endforeach
                </select>
                <select id="filter-sector" name="sector_id" class="h-11 rounded-xl bg-slate-800 border border-slate-700 text-sm px-2 text-slate-100">
                    <option value="">Setor</option>
                    @foreach($sectors as $sector)
                        <option value="{{ $sector->id }}" data-city-id="{{ $sector->city_id }}">{{ $sector->name }}</option>
                    @endforeach
                </select>
                <select id="filter-campaign" name="campaign_id" class="h-11 rounded-xl bg-slate-800 border border-slate-700 text-sm px-2 text-slate-100">
                    <option value="">Campanha</option>
                    @foreach($campaigns as $campaign)
                        <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                    @endforeach
                </select>
                <select id="filter-seller" name="user_id" class="h-11 rounded-xl bg-slate-800 border border-slate-700 text-sm px-2 text-slate-100">
                    <option value="">Vendedor</option>
                    @foreach($sellers as $seller)
                        <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                    @endforeach
                </select>
                <select id="filter-status" name="property_status" class="h-11 rounded-xl bg-slate-800 border border-slate-700 text-sm px-2 text-slate-100">
                    <option value="">Situação</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </form>
            @else
            {{-- Seller: filtros de cidade/setor ficam fora da UI (API/manager). Selects stub para JS. --}}
            <form id="map-filters-form" class="hidden" aria-hidden="true">
                <select id="filter-city" name="city_id"><option value="">Cidade</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}">{{ $city->name }}/{{ $city->state }}</option>
                    @endforeach
                </select>
                <select id="filter-sector" name="sector_id"><option value="">Setor</option>
                    @foreach($sectors as $sector)
                        <option value="{{ $sector->id }}" data-city-id="{{ $sector->city_id }}">{{ $sector->name }}</option>
                    @endforeach
                </select>
                <select id="filter-campaign" name="campaign_id"><option value="">Campanha</option>
                    @foreach($campaigns as $campaign)
                        <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                    @endforeach
                </select>
                <select id="filter-seller" name="user_id"><option value="">Vendedor</option></select>
                <select id="filter-status" name="property_status"><option value="">Situação</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </form>
            @endunless
        </div>
    </header>

    <div id="operational-map" class="absolute inset-0 z-0" role="application" aria-label="Mapa operacional"></div>

    {{-- Basemap: rua / satélite (Map Provider) — canto inferior esquerdo, acima dos filtros do topo --}}
    <div id="basemap-controls" class="absolute left-3 bottom-[13.5rem] sm:bottom-[11.5rem] z-20 flex gap-1 pointer-events-auto">
        <button type="button" id="basemap-street" class="basemap-btn is-active h-10 px-3 rounded-xl bg-slate-900/95 border border-slate-600 text-xs font-semibold text-slate-100 shadow-lg">🗺 Rua</button>
        <button type="button" id="basemap-satellite" class="basemap-btn h-10 px-3 rounded-xl bg-slate-900/90 border border-slate-700 text-xs font-semibold text-slate-300 shadow-lg" title="Satélite provisório (Esri) até Google/Mapbox">🛰 Satélite</button>
    </div>

    {{-- Filtros comerciais — manager sempre; seller: DOM oculto (JS mantém checkboxes) --}}
    <div id="commercial-filters"
         class="absolute left-3 top-[5.5rem] md:top-[6.25rem] z-20 w-[200px] max-w-[46vw] pointer-events-auto{{ !empty($isFieldSeller) ? ' hidden' : '' }}"
         @if(!empty($isFieldSeller)) aria-hidden="true" @endif>
        <div class="bg-slate-900/95 backdrop-blur-md border border-slate-700/80 rounded-2xl p-3 shadow-xl">
            <div class="flex items-center justify-between gap-2 mb-2">
                <div class="text-[11px] uppercase tracking-wide text-slate-400">Mostrar</div>
                <button type="button" id="close-layers" class="field-seller-only hidden p-1 rounded-lg text-slate-400 hover:bg-slate-800" title="Fechar" aria-label="Fechar camadas">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
            <div class="space-y-2 text-xs text-slate-100">
                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="commercial-filter accent-emerald-400" data-group="customer" checked> Clientes</label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="commercial-filter accent-blue-400" data-group="interested" checked> Interessados</label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="commercial-filter accent-amber-400" data-group="visited" checked> Visitados</label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="commercial-filter accent-rose-400" data-group="new" checked> Novos pontos</label>
                <label class="manager-only flex items-center gap-2 cursor-pointer pt-1 border-t border-slate-700"><input type="checkbox" id="filter-my-team" class="accent-sky-400" @checked(!empty($isFieldSeller))> Minha equipe</label>
            </div>
            @if(!empty($permissions['campaigns_manage']) || empty($isFieldSeller))
                <button type="button" id="btn-select-region" class="mt-3 w-full h-9 rounded-xl border border-dashed border-slate-600 text-[11px] text-slate-300 hover:border-sky-500 hover:text-sky-300">
                    Selecionar área · campanha
                </button>
            @endif
        </div>
    </div>

    {{-- Sprint 8.2.7: CTA de rota removida — fluxo via Meu Local --}}

    <div id="map-empty-state" class="absolute inset-0 z-10 hidden items-center justify-center pointer-events-none p-6 lg:pr-[316px]">
        <div class="pointer-events-auto max-w-sm w-full rounded-2xl bg-slate-950/95 border border-slate-700 p-5 text-center shadow-xl">
            <div class="text-lg font-semibold mb-1">Nenhuma residência nesta área</div>
            <p class="text-slate-400 text-sm mb-4">Use Meu Local para cadastrar a partir da sua posição GPS.</p>
            @if($permissions['properties_manage'])
                <button type="button" id="btn-empty-add-point" class="map-btn-meu-local w-full h-12 rounded-xl bg-sky-500 text-slate-950 font-bold inline-flex items-center justify-center gap-2">
                    <i data-lucide="map-pin" class="w-5 h-5" aria-hidden="true"></i>
                    <span>Meu Local</span>
                </button>
            @endif
        </div>
    </div>

    <div id="map-legend-panel" class="absolute left-3 bottom-20 sm:bottom-3 z-20 {{ !empty($isFieldSeller) ? 'hidden' : 'hidden sm:block' }} pointer-events-none"
         @if(!empty($isFieldSeller)) aria-hidden="true" @endif>
        <div class="pointer-events-auto bg-slate-900/90 backdrop-blur-md border border-slate-700/80 rounded-2xl p-3 min-w-[170px]">
            <div class="flex items-center justify-between gap-2 mb-2">
                <div class="text-[11px] uppercase tracking-wide text-slate-400">Legenda comercial</div>
                <button type="button" id="close-legend" class="field-seller-only hidden p-1 rounded-lg text-slate-400 hover:bg-slate-800 pointer-events-auto" title="Fechar" aria-label="Fechar legenda">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
            <ul class="space-y-1.5">
                @foreach($commercialLegend as $item)
                    <li class="flex items-center gap-2 text-xs text-slate-200">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $item['color'] }}"></span>
                        {{ $item['label'] }}
                    </li>
                @endforeach
            </ul>
            <div class="mt-2 pt-2 border-t border-slate-700 space-y-1 text-[10px] text-slate-400">
                <div>Anel: GPS · ajustado · baixa precisão</div>
            </div>
            <div class="mt-2 pt-2 border-t border-slate-700 text-xs text-slate-400">
                <span id="map-marker-count">0</span> pontos no mapa
            </div>
        </div>
    </div>

    <aside id="metrics-panel" class="absolute right-0 top-0 bottom-0 z-20 w-[300px] max-w-[90vw] translate-x-full lg:translate-x-0 transition-transform duration-300 bg-slate-950/95 backdrop-blur-xl border-l border-slate-700/80 flex flex-col">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-400">{{ empty($isFieldSeller) ? 'Equipe · hoje' : 'Hoje' }}</div>
                <h1 class="text-base font-semibold">Mapa operacional</h1>
            </div>
            <button id="close-metrics" type="button" class="lg:hidden p-2 rounded-lg text-slate-400 hover:bg-slate-800">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <div class="p-4 space-y-3 overflow-y-auto flex-1">
            @unless(!empty($isFieldSeller))
                <div class="rounded-xl bg-slate-900 border border-slate-800 p-3" id="team-view-panel">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="text-[11px] uppercase tracking-wide text-slate-400">Visão da equipe</div>
                        <button type="button" id="btn-team-view" class="h-8 px-2 rounded-lg border border-sky-600/50 text-sky-300 text-[11px] font-semibold">Ativar</button>
                    </div>
                    <p class="text-xs text-slate-500 mb-2">Ver vendedores, produtividade e rotas no mapa.</p>
                    <div id="team-view-body" class="hidden space-y-2">
                        <div class="grid grid-cols-3 gap-1 text-center text-xs">
                            <div class="rounded-lg bg-slate-950/80 p-2">
                                <div class="text-slate-500">Visitas</div>
                                <div class="font-semibold" id="team-metric-visits">{{ $teamMetrics?->visits_total ?? 0 }}</div>
                            </div>
                            <div class="rounded-lg bg-slate-950/80 p-2">
                                <div class="text-slate-500">Inter.</div>
                                <div class="font-semibold text-blue-400" id="team-metric-interested">{{ $teamMetrics?->interested_total ?? 0 }}</div>
                            </div>
                            <div class="rounded-lg bg-slate-950/80 p-2">
                                <div class="text-slate-500">{{ $commercial::sales() }}</div>
                                <div class="font-semibold text-emerald-400" id="team-metric-contracts">{{ $teamMetrics?->installations_total ?? 0 }}</div>
                            </div>
                        </div>
                        <div class="text-[11px] uppercase text-slate-500 pt-1">Ranking hoje</div>
                        <div id="team-ranking-list" class="space-y-1.5 text-xs max-h-40 overflow-y-auto">
                            @forelse(($teamMetrics?->seller_productivity ?? []) as $row)
                                <button type="button"
                                        class="team-seller-row w-full flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 bg-slate-950/60 border border-slate-800 text-left hover:border-sky-500/40"
                                        data-seller-id="{{ $row['user_id'] }}">
                                    <span class="truncate">{{ $row['name'] }}</span>
                                    <span class="text-slate-400 shrink-0">{{ $row['visits'] }}v · {{ $row['interested'] ?? 0 }}i · {{ $row['installations'] }}c</span>
                                </button>
                            @empty
                                <div class="text-slate-500">Sem visitas da equipe hoje.</div>
                            @endforelse
                        </div>
                        <a href="{{ route('dashboard', ['period' => 'today']) }}" class="block text-center text-xs text-sky-300 py-1">Ver Resultados →</a>
                    </div>
                </div>
            @endunless

            <div class="rounded-xl bg-slate-900 border border-slate-800 p-3" id="commercial-region-panel">
                <div class="text-[11px] uppercase tracking-wide text-slate-400 mb-2">Região atual</div>
                <div class="text-2xl font-semibold" id="region-points">0</div>
                <div class="text-xs text-slate-500 mb-3">pontos na área</div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div><div class="text-[11px] text-slate-500">Clientes</div><div id="region-customers" class="font-semibold text-emerald-400">0</div></div>
                    <div><div class="text-[11px] text-slate-500">Interessados</div><div id="region-interested" class="font-semibold text-blue-400">0</div></div>
                    <div><div class="text-[11px] text-slate-500">Visitados</div><div id="region-visited" class="font-semibold text-amber-300">0</div></div>
                    <div><div class="text-[11px] text-slate-500">Sem abordagem</div><div id="region-new" class="font-semibold text-rose-400">0</div></div>
                </div>
                <div id="region-opportunity" class="mt-3 rounded-lg px-3 py-2 text-xs font-medium bg-slate-800 text-slate-300">
                    Sem dados na região
                </div>
                @unless(!empty($isFieldSeller))
                    <div class="mt-2 pt-2 border-t border-slate-800 space-y-1 text-[10px] text-slate-400">
                        <div><span class="text-emerald-400">●</span> Alto potencial · poucos clientes</div>
                        <div><span class="text-sky-400">●</span> Médio potencial</div>
                        <div><span class="text-slate-500">●</span> Baixo potencial · área trabalhada</div>
                    </div>
                @endunless
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div class="rounded-xl bg-slate-900 border border-slate-800 p-3">
                    <div class="text-[11px] text-slate-400">Visitas feitas</div>
                    <div class="text-xl font-semibold" id="metric-visits">{{ $dayMetrics->visits_total }}</div>
                </div>
                <div class="rounded-xl bg-slate-900 border border-slate-800 p-3">
                    <div class="text-[11px] text-slate-400">Interessados</div>
                    <div class="text-xl font-semibold" id="metric-interested">{{ $dayMetrics->interested_total }}</div>
                </div>
                <div class="rounded-xl bg-slate-900 border border-slate-800 p-3">
                    <div class="text-[11px] text-slate-400">Retornos</div>
                    <div class="text-xl font-semibold">{{ $dayMetrics->pending_follow_ups }}</div>
                </div>
                <div class="rounded-xl bg-slate-900 border border-slate-800 p-3">
                    <div class="text-[11px] text-slate-400">{{ $commercial::sales() }}</div>
                    <div class="text-xl font-semibold" id="metric-installations">{{ $dayMetrics->installations_total }}</div>
                </div>
            </div>

            <div class="rounded-xl bg-slate-900 border border-slate-800 p-3">
                <div class="text-[11px] text-slate-400 mb-1">Meta do dia</div>
                @if($dayGoal)
                    <div class="text-lg font-semibold">{{ $dayGoal->target_count }} visitas / R$ {{ number_format((float) $dayGoal->target_amount, 0, ',', '.') }}</div>
                    <div class="header-meta text-xs mt-1">Período {{ $dayGoal->period_start?->format('d/m') }} – {{ $dayGoal->period_end?->format('d/m') }}</div>
                @else
                    <div class="text-sm text-slate-400">Sem meta definida para hoje.</div>
                @endif
            </div>

            <div class="rounded-xl bg-slate-900 border border-slate-800 p-3">
                <div class="text-[11px] text-slate-400 mb-2">Pontos na tela</div>
                <div class="text-2xl font-semibold" id="metric-viewport">0</div>
                <div class="text-xs text-slate-500" id="map-status-message">Carregando mapa...</div>
                <div id="metric-status-breakdown" class="mt-3 space-y-1.5 text-xs"></div>
            </div>

            @if($permissions['properties_manage'])
                <button id="btn-new-point-side" type="button" class="map-btn-meu-local w-full h-12 rounded-xl bg-sky-500 hover:bg-sky-400 text-slate-950 font-bold inline-flex items-center justify-center gap-2">
                    <i data-lucide="map-pin" class="w-5 h-5" aria-hidden="true"></i>
                    <span>Meu Local</span>
                </button>
            @endif
        </div>
    </aside>

    {{-- Drawer comercial / campo --}}
    <aside id="marker-drawer" class="absolute right-0 top-0 bottom-0 z-40 w-[360px] max-w-[100vw] translate-x-full transition-transform duration-300 bg-slate-950 border-l border-slate-700 flex flex-col" aria-hidden="true">
        <div class="p-4 border-b border-slate-800 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="text-xs text-slate-400 uppercase tracking-wide">📍 Residência</div>
                <h2 id="drawer-name" class="text-xl font-bold truncate">—</h2>
                <div id="drawer-status" class="mt-2 inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-slate-800">
                    <span id="drawer-status-dot" class="w-2 h-2 rounded-full bg-slate-400"></span>
                    <span id="drawer-status-label">—</span>
                </div>
                <div id="drawer-distance" class="mt-1.5 text-sm text-sky-300 font-medium hidden">—</div>
                <div id="drawer-location-kind" class="mt-1.5 text-xs text-slate-400 hidden">
                    <span id="drawer-location-dot" class="inline-block w-2 h-2 rounded-full align-middle mr-1"></span>
                    <span id="drawer-location-label">—</span>
                </div>
            </div>
            <button id="drawer-close" type="button" class="p-2 rounded-lg hover:bg-slate-800 text-slate-400"><i data-lucide="x" class="w-4 h-4"></i></button>
        </div>
        <div class="p-4 space-y-3 overflow-y-auto flex-1 text-sm">
            <div class="field-primary-info space-y-3">
                <div>
                    <div class="text-[11px] uppercase text-slate-500">Cliente</div>
                    <div id="drawer-contact" class="font-semibold text-base">—</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase text-slate-500">Telefone</div>
                    <div id="drawer-phone" class="font-semibold text-base">—</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase text-slate-500">WhatsApp</div>
                    <div id="drawer-whatsapp" class="font-semibold text-base">—</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase text-slate-500">Situação</div>
                    <div id="drawer-status-text" class="font-medium">—</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase text-slate-500">Endereço</div>
                    <div id="drawer-address">—</div>
                </div>
            </div>
            <div class="drawer-client-dossier space-y-3">
                <div>
                    <div class="text-[11px] uppercase text-slate-500">Última visita</div>
                    <div id="drawer-updated">—</div>
                    <div id="drawer-last-visit-result" class="text-slate-400 text-xs mt-0.5">—</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase text-slate-500">Próxima ação</div>
                    <div id="drawer-next-action">—</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase text-slate-500">Retorno agendado</div>
                    <div id="drawer-next-follow-up">—</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase text-slate-500">Produto vendido</div>
                    <div id="drawer-sold-product">—</div>
                </div>
            </div>
            <div class="field-secondary-info space-y-3">
                <div><div class="text-[11px] uppercase text-slate-500">Responsável</div><div id="drawer-responsible">—</div></div>
                <div><div class="text-[11px] uppercase text-slate-500">Campanha</div><div id="drawer-campaign">—</div></div>
                <div class="hidden"><div id="drawer-gps"></div><div id="drawer-created-by"></div><div id="drawer-created-at"></div><div id="drawer-last-visit"></div></div>
                <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-3 drawer-history-block">
                    <div class="text-[11px] uppercase text-slate-500 mb-2">Histórico</div>
                    <div id="drawer-history" class="space-y-2 text-xs text-slate-400 max-h-36 overflow-y-auto"></div>
                </div>
            </div>
        </div>
        <div class="p-3 border-t border-slate-800 space-y-2">
            <div class="grid grid-cols-2 gap-2">
                <a id="action-call" href="#" class="h-12 rounded-xl bg-slate-800 text-sm font-semibold inline-flex items-center justify-center gap-1.5">Ligar</a>
                <a id="action-whatsapp" href="#" target="_blank" rel="noopener" class="h-12 rounded-xl bg-emerald-600 text-white text-sm font-semibold inline-flex items-center justify-center gap-1.5">WhatsApp</a>
                <a id="action-route" href="#" target="_blank" rel="noopener" class="h-12 rounded-xl border border-slate-700 text-sm font-medium inline-flex items-center justify-center col-span-2">Navegar</a>
                <button id="action-visit" type="button" class="h-14 rounded-xl bg-sky-500 text-slate-950 text-base font-bold col-span-2 shadow-lg shadow-sky-500/20">Registrar visita</button>
            </div>
            <details class="drawer-more-actions rounded-xl border border-slate-800 bg-slate-900/40">
                <summary class="cursor-pointer list-none px-3 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wide flex items-center justify-between">
                    Mais opções
                    <span class="text-slate-600 normal-case font-normal">Editar · Ajustar · Remover</span>
                </summary>
                <div class="grid grid-cols-2 gap-2 p-2 pt-0 drawer-extra-actions">
                    <button id="action-edit" type="button" class="h-11 rounded-xl border border-slate-700 text-sm font-medium">✏ Editar</button>
                    <button id="action-adjust" type="button" class="h-11 rounded-xl border border-sky-600/50 text-sky-300 text-sm font-medium opacity-40" disabled>✏ Ajustar</button>
                    <button id="action-delete" type="button" class="h-11 rounded-xl border border-rose-700/60 text-rose-300 text-sm font-medium col-span-2">Remover</button>
                </div>
            </details>
        </div>
    </aside>

    {{-- Banner: modo arrastar --}}
    <div id="adjust-banner" class="hidden absolute top-20 inset-x-3 z-40 md:left-1/2 md:-translate-x-1/2 md:max-w-md pointer-events-none">
        <div class="pointer-events-auto rounded-2xl bg-slate-950/95 border border-sky-500/40 px-4 py-3 shadow-xl flex items-center gap-3">
            <div class="flex-1 text-sm text-slate-100 font-medium" id="adjust-banner-text">Arraste o ponto até a posição correta.</div>
            <button type="button" id="adjust-banner-cancel" class="h-10 px-3 rounded-xl border border-slate-600 text-sm shrink-0">Cancelar</button>
        </div>
    </div>
    <div id="drawer-backdrop" class="absolute inset-0 z-30 bg-black/40 opacity-0 pointer-events-none transition-opacity lg:hidden"></div>

    {{-- Sprint 8.2.7: etapa "Casa sem cadastro" removida — clique no mapa abre o formulário direto --}}
    <div id="empty-spot-modal" class="fixed inset-0 z-50 hidden" hidden aria-hidden="true"></div>

    {{-- Delete confirm --}}
    <div id="delete-point-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div id="delete-point-backdrop" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full max-w-sm rounded-2xl bg-slate-950 border border-slate-700 p-5">
            <h3 class="text-lg font-semibold mb-1">Remover este ponto?</h3>
            <p class="text-slate-400 text-sm mb-3">Ele sai do mapa, mas o histórico fica guardado.</p>
            <label class="text-xs text-slate-400">Motivo (opcional)</label>
            <input id="delete-point-reason" class="mt-1 w-full h-11 rounded-xl bg-slate-900 border border-slate-700 px-3 mb-4" placeholder="Cadastro errado…">
            <p id="delete-point-error" class="text-sm text-rose-400 hidden mb-2"></p>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" id="delete-point-cancel" class="h-12 rounded-xl border border-slate-700">Cancelar</button>
                <button type="button" id="delete-point-confirm" class="h-12 rounded-xl bg-rose-600 text-white font-bold">Confirmar</button>
            </div>
        </div>
    </div>

    {{-- Confirm new location --}}
    <div id="adjust-confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div id="adjust-confirm-backdrop" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full max-w-sm rounded-2xl bg-slate-950 border border-slate-700 p-5">
            <h3 class="text-lg font-semibold mb-1">Nova localização encontrada.</h3>
            <p class="text-slate-400 text-sm mb-3">Salvar ajuste?</p>
            <div class="rounded-xl bg-slate-900 border border-slate-800 p-3 mb-4 text-sm space-y-1">
                <div><span class="text-slate-500">Latitude:</span> <span id="adjust-confirm-lat" class="font-mono text-sky-300">—</span></div>
                <div><span class="text-slate-500">Longitude:</span> <span id="adjust-confirm-lng" class="font-mono text-sky-300">—</span></div>
            </div>
            <p id="adjust-confirm-error" class="text-sm text-rose-400 hidden mb-2"></p>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" id="adjust-confirm-cancel" class="h-12 rounded-xl border border-slate-700">Cancelar</button>
                <button type="button" id="adjust-confirm-save" class="h-12 rounded-xl bg-sky-500 text-slate-950 font-bold">Salvar posição</button>
            </div>
        </div>
    </div>

    {{-- After create: optional adjust --}}
    <div id="post-create-adjust-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div id="post-create-adjust-backdrop" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full max-w-sm rounded-2xl bg-slate-950 border border-slate-700 p-5 text-center">
            <h3 class="text-lg font-semibold mb-1">Ponto salvo</h3>
            <p class="text-slate-400 text-sm mb-4">Ajustar posição no mapa?</p>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" id="post-create-adjust-skip" class="h-12 rounded-xl border border-slate-700">Agora não</button>
                <button type="button" id="post-create-adjust-yes" class="h-12 rounded-xl bg-sky-500 text-slate-950 font-bold">Ajustar</button>
            </div>
        </div>
    </div>

    {{-- Visit modal — registro rápido --}}
    <div id="visit-modal" class="fixed inset-0 z-50 hidden items-end sm:items-center justify-center p-0 sm:p-4">
        <div id="visit-modal-backdrop" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full sm:max-w-md rounded-t-3xl sm:rounded-2xl bg-slate-950 border border-slate-700 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">Como foi?</h3>
                <button id="visit-modal-close" type="button" class="p-2 rounded-lg hover:bg-slate-800"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <form id="visit-form" class="space-y-3">
                <input type="hidden" name="property_id" id="visit-property-id">
                <input type="hidden" name="status" id="visit-status" value="">
                <div class="visit-campaign-block">
                    <label class="text-xs text-slate-400">Campanha</label>
                    <select name="campaign_id" id="visit-campaign-id" required class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3">
                        <option value="">Qual campanha?</option>
                        @foreach($campaigns as $campaign)
                            <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 gap-2" id="visit-quick-group">
                    <button type="button" class="visit-quick h-14 rounded-xl border border-slate-700 bg-slate-900 text-left px-4 font-semibold text-sm has-active:border-sky-400" data-status="interested">✅ Cliente interessado</button>
                    <button type="button" class="visit-quick h-14 rounded-xl border border-slate-700 bg-slate-900 text-left px-4 font-semibold text-sm" data-status="no_interest">❌ Sem interesse</button>
                    <button type="button" class="visit-quick h-14 rounded-xl border border-slate-700 bg-slate-900 text-left px-4 font-semibold text-sm" data-status="return_later">🔄 Retornar depois</button>
                    <button type="button" class="visit-quick h-14 rounded-xl border border-slate-700 bg-slate-900 text-left px-4 font-semibold text-sm" data-status="not_home">🏠 Não encontrado</button>
                    <button type="button" class="visit-quick h-14 rounded-xl border border-slate-700 bg-slate-900 text-left px-4 font-semibold text-sm" data-status="installation_requested">📄 {{ $commercial::saleCompleted() }}</button>
                </div>
                @include('partials.sale-finalize-fields', [
                    'prefix' => 'visit',
                    'requiredChecklist' => $saleRequiredChecklist ?? [],
                    'sellableProducts' => $sellableProducts ?? [],
                ])
                <div class="visit-notes-block">
                    <label class="text-xs text-slate-400">Anotação da visita <span class="text-slate-600" id="visit-notes-hint">(opcional)</span></label>
                    <textarea name="notes" id="visit-notes" rows="2" class="mt-1 w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2" placeholder="Ex.: voltar amanhã à tarde"></textarea>
                </div>
                <div id="visit-return-block" class="hidden space-y-2">
                    <label class="text-xs text-slate-400">Sugestão de retorno <span class="text-slate-600">(opcional)</span></label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[11px] text-slate-500" for="visit-follow-up-date">Data</label>
                            <input type="date" id="visit-follow-up-date" class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3">
                        </div>
                        <div>
                            <label class="text-[11px] text-slate-500" for="visit-follow-up-time">Horário <span class="text-slate-600">(opc.)</span></label>
                            <input type="time" id="visit-follow-up-time" class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3">
                        </div>
                    </div>
                </div>
                <p id="visit-error" class="text-sm text-rose-400 hidden"></p>
                <button type="submit" id="visit-submit" class="w-full h-14 rounded-xl bg-sky-500 text-slate-950 font-bold">Salvar visita</button>
            </form>
        </div>
    </div>

    {{-- Pós-visita --}}
    <div id="post-visit-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div id="post-visit-backdrop" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full max-w-sm rounded-2xl bg-slate-950 border border-slate-700 p-5 text-center">
            <div class="text-3xl mb-2">✅</div>
            <h3 class="text-lg font-semibold mb-1">Visita salva</h3>
            <p class="text-slate-400 text-sm mb-4">Pronto. Continuar na rua?</p>
            <button type="button" id="post-visit-next" class="w-full h-14 rounded-xl bg-sky-500 text-slate-950 font-bold mb-2">Continuar no mapa</button>
            <button type="button" id="post-visit-close" class="w-full h-11 rounded-xl border border-slate-700 text-sm">Ficar aqui</button>
        </div>
    </div>

    {{-- New point modal --}}
    <div id="point-modal" class="fixed inset-0 z-50 hidden items-end sm:items-center justify-center p-0 sm:p-4">
        <div id="point-modal-backdrop" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full sm:max-w-md rounded-t-3xl sm:rounded-2xl bg-slate-950 border border-slate-700 p-5 max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold" id="point-modal-title">Meu Local</h3>
                <button id="point-modal-close" type="button" class="p-2 rounded-lg hover:bg-slate-800"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <div class="rounded-xl bg-slate-900 border border-slate-800 p-3 mb-4 text-sm">
                <div class="text-slate-400 text-xs uppercase mb-1" id="point-gps-title">Capturando localização...</div>
                <div id="point-gps-label" class="font-mono text-xs text-sky-300">Aguarde um instante</div>
                <div class="text-xs text-slate-500 mt-1" id="point-meta-label">Você: {{ $sellerName }}</div>
                <div class="text-xs text-emerald-400 mt-0.5 font-medium" id="point-accuracy-label"></div>
                <div class="text-xs text-slate-400 mt-0.5" id="point-accuracy-class"></div>
                <button type="button" id="point-adjust-on-map" class="mt-3 w-full h-11 rounded-xl border border-sky-600/50 text-sky-300 text-sm font-medium hidden">
                    ✏ Ajustar posição no mapa
                </button>
            </div>
            <form id="point-form" class="space-y-3">
                <input type="hidden" id="point-property-id" name="property_id">
                <input type="hidden" id="point-latitude" name="latitude">
                <input type="hidden" id="point-longitude" name="longitude">
                <input type="hidden" id="point-gps-accuracy" name="gps_accuracy">
                <input type="hidden" id="point-mode" value="create">
                <div class="point-field-primary">
                    <label class="text-xs text-slate-400">Nome</label>
                    <input id="point-contact-name" name="contact_name" class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3" placeholder="Nome de quem atendeu" autocomplete="name">
                </div>
                <div class="point-field-primary">
                    <label class="text-xs text-slate-400">Telefone</label>
                    <input id="point-contact-phone" name="contact_phone" class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3" inputmode="tel" placeholder="WhatsApp" autocomplete="tel">
                </div>
                <div class="point-field-primary">
                    <label class="text-xs text-slate-400">Observação <span class="text-slate-600" id="point-notes-hint">(opcional)</span></label>
                    <textarea id="point-notes" name="notes" rows="2" class="mt-1 w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2" placeholder="Referência, horário, portão…"></textarea>
                </div>
                <div class="point-address-block space-y-3">
                    <div>
                        <label class="text-xs text-slate-400">Cidade</label>
                        <select id="point-city-id" name="city_id" required class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3">
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}">{{ $city->name }}/{{ $city->state }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="col-span-2">
                            <label class="text-xs text-slate-400">Rua <span class="text-slate-600 field-seller-optional">(opcional no campo)</span></label>
                            <input id="point-street" name="street" class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3" placeholder="Nome da rua">
                        </div>
                        <div>
                            <label class="text-xs text-slate-400">Nº</label>
                            <input id="point-number" name="number" class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3" placeholder="S/N">
                        </div>
                    </div>
                </div>
                {{-- Manager: status do imóvel. Seller create: resultado do 1º atendimento (VisitStatus). --}}
                <div id="point-manager-status" class="{{ !empty($isFieldSeller) ? 'hidden' : '' }}">
                    <label class="text-xs text-slate-400 mb-2 block">Situação</label>
                    <div class="grid grid-cols-2 gap-2" id="point-status-group">
                        @foreach($quickStatuses as $value => $label)
                            <label class="flex items-center gap-2 h-12 px-3 rounded-xl border border-slate-700 bg-slate-900 cursor-pointer has-[:checked]:border-sky-400 has-[:checked]:bg-sky-500/10">
                                <input type="radio" name="status" value="{{ $value }}" class="accent-sky-400" @checked($value === 'interested')>
                                <span class="text-sm">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div id="point-first-approach" class="{{ empty($isFieldSeller) ? 'hidden' : '' }} space-y-3">
                    <div id="point-campaign-block">
                        <label class="text-xs text-slate-400">Campanha</label>
                        <select id="point-campaign-id" class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3">
                            <option value="">Qual campanha?</option>
                            @foreach(($sellerCampaigns ?? []) as $campaign)
                                <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-500 mt-1" id="point-campaign-hint"></p>
                    </div>
                    <div>
                        <label class="text-xs text-slate-400 mb-2 block">Resultado do atendimento</label>
                        <input type="hidden" id="point-visit-status" value="">
                        <div class="grid grid-cols-1 gap-2" id="point-outcome-group">
                            <button type="button" class="point-outcome h-12 rounded-xl border border-slate-700 bg-slate-900 text-left px-4 font-semibold text-sm" data-status="installation_requested">🟢 {{ $commercial::saleCompleted() }}</button>
                            <button type="button" class="point-outcome h-12 rounded-xl border border-slate-700 bg-slate-900 text-left px-4 font-semibold text-sm" data-status="interested">🔵 Interessado</button>
                            <button type="button" class="point-outcome h-12 rounded-xl border border-slate-700 bg-slate-900 text-left px-4 font-semibold text-sm" data-status="return_later">🟡 Retornar</button>
                            <button type="button" class="point-outcome h-12 rounded-xl border border-slate-700 bg-slate-900 text-left px-4 font-semibold text-sm" data-status="no_interest">🔴 Sem interesse</button>
                            <button type="button" class="point-outcome h-12 rounded-xl border border-slate-700 bg-slate-900 text-left px-4 font-semibold text-sm" data-status="not_home">⚪ Não encontrado</button>
                        </div>
                    </div>
                    @include('partials.sale-finalize-fields', [
                        'prefix' => 'point',
                        'requiredChecklist' => $saleRequiredChecklist ?? [],
                        'sellableProducts' => $sellableProducts ?? [],
                    ])
                    <div id="point-return-block" class="hidden space-y-2">
                        <label class="text-xs text-slate-400">Sugestão de retorno <span class="text-slate-600">(opcional)</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-[11px] text-slate-500" for="point-follow-up-date">Data</label>
                                <input type="date" id="point-follow-up-date" class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3">
                            </div>
                            <div>
                                <label class="text-[11px] text-slate-500" for="point-follow-up-time">Horário <span class="text-slate-600">(opc.)</span></label>
                                <input type="time" id="point-follow-up-time" class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3">
                            </div>
                        </div>
                    </div>
                </div>
                <p id="point-error" class="text-sm text-rose-400 hidden"></p>
                <button type="submit" id="point-submit" class="w-full h-14 rounded-xl bg-sky-500 text-slate-950 font-bold text-base">Salvar atendimento</button>
            </form>
        </div>
    </div>
    {{-- Prep: campanha por região (sem criar campanha ainda) --}}
    <div id="region-campaign-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div id="region-campaign-backdrop" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full max-w-sm rounded-2xl bg-slate-950 border border-slate-700 p-5 text-center">
            <h3 class="text-lg font-semibold mb-1">Campanha por região</h3>
            <p class="text-slate-400 text-sm mb-2">Área selecionada no mapa.</p>
            <p class="text-xs text-slate-500 mb-4" id="region-campaign-bounds">—</p>
            <p class="text-sm text-sky-300 mb-4">Em breve: criar campanha a partir desta área.</p>
            <button type="button" id="region-campaign-close" class="w-full h-12 rounded-xl bg-sky-500 text-slate-950 font-bold">Entendi</button>
        </div>
    </div>

    @if(!empty($isFieldSeller))
    {{-- Seller day brief — tela inicial de campo --}}
    <div id="seller-day-brief" class="fixed inset-0 z-[70] hidden flex-col justify-end sm:justify-center items-stretch sm:items-center p-0 sm:p-6 bg-slate-950/95 backdrop-blur-sm">
        <div class="w-full sm:max-w-md rounded-t-3xl sm:rounded-3xl bg-slate-900 border border-slate-700 p-6 pb-8 shadow-2xl">
            <p class="text-sky-300 text-sm font-medium mb-1">Expandor · Campo</p>
            <h2 class="text-2xl font-bold text-white mb-1" id="seller-brief-greeting">Olá, {{ \Illuminate\Support\Str::of($sellerName ?? '')->before(' ') }}</h2>
            <p class="text-slate-400 text-sm mb-5">Resumo do seu dia</p>
            <div class="grid grid-cols-3 gap-2 mb-4">
                <div class="rounded-2xl bg-slate-950 border border-slate-800 p-3 text-center">
                    <div class="text-lg mb-0.5" aria-hidden="true">🏠</div>
                    <div class="text-2xl font-bold text-white" id="brief-visits">{{ $dayMetrics->visits_total ?? 0 }}</div>
                    <div class="text-[11px] text-slate-400 mt-1 leading-tight">Casas visitadas hoje</div>
                </div>
                <div class="rounded-2xl bg-slate-950 border border-slate-800 p-3 text-center">
                    <div class="text-lg mb-0.5" aria-hidden="true">⭐</div>
                    <div class="text-2xl font-bold text-sky-300" id="brief-interested">{{ $dayMetrics->interested_total ?? 0 }}</div>
                    <div class="text-[11px] text-slate-400 mt-1 leading-tight">Interessados</div>
                </div>
                <div class="rounded-2xl bg-slate-950 border border-slate-800 p-3 text-center">
                    <div class="text-lg mb-0.5" aria-hidden="true">📄</div>
                    <div class="text-2xl font-bold text-emerald-400" id="brief-contracts">{{ $dayMetrics->installations_total ?? 0 }}</div>
                    <div class="text-[11px] text-slate-400 mt-1 leading-tight">{{ $commercial::sales() }}</div>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-950/80 p-4 mb-5">
                <div class="text-xs uppercase tracking-wide text-slate-500 mb-1">Meu Local</div>
                <p class="text-sm text-slate-200" id="brief-next-house">Toque em Meu Local para cadastrar a partir do GPS, sem etapas extras.</p>
            </div>
            <button type="button" id="seller-start-route" class="w-full h-16 rounded-2xl bg-sky-500 text-slate-950 font-extrabold text-lg shadow-[0_12px_32px_rgba(14,165,233,0.45)]">
                Começar no mapa
            </button>
        </div>
    </div>

    {{-- First-login field tips --}}
    <div id="seller-tips-modal" class="fixed inset-0 z-[80] hidden flex-col justify-end sm:justify-center items-stretch sm:items-center p-0 sm:p-6 bg-slate-950/95 backdrop-blur-sm">
        <div class="w-full sm:max-w-md rounded-t-3xl sm:rounded-3xl bg-slate-900 border border-slate-700 p-6 pb-8">
            <p class="text-sky-300 text-sm font-medium mb-1">Primeiros passos</p>
            <h2 class="text-xl font-bold text-white mb-4">Como trabalhar no mapa</h2>
            <ol class="space-y-3 mb-6 text-sm text-slate-200">
                <li class="flex gap-3 items-start">
                    <span class="shrink-0 w-8 h-8 rounded-full bg-sky-500/20 text-sky-300 font-bold flex items-center justify-center">1</span>
                    <span><strong class="text-white">Meu Local</strong> — use o GPS para abrir o cadastro na sua posição.</span>
                </li>
                <li class="flex gap-3 items-start">
                    <span class="shrink-0 w-8 h-8 rounded-full bg-sky-500/20 text-sky-300 font-bold flex items-center justify-center">2</span>
                    <span><strong class="text-white">Registre o resultado</strong> — ao salvar o ponto, diga como foi o atendimento.</span>
                </li>
                <li class="flex gap-3 items-start">
                    <span class="shrink-0 w-8 h-8 rounded-full bg-sky-500/20 text-sky-300 font-bold flex items-center justify-center">3</span>
                    <span><strong class="text-white">Continue na rua</strong> — toque no mapa ou use Meu Local de novo.</span>
                </li>
            </ol>
            <button type="button" id="seller-tips-continue" class="w-full h-14 rounded-2xl bg-sky-500 text-slate-950 font-bold mb-2">Entendi, vamos lá</button>
            <button type="button" id="seller-tips-skip" class="w-full h-11 rounded-xl border border-slate-700 text-sm text-slate-300">Pular</button>
        </div>
    </div>
    @endif
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" crossorigin="">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" crossorigin="">
<style>
    .op-main { height: 100vh; }
    @media (max-width: 900px) { .op-main { height: calc(100dvh - 74px); } }
    .leaflet-container { background: #0b1220; font: inherit; }
    .map-marker-wrap { position: relative; width: 18px; height: 18px; }
    .map-marker-dot {
        width: 14px; height: 14px; border-radius: 50%; border: 2px solid #fff;
        box-shadow: 0 0 0 1px rgba(0,0,0,.35); position: absolute; left: 2px; top: 2px;
    }
    .map-marker-ring {
        position: absolute; inset: 0; border-radius: 50%; border: 2px solid transparent; pointer-events: none;
    }
    .map-marker-ring.kind-gps { border-color: #22c55e; }
    .map-marker-ring.kind-adjusted { border-color: #3b82f6; }
    .map-marker-ring.kind-low_accuracy { border-color: #eab308; }
    .map-marker-dragging .map-marker-dot { transform: scale(1.25); }
    .commercial-cluster { background: transparent !important; border: 0 !important; }
    .commercial-cluster-bubble {
        border-radius: 999px; background: color-mix(in srgb, var(--cluster-color) 88%, #0f172a);
        color: #0f172a; font-weight: 800; display: flex; flex-direction: column;
        align-items: center; justify-content: center; box-shadow: 0 0 0 2px #fff, 0 4px 14px rgba(0,0,0,.35);
        line-height: 1.05; font-size: 12px;
    }
    .commercial-cluster-bubble strong { font-size: 13px; }
    .commercial-cluster-mix { font-size: 8px; font-weight: 600; max-width: 90%; overflow: hidden; white-space: nowrap; }
    .basemap-btn.is-active { border-color: #38bdf8; color: #e0f2fe; background: rgba(14,165,233,.15); }
    .leaflet-region-select { stroke: #38bdf8; stroke-width: 2; stroke-dasharray: 6 4; fill: rgba(56,189,248,.12); }
    #metrics-panel.open, #marker-drawer.open { transform: translateX(0); }
    #drawer-backdrop.open { opacity: 1; pointer-events: auto; }
    #visit-modal.open, #point-modal.open, #empty-spot-modal.open, #delete-point-modal.open,
    #adjust-confirm-modal.open, #post-create-adjust-modal.open, #region-campaign-modal.open,
    #post-visit-modal.open, #seller-day-brief.open, #seller-tips-modal.open { display: flex; }
    #adjust-banner:not(.hidden) { display: block; }
    #map-empty-state.visible { display: flex; }
    #map-filters-form.open { display: grid !important; }
    #commercial-filters.open { display: block !important; }
    #map-legend-panel.open { display: block !important; }
    .visit-quick.is-selected { border-color: #38bdf8 !important; background: rgba(14,165,233,.12) !important; }
    #map-empty-state { z-index: 10; }
    #offline-queue-badge:not(.hidden) { display: inline-flex; }

    /* Manager vs Seller chrome — também via data-attr (não depende só de body.field-seller) */
    body.field-seller .manager-only,
    #map-page[data-is-field-seller="1"] .manager-only { display: none !important; }
    body:not(.field-seller) .field-seller-only { display: none !important; }
    body.field-seller .field-seller-only { display: inline-flex; }

    body.field-seller #metrics-panel,
    #map-page[data-is-field-seller="1"] #metrics-panel { display: none !important; }
    body.field-seller header.absolute { padding-right: 0.75rem !important; padding-left: 0.75rem !important; }
    body.field-seller header.absolute > div > div.flex { justify-content: flex-start; }
    /* Sprint 5.2.2 — Seller usa a mesma barra de busca do Manager */
    body.field-seller #map-search-wrap,
    #map-page[data-is-field-seller="1"] #map-search-wrap { display: flex !important; }

    /* UX 3.3.1: Seller — zoom, créditos, Mostrar e legenda sempre ocultos */
    body.field-seller .leaflet-control-attribution,
    body.field-seller .leaflet-control-zoom,
    #map-page[data-is-field-seller="1"] .leaflet-control-attribution,
    #map-page[data-is-field-seller="1"] .leaflet-control-zoom,
    #map-page[data-is-field-seller="1"] .leaflet-bottom.leaflet-right,
    body.field-seller #commercial-filters,
    body.field-seller #commercial-filters.open,
    #map-page[data-is-field-seller="1"] #commercial-filters,
    #map-page[data-is-field-seller="1"] #commercial-filters.open,
    body.field-seller #map-legend-panel,
    body.field-seller #map-legend-panel.open,
    #map-page[data-is-field-seller="1"] #map-legend-panel,
    #map-page[data-is-field-seller="1"] #map-legend-panel.open {
        display: none !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }

    body.field-seller .field-secondary-info,
    body.field-seller .drawer-history-block { display: none; }
    body.field-seller .drawer-client-dossier { display: block; }
    .map-marker-selected {
        filter: drop-shadow(0 0 6px rgba(56, 189, 248, 0.95));
        transform: scale(1.28);
        z-index: 600 !important;
    }
    #map-search-results button {
        display: block; width: 100%; text-align: left;
        padding: 0.7rem 0.85rem; border: 0; border-bottom: 1px solid #1e293b;
        background: transparent; color: #e2e8f0; cursor: pointer;
    }
    #map-search-results button:hover,
    #map-search-results button:focus { background: #0f172a; outline: none; }
    #map-search-results button:last-child { border-bottom: 0; }
    #map-search-results .search-hit-meta { font-size: 0.75rem; color: #94a3b8; margin-top: 0.15rem; }
    body.field-seller #map-filters-form { display: none !important; }
    #basemap-controls { bottom: 5.5rem; }
    body.field-seller #basemap-controls {
        bottom: 5.5rem;
        left: 0.75rem;
    }
    body.field-seller #basemap-controls .basemap-btn {
        height: 2rem;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
        font-size: 10px;
        border-radius: 0.65rem;
    }
    @media (min-width: 640px) {
        body.field-seller #basemap-controls { bottom: 1.5rem; }
        .map-toolbar { padding-top: 1rem !important; }
    }
    @media (max-width: 900px) {
        .map-toolbar { padding-top: 4.25rem !important; }
    }
    body.field-seller .visit-notes-block { display: none; }
    body.field-seller .visit-campaign-block select { font-size: 14px; }
    body.field-seller .point-address-block { display: none; }
    body.field-seller .field-seller-optional { display: inline; }
    body:not(.field-seller) .field-seller-optional { display: none; }
    body.field-seller #filter-seller { display: none; }
    body.field-seller #map-filters-form.open { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (min-width: 768px) {
        body.field-seller #map-filters-form.open { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    @media (max-width: 640px) {
        #commercial-filters { width: min(180px, 44vw); }
        #commercial-filters .text-xs { font-size: 11px; }
        body.field-seller #point-modal .relative,
        body.field-seller #visit-modal .relative { max-height: 88dvh; overflow-y: auto; }
        .map-btn-meu-local span { display: inline !important; }
        .map-btn-meu-local {
            min-height: 3rem;
            font-size: 0.95rem;
        }
        body.field-seller #marker-drawer {
            width: min(100vw, 400px);
            max-height: 78dvh;
            top: auto;
            bottom: 0;
            border-radius: 1.25rem 1.25rem 0 0;
            border-left: 0;
            border-top: 1px solid rgb(51 65 85);
        }
        body.field-seller #basemap-controls { bottom: 5.5rem; }
    }
    body.adjust-mode { cursor: grab; }
    body.adjust-mode .leaflet-marker-draggable { cursor: grabbing; }
    body.region-select-mode { cursor: crosshair; }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js" crossorigin=""></script>
<script src="{{ asset('js/map-provider.js') }}?v=2"></script>
<script src="{{ asset('js/field-offline-queue.js') }}?v=3"></script>
<script src="{{ asset('js/operational-map.js') }}?v=42"></script>
@endpush
