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
    data-commission-awarded-flash='@json($commissionAwardedFlash ?? null)'
    data-sale-required-fields='@json($saleRequiredFields ?? [])'
    data-sale-field-labels='@json($saleFieldLabels ?? [])'
    data-sectors='@json($sectors->map(fn ($s) => ["id" => $s->id, "city_id" => $s->city_id, "name" => $s->name])->values())'
    data-legend='@json($legend)'
    data-commercial-legend='@json($commercialLegend)'
    data-sellers='@json($sellers->map(fn ($s) => ["id" => $s->id, "name" => $s->name])->values())'
    data-leaflet-version="1.9.4"
    data-markercluster-version="1.5.3"
    data-googlemutant-version="0.14.1"
    data-map-provider="{{ $mapFrontendConfig->provider }}"
    data-map-fallback="{{ $mapFrontendConfig->fallback }}"
    data-map-provider-reason="{{ $mapFrontendConfig->reason }}"
    @if(!empty($mapFrontendConfig->forceFailure))
    data-map-force-google-failure="1"
    @endif
    data-map-provider-fallback-url="{{ $mapProviderFallbackUrl }}"
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
                    @if(!empty($isFieldSeller))
                        <a href="{{ $urls['follow_ups_today'] ?? route('follow-ups.index', ['day' => 'today']) }}"
                           id="map-today-chip"
                           class="h-12 md:h-14 px-3 rounded-2xl bg-slate-900/90 border border-slate-700/80 text-slate-200 inline-flex items-center gap-1.5 shrink-0 text-sm font-semibold no-underline hover:border-sky-500/50"
                           title="{{ ($todayFollowUpsCount ?? 0) > 0 ? 'Ver retornos de hoje' : 'Nenhum retorno hoje' }}">
                            <span class="text-slate-300">Hoje</span>
                            <span class="text-sky-300">·</span>
                            <span id="map-today-count" class="tabular-nums text-sky-300">{{ (int) ($todayFollowUpsCount ?? 0) }}</span>
                        </a>
                    @endif
                    <span id="offline-queue-badge" class="hidden h-12 md:h-14 px-3 rounded-2xl bg-amber-500/20 border border-amber-500/40 text-amber-200 text-xs font-semibold items-center gap-1 shrink-0">
                        <span id="offline-queue-count">0</span> pendente(s)
                    </span>
                    @unless(!empty($isFieldSeller))
                    <button type="button" id="btn-map-filters"
                            class="h-12 md:h-14 px-3 rounded-2xl bg-slate-900/90 border border-slate-700/80 text-slate-200 inline-flex items-center gap-2 shrink-0"
                            aria-expanded="false" aria-controls="map-company-filters-panel" title="Filtros do mapa">
                        <i data-lucide="sliders-horizontal" class="w-5 h-5 shrink-0"></i>
                        <span class="text-sm font-semibold">
                            <span id="map-filters-label">Filtros</span><span id="map-filters-count-wrap" class="hidden"> · <span id="map-filters-count">0</span></span>
                        </span>
                    </button>
                    <button type="button" id="btn-map-legend"
                            class="h-12 md:h-14 px-3 rounded-2xl bg-slate-900/90 border border-slate-700/80 text-slate-200 inline-flex items-center gap-2 shrink-0"
                            aria-expanded="false" aria-controls="map-legend-panel" title="Legenda">
                        <i data-lucide="map" class="w-5 h-5 shrink-0"></i>
                        <span class="hidden sm:inline text-sm font-semibold">Legenda</span>
                    </button>
                    <details class="map-more-tools relative" id="map-more-tools">
                        <summary class="h-12 md:h-14 px-3 rounded-2xl bg-slate-900/90 border border-slate-700/80 text-slate-200 inline-flex items-center gap-2 cursor-pointer list-none"
                                 title="Mais ferramentas">
                            <i data-lucide="more-horizontal" class="w-5 h-5"></i>
                            <span class="hidden sm:inline text-sm font-semibold">Mais</span>
                        </summary>
                        <div class="absolute right-0 top-[calc(100%+0.4rem)] z-50 min-w-[13rem] rounded-xl border border-slate-700 bg-slate-950 shadow-xl p-2 flex flex-col gap-1">
                            <button id="toggle-metrics" type="button" class="h-11 px-3 rounded-lg text-left text-sm text-slate-100 hover:bg-slate-800 inline-flex items-center gap-2">
                                <i data-lucide="users" class="w-4 h-4"></i> Equipe
                            </button>
                            @if(auth()->user()?->hasPermission('sales_app.access'))
                                <a href="{{ route('sales-app.products.present') }}"
                                   class="h-11 px-3 rounded-lg text-left text-sm text-slate-100 hover:bg-slate-800 inline-flex items-center gap-2 no-underline">
                                    <i data-lucide="presentation" class="w-4 h-4"></i> Apresentar produtos
                                </a>
                            @endif
                            {{-- Compat: id antigo usado em testes/handlers --}}
                            <button id="toggle-filters-manager" type="button" class="sr-only" tabindex="-1" aria-hidden="true">Filtros</button>
                        </div>
                    </details>
                    @endunless
                </div>
            </div>

            @unless(!empty($isFieldSeller))
            <div id="map-company-filters-panel"
                 class="map-company-overlay hidden"
                 role="dialog" aria-label="Filtros do mapa" aria-hidden="true">
                <div class="map-company-overlay-card">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <div class="text-sm font-semibold text-slate-100">Filtros</div>
                        <button type="button" id="close-map-filters" class="p-2 rounded-lg text-slate-400 hover:bg-slate-800" aria-label="Fechar filtros">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <form id="map-filters-form" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <select id="filter-city" name="city_id" class="h-11 rounded-xl bg-slate-800 border border-slate-700 text-sm px-2 text-slate-100">
                            <option value="">Cidade</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}">{{ $city->name }}/{{ $city->state }}</option>
                            @endforeach
                        </select>
                        <select id="filter-sector" name="sector_id" class="h-11 rounded-xl bg-slate-800 border border-slate-700 text-sm px-2 text-slate-100">
                            <option value="">Bairro / Setor</option>
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
                        <select id="filter-status" name="property_status" class="h-11 rounded-xl bg-slate-800 border border-slate-700 text-sm px-2 text-slate-100 sm:col-span-2">
                            <option value="">Situação</option>
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>

                    <div id="commercial-filters" class="mt-4 pt-3 border-t border-slate-700">
                        <div class="text-[11px] uppercase tracking-wide text-slate-400 mb-2">Mostrar no mapa</div>
                        <div class="space-y-2 text-xs text-slate-100">
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="commercial-filter accent-emerald-400" data-group="customer" checked> Clientes</label>
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="commercial-filter accent-blue-400" data-group="interested" checked> Interessados</label>
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="commercial-filter accent-amber-400" data-group="visited" checked> Visitados</label>
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="commercial-filter accent-rose-400" data-group="new" checked> Novos pontos</label>
                            <label class="manager-only flex items-center gap-2 cursor-pointer pt-1 border-t border-slate-700"><input type="checkbox" id="filter-my-team" class="accent-sky-400"> Minha equipe</label>
                        </div>
                        @if(!empty($permissions['campaigns_manage']) || empty($isFieldSeller))
                            <button type="button" id="btn-select-region" class="mt-3 w-full h-10 rounded-xl border border-dashed border-slate-600 text-xs text-slate-300 hover:border-sky-500 hover:text-sky-300">
                                Selecionar área · campanha
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            @else
            {{-- Seller: filtros fora da UI; stubs para JS. --}}
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
    {{-- Basemap + Minha localização. Apresentar (azul) só no seller — no manager fica em Mais (evita sobreposição). --}}
    <div id="map-bottom-left-controls" class="absolute left-3 bottom-[5.5rem] sm:bottom-6 z-20 flex flex-col gap-2 pointer-events-auto">
        @if(!empty($isFieldSeller) && auth()->user()?->hasPermission('sales_app.access'))
            <a href="{{ route('sales-app.products.present') }}"
               id="btn-present-products"
               class="map-btn-present h-12 min-w-[3rem] sm:h-14 sm:w-auto sm:px-4 px-3 rounded-2xl bg-sky-500 text-slate-950 shadow-lg inline-flex items-center justify-center gap-2 font-bold text-sm"
               title="Abrir apresentação comercial para o cliente">
                <i data-lucide="presentation" class="w-5 h-5 shrink-0" aria-hidden="true"></i>
                <span>Apresentar produtos</span>
            </a>
        @endif
        <button type="button" id="btn-recenter-location"
                class="map-btn-recenter h-12 min-w-[3rem] sm:h-14 sm:w-auto sm:px-4 px-3 rounded-2xl bg-slate-900/95 border border-slate-600 text-slate-100 shadow-lg inline-flex items-center justify-center gap-2 font-semibold text-sm"
                title="Centralizar no GPS sem criar ponto" aria-label="Minha localização">
            <i data-lucide="locate-fixed" class="w-5 h-5 shrink-0" aria-hidden="true"></i>
            <span>Minha localização</span>
        </button>
        <div id="basemap-controls" class="flex gap-1">
            <button type="button" id="basemap-street" class="basemap-btn is-active h-10 px-3 rounded-xl bg-slate-900/95 border border-slate-600 text-xs font-semibold text-slate-100 shadow-lg">Rua</button>
            <button type="button" id="basemap-satellite" class="basemap-btn h-10 px-3 rounded-xl bg-slate-900/90 border border-slate-700 text-xs font-semibold text-slate-300 shadow-lg" title="Satélite">Satélite</button>
        </div>
    </div>

    @if(!empty($isFieldSeller))
    {{-- Seller: checkboxes comerciais ocultos (JS). --}}
    <div id="commercial-filters" class="hidden" aria-hidden="true">
        <input type="checkbox" class="commercial-filter" data-group="customer" checked>
        <input type="checkbox" class="commercial-filter" data-group="interested" checked>
        <input type="checkbox" class="commercial-filter" data-group="visited" checked>
        <input type="checkbox" class="commercial-filter" data-group="new" checked>
        <input type="checkbox" id="filter-my-team" checked>
        <button type="button" id="close-layers" class="field-seller-only hidden"></button>
    </div>
    @endif

    {{-- Sprint 8.2.7: CTA de rota removida — fluxo via Meu Local --}}

    {{-- Empty viewport hint (Sprint 8.2.26): não bloqueia; JS usa toast 1× por lifecycle --}}
    <div id="map-empty-state" class="hidden" aria-hidden="true" data-empty-hint="1"></div>

    <div id="map-empty-hint" class="map-empty-hint hidden" role="status" aria-live="polite" data-empty-hint-toast="1">
        <p class="map-empty-hint__text">Nenhum ponto nesta área. Toque no mapa para adicionar.</p>
    </div>

    <div id="map-legend-panel" class="map-legend-panel map-company-overlay{{ !empty($isFieldSeller) ? ' is-field-seller is-collapsed hidden' : ' is-collapsed hidden' }}"
         aria-hidden="true"
         @if(!empty($isFieldSeller)) data-collapsible="1" @endif>
        <div class="pointer-events-auto map-company-overlay-card map-legend-card">
            <div class="flex items-center justify-between gap-2 mb-2">
                <span class="text-[11px] uppercase tracking-wide text-slate-400 font-semibold">Legenda</span>
                <button type="button" id="toggle-legend" class="map-legend-toggle p-1.5 rounded-lg text-slate-400 hover:bg-slate-800"
                        aria-expanded="false" aria-controls="map-legend-body" title="Fechar legenda">
                    <i data-lucide="x" class="w-3.5 h-3.5" aria-hidden="true"></i>
                </button>
            </div>
            <div id="map-legend-body" class="map-legend-body">
                <ul class="space-y-1.5" id="map-legend-list">
                    @foreach($commercialLegend as $item)
                        <li class="flex items-center gap-2 text-xs text-slate-200">
                            <span class="map-legend-pin shrink-0" style="--pin-color: {{ $item['color'] }}" title="{{ $item['label'] }}">
                                <svg class="map-legend-pin-svg" viewBox="0 0 28 36" width="16" height="20" aria-hidden="true" focusable="false">
                                    <path class="map-house-pin-body" d="M14 1.6C8.15 1.6 3.4 6.5 3.4 12.6c0 7.35 10.6 21.9 10.6 21.9s10.6-14.55 10.6-21.9C24.6 6.5 19.85 1.6 14 1.6z"/>
                                    <path class="map-house-pin-house" d="M9.15 16.35 14 12.1l4.85 4.25V21.2h-2.75v-3.25h-4.2V21.2H9.15z"/>
                                </svg>
                                @if(!empty($item['mark']))
                                    <span class="map-legend-mark" aria-hidden="true">{{ $item['mark'] }}</span>
                                @endif
                            </span>
                            <span>{{ $item['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-2 pt-2 border-t border-slate-700 space-y-1 text-[10px] text-slate-400">
                    <div>Marca: R = retorno · × = sem interesse</div>
                </div>
                <div class="mt-2 pt-2 border-t border-slate-700 text-xs text-slate-400">
                    <span id="map-marker-count">0</span> pontos no mapa
                </div>
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

        </div>
    </aside>

    {{-- Drawer comercial / campo --}}
    <aside id="marker-drawer" class="absolute right-0 top-0 bottom-0 z-40 w-[360px] max-w-[100vw] translate-x-full transition-transform duration-300 bg-slate-950 border-l border-slate-700 flex flex-col" aria-hidden="true">
        <div class="p-4 border-b border-slate-800 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="text-xs text-slate-400 uppercase tracking-wide">Ponto</div>
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

    {{-- Sprint 8.2.7/8.2.8: sem modal intermediário — clique no mapa abre o formulário direto --}}

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

    {{-- Visit modal — Map Operation Sheet (Sprint 8.2.26) --}}
    <div id="visit-modal" class="fixed inset-0 z-[100] hidden items-end sm:items-center justify-center p-0 sm:p-4"
         role="dialog" aria-modal="true" aria-labelledby="visit-modal-title" data-map-operation-sheet="1">
        <div id="visit-modal-backdrop" class="absolute inset-0 bg-black/60"></div>
        <div class="map-sheet-panel map-operation-sheet relative w-full sm:max-w-md rounded-t-3xl sm:rounded-2xl bg-slate-950 border border-slate-700">
            <div class="map-sheet-header map-operation-header px-5 pt-5 pb-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold mb-0.5">Resultado da abordagem</p>
                        <h3 class="text-lg font-semibold leading-tight" id="visit-modal-title">Registrar visita</h3>
                        <p class="text-sm text-slate-400 mt-1 truncate" id="visit-modal-subtitle">Como foi a abordagem?</p>
                    </div>
                    <button id="visit-modal-close" type="button" class="p-2 rounded-lg hover:bg-slate-800 shrink-0" aria-label="Fechar">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            <div class="map-sheet-body map-operation-body px-5">
                <form id="visit-form" class="space-y-3 pb-3">
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
                    <div>
                        <p class="text-xs text-slate-400 mb-2 font-medium">Como foi a abordagem?</p>
                        <div class="grid grid-cols-1 gap-2" id="visit-quick-group">
                            @foreach(($outcomeStatuses ?? []) as $value => $meta)
                                <button type="button"
                                        class="visit-quick status-chip h-14 rounded-xl border border-slate-700 bg-slate-900 text-left px-4 font-semibold text-sm inline-flex items-center gap-2"
                                        data-status="{{ $value }}"
                                        data-status-color="{{ $meta['color'] }}"
                                        style="--status-color: {{ $meta['color'] }};">
                                    <span class="status-chip-swatch shrink-0" aria-hidden="true">{{ ($meta['mark'] ?? '') !== '' ? $meta['mark'] : '' }}</span>
                                    <span>{{ $meta['label'] }}</span>
                                </button>
                            @endforeach
                        </div>
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
                        <label class="text-xs text-slate-400">Quando voltar? <span class="text-rose-400/80">*</span></label>
                        <div class="flex flex-wrap gap-2" id="visit-return-shortcuts">
                            <button type="button" class="return-shortcut h-10 px-3 rounded-xl border border-slate-700 text-xs font-semibold text-slate-200" data-days="1" data-target="visit">Amanhã</button>
                            <button type="button" class="return-shortcut h-10 px-3 rounded-xl border border-slate-700 text-xs font-semibold text-slate-200" data-days="2" data-target="visit">+2 dias</button>
                            <button type="button" class="return-shortcut h-10 px-3 rounded-xl border border-slate-700 text-xs font-semibold text-slate-200" data-days="pick" data-target="visit">Escolher data</button>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-[11px] text-slate-500" for="visit-follow-up-date">Data</label>
                                <input type="date" id="visit-follow-up-date" class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3" min="{{ now()->toDateString() }}">
                            </div>
                            <div>
                                <label class="text-[11px] text-slate-500" for="visit-follow-up-time">Horário <span class="text-slate-600">(opc.)</span></label>
                                <input type="time" id="visit-follow-up-time" class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="map-sheet-footer map-operation-footer px-5 pt-2 space-y-2">
                <p id="visit-error" class="text-sm text-rose-400 hidden"></p>
                <div class="map-operation-actions">
                    <button type="button" id="visit-modal-cancel" class="map-operation-btn-secondary">Cancelar</button>
                    <button type="submit" form="visit-form" id="visit-submit" class="map-operation-btn-primary">Salvar visita</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Pós-visita --}}
    <div id="post-visit-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div id="post-visit-backdrop" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full max-w-sm rounded-2xl bg-slate-950 border border-slate-700 p-5 text-center">
            <div class="text-3xl mb-2" aria-hidden="true"><i data-lucide="check-circle-2" class="w-8 h-8 mx-auto text-emerald-400"></i></div>
            <h3 class="text-lg font-semibold mb-1">Visita salva</h3>
            <p class="text-slate-400 text-sm mb-4">Pronto. Continuar na rua?</p>
            <button type="button" id="post-visit-next" class="w-full h-14 rounded-xl bg-sky-500 text-slate-950 font-bold mb-2">Continuar no mapa</button>
            <button type="button" id="post-visit-close" class="w-full h-11 rounded-xl border border-slate-700 text-sm">Ficar aqui</button>
        </div>
    </div>

    {{-- New point modal — Map Operation Sheet (Sprint 8.2.26) --}}
    <div id="point-modal" class="fixed inset-0 z-[100] hidden items-end sm:items-center justify-center p-0 sm:p-4"
         role="dialog" aria-modal="true" aria-labelledby="point-modal-title" data-map-operation-sheet="1">
        <div id="point-modal-backdrop" class="absolute inset-0 bg-black/60"></div>
        <div class="map-sheet-panel map-operation-sheet relative w-full sm:max-w-md rounded-t-3xl sm:rounded-2xl bg-slate-950 border border-slate-700">
            <div class="map-sheet-header map-operation-header px-5 pt-5 pb-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold mb-0.5" id="point-modal-eyebrow">Adicionar local</p>
                        <h3 class="text-lg font-semibold leading-tight" id="point-modal-title">Novo ponto</h3>
                        <p class="text-sm text-slate-400 mt-1" id="point-modal-subtitle">Cadastre este local para iniciar uma abordagem.</p>
                    </div>
                    <button id="point-modal-close" type="button" class="p-2 rounded-lg hover:bg-slate-800 shrink-0" aria-label="Fechar">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            <div class="map-sheet-body map-operation-body px-5">
            <div class="rounded-xl bg-slate-900 border border-slate-800 p-3 mb-4 text-sm" id="point-gps-block">
                <div class="text-slate-400 text-xs uppercase mb-1" id="point-gps-title">Localização</div>
                <div id="point-gps-label" class="text-sm text-sky-300 font-medium">Posição pronta para registro</div>
                <div class="text-xs text-slate-500 mt-1 field-seller-hide-meta" id="point-meta-label">Você: {{ $sellerName }}</div>
                <div class="text-xs text-emerald-400 mt-0.5 font-medium" id="point-accuracy-label"></div>
                <div class="text-xs text-slate-400 mt-0.5 field-seller-hide-meta" id="point-accuracy-class"></div>
                <button type="button" id="point-adjust-on-map" class="mt-3 w-full h-11 rounded-xl border border-slate-600 text-slate-300 text-sm font-medium hidden">
                    Ajustar posição no mapa
                </button>
            </div>
            <form id="point-form" class="space-y-3 pb-3">
                <input type="hidden" id="point-property-id" name="property_id">
                <input type="hidden" id="point-latitude" name="latitude">
                <input type="hidden" id="point-longitude" name="longitude">
                <input type="hidden" id="point-gps-accuracy" name="gps_accuracy">
                <input type="hidden" id="point-mode" value="create">
                <div class="point-field-primary">
                    <label class="text-xs text-slate-400" for="point-contact-name">Nome / responsável</label>
                    <input id="point-contact-name" name="contact_name" class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3" placeholder="Quem atendeu" autocomplete="name">
                </div>
                <div class="point-field-primary">
                    <label class="text-xs text-slate-400" for="point-contact-phone">Telefone / WhatsApp</label>
                    <input id="point-contact-phone" name="contact_phone" class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3" inputmode="tel" placeholder="WhatsApp" autocomplete="tel">
                </div>
                <div class="point-field-primary">
                    <label class="text-xs text-slate-400" for="point-notes">Observação curta <span class="text-slate-600" id="point-notes-hint">(opcional)</span></label>
                    <textarea id="point-notes" name="notes" rows="2" class="mt-1 w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2" placeholder="Referência rápida…"></textarea>
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
                        @foreach($quickStatuses as $value => $meta)
                            @php
                                $statusLabel = is_array($meta) ? ($meta['label'] ?? $value) : $meta;
                                $statusColor = is_array($meta) ? ($meta['color'] ?? '#64748b') : '#64748b';
                                $statusMark = is_array($meta) ? ($meta['mark'] ?? '') : '';
                            @endphp
                            <label class="status-chip flex items-center gap-2 h-12 px-3 rounded-xl border border-slate-700 bg-slate-900 cursor-pointer"
                                   style="--status-color: {{ $statusColor }};"
                                   data-status-color="{{ $statusColor }}">
                                <input type="radio" name="status" value="{{ $value }}" class="sr-only peer" @checked($value === 'interested')>
                                <span class="status-chip-swatch shrink-0" aria-hidden="true">{{ $statusMark !== '' ? $statusMark : '' }}</span>
                                <span class="text-sm">{{ $statusLabel }}</span>
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
                        <label class="text-xs text-slate-400 mb-2 block">Situação / interesse</label>
                        <input type="hidden" id="point-visit-status" value="">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 field-seller-outcome-grid" id="point-outcome-group">
                            @foreach(($outcomeStatuses ?? []) as $value => $meta)
                                <button type="button"
                                        class="point-outcome status-chip h-14 rounded-xl border border-slate-700 bg-slate-900 text-left px-4 font-semibold text-sm inline-flex items-center gap-2 {{ $value === 'not_home' ? 'sm:col-span-2' : '' }}"
                                        data-status="{{ $value }}"
                                        data-status-color="{{ $meta['color'] }}"
                                        style="--status-color: {{ $meta['color'] }};">
                                    <span class="status-chip-swatch shrink-0" aria-hidden="true">{{ ($meta['mark'] ?? '') !== '' ? $meta['mark'] : '' }}</span>
                                    <span>{{ $meta['label'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    @include('partials.sale-finalize-fields', [
                        'prefix' => 'point',
                        'requiredChecklist' => $saleRequiredChecklist ?? [],
                        'sellableProducts' => $sellableProducts ?? [],
                    ])
                    <div id="point-return-block" class="hidden space-y-2">
                        <label class="text-xs text-slate-400">Quando voltar? <span class="text-rose-400/80">*</span></label>
                        <div class="flex flex-wrap gap-2" id="point-return-shortcuts">
                            <button type="button" class="return-shortcut h-10 px-3 rounded-xl border border-slate-700 text-xs font-semibold text-slate-200" data-days="1" data-target="point">Amanhã</button>
                            <button type="button" class="return-shortcut h-10 px-3 rounded-xl border border-slate-700 text-xs font-semibold text-slate-200" data-days="2" data-target="point">+2 dias</button>
                            <button type="button" class="return-shortcut h-10 px-3 rounded-xl border border-slate-700 text-xs font-semibold text-slate-200" data-days="pick" data-target="point">Escolher data</button>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-[11px] text-slate-500" for="point-follow-up-date">Data</label>
                                <input type="date" id="point-follow-up-date" class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3" min="{{ now()->toDateString() }}">
                            </div>
                            <div>
                                <label class="text-[11px] text-slate-500" for="point-follow-up-time">Horário <span class="text-slate-600">(opc.)</span></label>
                                <input type="time" id="point-follow-up-time" class="mt-1 w-full h-12 rounded-xl bg-slate-900 border border-slate-700 px-3">
                            </div>
                        </div>
                    </div>
                </div>
                </form>
            </div>
            <div class="map-sheet-footer map-operation-footer point-form-actions px-5 pt-2 space-y-2">
                <p id="point-error" class="text-sm text-rose-400 hidden"></p>
                <div class="map-operation-actions">
                    <button type="button" id="point-modal-cancel" class="map-operation-btn-secondary">Cancelar</button>
                    <button type="submit" form="point-form" id="point-submit" class="map-operation-btn-primary">Salvar ponto</button>
                </div>
            </div>
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
                    <div class="text-lg mb-0.5" aria-hidden="true"><i data-lucide="map-pin" class="w-5 h-5 mx-auto text-slate-300"></i></div>
                    <div class="text-2xl font-bold text-white" id="brief-visits">{{ $dayMetrics->visits_total ?? 0 }}</div>
                    <div class="text-[11px] text-slate-400 mt-1 leading-tight">Casas visitadas hoje</div>
                </div>
                <div class="rounded-2xl bg-slate-950 border border-slate-800 p-3 text-center">
                    <div class="text-lg mb-0.5" aria-hidden="true"><i data-lucide="star" class="w-5 h-5 mx-auto text-sky-300"></i></div>
                    <div class="text-2xl font-bold text-sky-300" id="brief-interested">{{ $dayMetrics->interested_total ?? 0 }}</div>
                    <div class="text-[11px] text-slate-400 mt-1 leading-tight">Interessados</div>
                </div>
                <div class="rounded-2xl bg-slate-950 border border-slate-800 p-3 text-center">
                    <div class="text-lg mb-0.5" aria-hidden="true"><i data-lucide="file-check" class="w-5 h-5 mx-auto text-emerald-400"></i></div>
                    <div class="text-2xl font-bold text-emerald-400" id="brief-contracts">{{ $dayMetrics->installations_total ?? 0 }}</div>
                    <div class="text-[11px] text-slate-400 mt-1 leading-tight">{{ $commercial::sales() }}</div>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-950/80 p-4 mb-5">
                <div class="text-xs uppercase tracking-wide text-slate-500 mb-1">Minha localização</div>
                <p class="text-sm text-slate-200" id="brief-next-house">Use Minha localização para se localizar. Depois toque no mapa para registrar o ponto.</p>
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
                    <span><strong class="text-white">Minha localização</strong> — centraliza o mapa na sua posição GPS.</span>
                </li>
                <li class="flex gap-3 items-start">
                    <span class="shrink-0 w-8 h-8 rounded-full bg-sky-500/20 text-sky-300 font-bold flex items-center justify-center">2</span>
                    <span><strong class="text-white">Toque no mapa</strong> — o formulário abre direto. Informe a situação e salve.</span>
                </li>
                <li class="flex gap-3 items-start">
                    <span class="shrink-0 w-8 h-8 rounded-full bg-sky-500/20 text-sky-300 font-bold flex items-center justify-center">3</span>
                    <span><strong class="text-white">Apresentar produtos</strong> — quando o cliente demonstrar interesse, mostre a apresentação.</span>
                </li>
                <li class="flex gap-3 items-start">
                    <span class="shrink-0 w-8 h-8 rounded-full bg-sky-500/20 text-sky-300 font-bold flex items-center justify-center">4</span>
                    <span><strong class="text-white">Continue na rua</strong> — após apresentar, use <strong class="text-white">Voltar ao mapa</strong> e siga o próximo ponto.</span>
                </li>
            </ol>
            <button type="button" id="seller-tips-continue" class="w-full h-14 rounded-2xl bg-sky-500 text-slate-950 font-bold mb-2">Entendi, vamos lá</button>
            <button type="button" id="seller-tips-skip" class="w-full h-11 rounded-xl border border-slate-700 text-sm text-slate-300">Pular</button>
        </div>
    </div>
    @endif

    {{-- Sprint 8.2.23 hotfix — seller commission reward (non-blocking) --}}
    <div id="commission-reward" class="commission-reward" hidden aria-live="polite" role="status">
        <div class="commission-reward__card">
            <div class="commission-reward__emoji" aria-hidden="true">🪙</div>
            <p class="commission-reward__title">Venda fechada!</p>
            <p class="commission-reward__label">Você ganhou</p>
            <p class="commission-reward__amount" id="commission-reward-amount">R$ 0,00</p>
            <p class="commission-reward__sub">de comissão</p>
            <p class="commission-reward__hint">Comissão adicionada ao seu resultado.</p>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .op-main { height: 100vh; }
    @media (max-width: 900px) { .op-main { height: calc(100dvh - 74px); } }
    .leaflet-container { background: #0b1220; font: inherit; }
    /* Sprint 8.2.29 — house pins (property) vs circle GPS */
    .map-house-pin-icon { background: transparent !important; border: 0 !important; }
    .map-house-pin {
        position: relative;
        width: 28px;
        height: 36px;
        --pin-color: #9ca3af;
        pointer-events: auto;
    }
    .map-house-pin-svg { display: block; overflow: visible; }
    .map-house-pin-body {
        fill: var(--pin-color);
        stroke: #fff;
        stroke-width: 1.55;
        vector-effect: non-scaling-stroke;
    }
    .map-house-pin-house { fill: #fff; }
    .map-house-pin.kind-adjusted .map-house-pin-body { stroke: #3b82f6; stroke-width: 2; }
    .map-house-pin.kind-low_accuracy .map-house-pin-body { stroke: #eab308; stroke-width: 2; }
    .map-house-pin.is-draft .map-house-pin-body {
        fill-opacity: 0.4;
        stroke: #e2e8f0;
        stroke-dasharray: 3.5 2.5;
        stroke-width: 1.7;
    }
    .map-house-pin.is-draft .map-house-pin-house { fill-opacity: 0.85; }
    .map-house-pin .map-marker-mark {
        position: absolute;
        right: 1px;
        top: 7px;
        min-width: 11px;
        height: 11px;
        padding: 0 2px;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.88);
        border: 1px solid #fff;
        font-size: 7px;
        font-weight: 800;
        line-height: 11px;
        color: #fff;
        text-align: center;
        pointer-events: none;
    }
    .map-legend-pin {
        position: relative;
        display: inline-flex;
        width: 16px;
        height: 20px;
        --pin-color: #9ca3af;
        flex-shrink: 0;
        align-items: flex-start;
        justify-content: center;
    }
    .map-legend-pin-svg { display: block; }
    .map-legend-pin .map-legend-mark {
        position: absolute;
        right: -2px;
        top: 2px;
        font-size: 7px;
        font-weight: 800;
        line-height: 1;
        color: #fff;
        text-shadow: 0 0 2px rgba(0,0,0,.9);
    }
    /* Legacy circle classes kept for any residual markup */
    .map-marker-wrap { position: relative; width: 18px; height: 18px; }
    .map-marker-dot {
        width: 14px; height: 14px; border-radius: 50%; border: 2px solid #fff;
        box-shadow: 0 0 0 1px rgba(0,0,0,.35); position: absolute; left: 2px; top: 2px;
        display: flex; align-items: center; justify-content: center;
    }
    .map-marker-mark {
        font-size: 8px; font-weight: 800; line-height: 1; color: #fff;
        text-shadow: 0 0 2px rgba(0,0,0,.85);
        pointer-events: none;
    }
    .map-legend-swatch {
        width: 14px; height: 14px; border-radius: 50%; border: 1.5px solid #fff;
        box-shadow: 0 0 0 1px rgba(0,0,0,.35);
        display: inline-flex; align-items: center; justify-content: center;
    }
    .map-legend-mark {
        font-size: 8px; font-weight: 800; line-height: 1; color: #fff;
        text-shadow: 0 0 2px rgba(0,0,0,.8);
    }
    .map-legend-panel {
        position: absolute;
        z-index: 35;
        pointer-events: none;
        top: auto;
        left: 0.75rem;
        right: auto;
        bottom: auto;
        max-width: min(16rem, calc(100vw - 1.5rem));
    }
    body:not(.field-seller) .map-legend-panel:not(.hidden) {
        /* Ancorado sob a toolbar, não sobre Minha localização */
        top: 5.75rem;
        left: 0.75rem;
        bottom: auto;
        right: auto;
    }
    @media (min-width: 768px) {
        body:not(.field-seller) .map-legend-panel:not(.hidden) {
            top: 5.25rem;
        }
    }
    @media (min-width: 1024px) {
        body:not(.field-seller) .map-legend-panel:not(.hidden) {
            left: 0.75rem;
        }
    }
    body.field-seller .map-legend-panel {
        right: 0.75rem;
        left: auto;
        bottom: 5.5rem;
        z-index: 25;
    }
    .map-legend-panel.is-collapsed .map-legend-body { display: none; }
    .map-company-overlay.hidden { display: none !important; }
    .map-company-overlay:not(.hidden) { display: block; }
    .map-company-overlay-card {
        pointer-events: auto;
        background: rgba(15, 23, 42, 0.96);
        backdrop-filter: blur(10px);
        border: 1px solid rgb(51 65 85 / 0.9);
        border-radius: 1rem;
        box-shadow: 0 12px 40px rgba(2, 6, 23, 0.55);
        padding: 0.85rem;
        max-height: min(70dvh, 32rem);
        overflow: auto;
    }
    #map-company-filters-panel {
        position: absolute;
        z-index: 35;
        left: 0.75rem;
        right: 0.75rem;
        top: 5.75rem;
        max-width: 28rem;
        pointer-events: none;
    }
    @media (min-width: 768px) {
        #map-company-filters-panel {
            top: 5.25rem;
            left: 1rem;
            right: auto;
            width: 26rem;
        }
    }
    @media (min-width: 1024px) {
        #map-company-filters-panel {
            top: 5rem;
        }
    }
    @media (max-width: 767px) {
        #map-company-filters-panel {
            left: 0.5rem;
            right: 0.5rem;
            top: auto;
            bottom: 0;
            max-width: none;
            width: auto;
        }
        #map-company-filters-panel .map-company-overlay-card {
            border-radius: 1.25rem 1.25rem 0 0;
            max-height: min(78dvh, 36rem);
        }
        body:not(.field-seller) .map-legend-panel:not(.hidden) {
            top: auto;
            bottom: 5.75rem;
            left: 0.5rem;
            right: auto;
        }
    }
    .map-legend-card { padding-bottom: 0.85rem; }
    .map-legend-toggle { min-height: auto; }
    .map-marker-ring {
        position: absolute; inset: 0; border-radius: 50%; border: 2px solid transparent; pointer-events: none;
    }
    .map-marker-ring.kind-gps { border-color: #22c55e; }
    .map-marker-ring.kind-adjusted { border-color: #3b82f6; }
    .map-marker-ring.kind-low_accuracy { border-color: #eab308; }
    .map-marker-dragging .map-house-pin { transform: scale(1.12); transform-origin: 50% 100%; }
    .map-marker-dragging .map-marker-dot { transform: scale(1.25); }
    .commercial-cluster { background: transparent !important; border: 0 !important; }
    .commercial-cluster-bubble {
        border-radius: 999px; background: color-mix(in srgb, var(--cluster-color) 82%, #0f172a);
        color: #f8fafc; font-weight: 800; display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        box-shadow: 0 0 0 2px #fff, 0 3px 12px rgba(0,0,0,.4);
        line-height: 1.05; font-size: 12px;
    }
    .commercial-cluster-bubble strong { font-size: 13px; color: #fff; }
    .commercial-cluster-mix { font-size: 8px; font-weight: 600; max-width: 90%; overflow: hidden; white-space: nowrap; opacity: 0.92; }
    .basemap-btn.is-active { border-color: #38bdf8; color: #e0f2fe; background: rgba(14,165,233,.15); }
    .leaflet-region-select { stroke: #38bdf8; stroke-width: 2; stroke-dasharray: 6 4; fill: rgba(56,189,248,.12); }
    #metrics-panel.open, #marker-drawer.open { transform: translateX(0); }
    #drawer-backdrop.open { opacity: 1; pointer-events: auto; }

    /* Sprint 8.2.26 — Map Operation Sheet: altura flex correta + CTA acessível */
    #visit-modal.open, #point-modal.open, #delete-point-modal.open,
    #adjust-confirm-modal.open, #post-create-adjust-modal.open, #region-campaign-modal.open,
    #post-visit-modal.open, #seller-day-brief.open, #seller-tips-modal.open {
        display: flex !important;
    }

    #visit-modal.open,
    #point-modal.open {
        z-index: 100;
    }

    .map-sheet-panel,
    .map-operation-sheet {
        display: flex;
        flex-direction: column;
        min-height: 0;
        width: 100%;
        max-height: min(100dvh, 100%);
        overflow: hidden;
        padding-bottom: env(safe-area-inset-bottom, 0px);
        align-self: stretch;
    }
    @media (min-width: 640px) {
        .map-sheet-panel,
        .map-operation-sheet {
            max-height: min(92dvh, 900px);
            align-self: center;
        }
    }
    .map-sheet-header,
    .map-operation-header { flex: 0 0 auto; }
    .map-sheet-body,
    .map-operation-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-x: hidden;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
    }
    .map-sheet-footer,
    .map-operation-footer {
        flex: 0 0 auto;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
        border-top: 1px solid color-mix(in srgb, #334155 80%, transparent);
        background: color-mix(in srgb, #020617 94%, #0f172a);
        padding-bottom: max(0.75rem, env(safe-area-inset-bottom, 0px));
        box-shadow: 0 -8px 24px rgba(2, 6, 23, 0.45);
    }
    /* Hotfix 8.2.26 — footer actions must stay inside sheet (no horizontal overflow) */
    .map-operation-actions {
        display: flex;
        flex-direction: column-reverse;
        gap: 0.5rem;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }
    .map-operation-btn-primary,
    .map-operation-btn-secondary {
        box-sizing: border-box;
        max-width: 100%;
        min-width: 0;
        width: 100%;
        border-radius: 0.75rem;
        padding-left: 0.85rem;
        padding-right: 0.85rem;
        white-space: nowrap;
    }
    .map-operation-btn-primary {
        height: 3.5rem;
        background: #0ea5e9;
        color: #020617;
        font-weight: 700;
        font-size: 1rem;
        border: 0;
    }
    .map-operation-btn-secondary {
        height: 3rem;
        border: 1px solid #334155;
        background: transparent;
        color: #e2e8f0;
        font-weight: 500;
        font-size: 0.875rem;
    }
    @media (min-width: 480px) {
        .map-operation-actions {
            flex-direction: row;
            align-items: stretch;
        }
        .map-operation-btn-secondary {
            flex: 0 1 auto;
            width: auto;
            max-width: 42%;
        }
        .map-operation-btn-primary {
            flex: 1 1 0%;
            width: auto;
            min-width: 0;
        }
    }
    /* Drawer passivo enquanto sheet operacional está aberto */
    body.map-operation-open #marker-drawer.open {
        opacity: 0.35;
        pointer-events: none;
        filter: saturate(0.7);
    }
    body.map-operation-open #drawer-backdrop.open {
        opacity: 0.25;
        pointer-events: none;
    }
    .map-empty-hint {
        position: absolute;
        left: 50%;
        bottom: calc(5.75rem + env(safe-area-inset-bottom, 0px));
        transform: translateX(-50%);
        z-index: 25;
        max-width: min(22rem, calc(100vw - 1.5rem));
        pointer-events: none;
        padding: 0.65rem 0.9rem;
        border-radius: 0.85rem;
        background: color-mix(in srgb, #0f172a 92%, #38bdf8);
        border: 1px solid color-mix(in srgb, #38bdf8 35%, #334155);
        box-shadow: 0 10px 28px rgba(2, 6, 23, 0.45);
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    .map-empty-hint.is-visible {
        opacity: 1;
    }
    .map-empty-hint__text {
        margin: 0;
        font-size: 0.8rem;
        line-height: 1.35;
        color: #e2e8f0;
        text-align: center;
        font-weight: 500;
    }
    @media (min-width: 640px) {
        .map-empty-hint { bottom: 1.75rem; }
    }
    #adjust-banner:not(.hidden) { display: block; }
    #map-empty-state.visible { display: none !important; }
    #map-filters-form.open { display: grid !important; }
    #commercial-filters.open { display: block !important; }
    #map-legend-panel.open { display: block !important; }
    #map-legend-panel:not(.hidden) { pointer-events: none; }
    #map-legend-panel:not(.hidden) .map-company-overlay-card { pointer-events: auto; }
    body:not(.field-seller) #map-filters-form { display: grid; }
    .sr-only {
        position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
        overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;
    }
    .visit-quick.is-selected,
    body.field-seller .point-outcome.is-selected,
    .point-outcome.is-selected,
    #point-status-group .status-chip:has(:checked) {
        border-color: var(--status-color, #38bdf8) !important;
        background: color-mix(in srgb, var(--status-color, #38bdf8) 16%, transparent) !important;
        box-shadow: inset 3px 0 0 var(--status-color, #38bdf8);
    }
    .status-chip-swatch {
        width: 1.35rem;
        height: 1.35rem;
        border-radius: 999px;
        background: var(--status-color, #64748b);
        color: #0f172a;
        font-size: 0.7rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
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
        filter: drop-shadow(0 0 7px rgba(56, 189, 248, 0.95));
        transform: scale(1.18);
        transform-origin: 50% 100%;
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
    #basemap-controls { position: static; }
    #map-bottom-left-controls {
        bottom: 5.5rem;
        left: 0.75rem;
    }
    body.field-seller #map-bottom-left-controls {
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
    body.field-seller .field-seller-hide-meta { display: none !important; }
    body.field-seller #point-gps-label { font-family: inherit !important; }
    @media (min-width: 640px) {
        body.field-seller #map-bottom-left-controls { bottom: 1.5rem; }
        .map-toolbar { padding-top: 1rem !important; }
        .map-btn-recenter span { display: inline; }
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
        body.field-seller #point-modal .map-sheet-panel,
        body.field-seller #visit-modal .map-sheet-panel {
            max-height: min(100dvh, 100%);
            overflow: hidden;
        }
        .map-btn-meu-local span { display: inline !important; }
        .map-btn-meu-local {
            min-height: 3rem;
            font-size: 0.95rem;
        }
        .map-btn-recenter {
            min-width: 3rem;
            min-height: 3rem;
            max-width: min(11.5rem, 46vw);
            padding-left: 0.65rem;
            padding-right: 0.65rem;
            font-size: 0.75rem;
            line-height: 1.15;
            white-space: normal;
            text-align: left;
        }
        .map-btn-present {
            min-width: 3rem;
            min-height: 3rem;
            max-width: min(12rem, 48vw);
            padding-left: 0.65rem;
            padding-right: 0.65rem;
            font-size: 0.72rem;
            line-height: 1.15;
            white-space: normal;
            text-align: left;
        }
        .map-btn-recenter span,
        .map-btn-present span { display: inline; }
        .map-more-tools summary::-webkit-details-marker { display: none; }
        body:not(.field-seller) #map-bottom-left-controls {
            bottom: 1.5rem;
        }
        body:not(.field-seller) #commercial-filters {
            top: 5.25rem;
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
        body.field-seller #map-bottom-left-controls { bottom: 5.5rem; }
        #point-outcome-group {
            grid-template-columns: 1fr 1fr;
        }
    }
    @media (max-width: 430px) {
        .map-toolbar { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        .map-btn-meu-local { min-width: 7.5rem; padding-left: 0.75rem; padding-right: 0.75rem; }
        #map-bottom-left-controls { max-width: calc(100vw - 1.5rem); }
        body.map-fullscreen .op-main,
        body.field-seller .op-main { overflow-x: hidden; }
        #point-modal .map-sheet-panel,
        #visit-modal .map-sheet-panel { max-height: min(100dvh, 100%); }
        .point-form-actions { /* footer actions live in map-sheet-footer */ }
    }
    @media (max-width: 320px) {
        .map-btn-meu-local span { font-size: 0.8rem; }
        .map-btn-recenter { font-size: 0.65rem; max-width: 42vw; }
    }
    body.adjust-mode .leaflet-marker-draggable { cursor: grabbing; }
    body.region-select-mode { cursor: crosshair; }

    /* Sprint 8.2.23 hotfix — commission reward overlay */
    .commission-reward {
        position: fixed;
        inset: 0;
        z-index: 120;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        padding: 0 0.85rem calc(5.75rem + env(safe-area-inset-bottom, 0px));
        pointer-events: none;
        background: transparent;
    }
    @media (min-width: 640px) {
        .commission-reward {
            align-items: center;
            padding: 1rem;
        }
    }
    .commission-reward[hidden] { display: none !important; }
    .commission-reward.is-visible { display: flex; }
    .commission-reward__card {
        width: min(100%, 22rem);
        pointer-events: none;
        text-align: center;
        border-radius: 1.25rem;
        padding: 1.15rem 1.25rem 1.25rem;
        background: color-mix(in srgb, #0f172a 92%, #22c55e);
        border: 1px solid color-mix(in srgb, #22c55e 45%, transparent);
        box-shadow: 0 18px 48px rgba(2, 6, 23, 0.55);
        color: #f8fafc;
        animation: commissionRewardIn 0.28s ease;
    }
    .commission-reward.is-leaving .commission-reward__card {
        animation: commissionRewardOut 0.25s ease forwards;
    }
    .commission-reward__emoji { font-size: 1.75rem; line-height: 1; margin-bottom: 0.35rem; }
    .commission-reward__title {
        margin: 0; font-size: 1.15rem; font-weight: 800; letter-spacing: 0.01em;
    }
    .commission-reward__label {
        margin: 0.55rem 0 0; font-size: 0.85rem; color: #cbd5e1; font-weight: 500;
    }
    .commission-reward__amount {
        margin: 0.15rem 0; font-size: clamp(1.65rem, 6vw, 2rem); font-weight: 800;
        color: #4ade80; letter-spacing: 0.01em;
    }
    .commission-reward__sub {
        margin: 0; font-size: 0.9rem; color: #e2e8f0; font-weight: 600;
    }
    .commission-reward__hint {
        margin: 0.65rem 0 0; font-size: 0.78rem; color: #94a3b8; font-weight: 500;
    }
    @keyframes commissionRewardIn {
        from { opacity: 0; transform: translateY(14px) scale(0.97); }
        to { opacity: 1; transform: none; }
    }
    @keyframes commissionRewardOut {
        from { opacity: 1; transform: none; }
        to { opacity: 0; transform: translateY(10px) scale(0.98); }
    }
    @media (max-width: 360px) {
        .commission-reward { padding-left: 0.65rem; padding-right: 0.65rem; }
        .commission-reward__card { padding: 1rem; }
    }
</style>
@endpush

@push('scripts')
@if($mapFrontendConfig->usesGoogleVisual())
<script src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($mapFrontendConfig->browserKey()) }}&v=weekly" async defer></script>
@endif
<script src="{{ asset('js/map-provider.js') }}?v=6"></script>
<script src="{{ asset('js/field-offline-queue.js') }}?v=3"></script>
<script src="{{ asset('js/operational-map.js') }}?v=58"></script>
@endpush
