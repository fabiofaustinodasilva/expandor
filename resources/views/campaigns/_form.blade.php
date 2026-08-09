@php
    $selectedUsers = old('user_ids', isset($campaign) ? $campaign->users->pluck('id')->all() : []);
    $selectedSectors = collect(old('sector_ids', isset($campaign) ? $campaign->sectors->pluck('id')->all() : []))
        ->map(fn ($id) => (int) $id)
        ->all();
    $selectedCity = old('city_id', $campaign->city_id ?? '');
    $territoryMode = old(
        'territory_mode',
        count($selectedSectors) > 0 ? 'sectors' : 'all'
    );
@endphp

<div class="form-group">
    <label for="name">Nome</label>
    <input class="form-control" id="name" name="name" value="{{ old('name', $campaign->name ?? '') }}" required>
</div>

<div class="form-group">
    <label for="description">Descrição</label>
    <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $campaign->description ?? '') }}</textarea>
</div>

<div class="form-group">
    <label for="city_id">Cidade</label>
    <select class="form-control" id="city_id" name="city_id" required
            data-sectors-url="{{ $sectorsForCityUrl }}">
        <option value="">Selecione</option>
        @foreach($cities as $city)
            <option value="{{ $city->id }}" @selected((string) $selectedCity === (string) $city->id)>
                {{ $city->name }}/{{ $city->state }}
            </option>
        @endforeach
    </select>
    <p class="header-meta" style="margin-top:.35rem;">Estado/UF vem da cidade cadastrada na empresa.</p>
</div>

<div class="form-group" id="campaign-territory-block">
    <label>Setores</label>
    <div class="campaign-territory-modes" style="display:grid; gap:.55rem; margin-bottom:.75rem;">
        <label style="display:flex; gap:.55rem; align-items:flex-start; color:var(--text);">
            <input type="radio" name="territory_mode" value="all" id="territory-mode-all"
                @checked($territoryMode === 'all')>
            <span>
                <strong>Todos os setores</strong>
                <div class="header-meta">Campanha cobre a cidade inteira (sem restrição de setor).</div>
            </span>
        </label>
        <label style="display:flex; gap:.55rem; align-items:flex-start; color:var(--text);">
            <input type="radio" name="territory_mode" value="sectors" id="territory-mode-sectors"
                @checked($territoryMode === 'sectors')>
            <span>
                <strong>Selecionar setores</strong>
                <div class="header-meta">Um ou vários setores da cidade.</div>
            </span>
        </label>
    </div>

    <div id="sector-picker" style="{{ $territoryMode === 'sectors' ? '' : 'display:none;' }}">
        <input type="search" id="sector-search" class="form-control" placeholder="Buscar setor…"
               style="margin-bottom:.55rem;" autocomplete="off" aria-label="Buscar setor">
        <div class="card" style="padding:0.85rem; max-height:220px; overflow:auto;" id="sector-options"
             data-selected="{{ implode(',', $selectedSectors) }}">
            @forelse($sectors as $sector)
                <label class="sector-option" data-city-id="{{ $sector->city_id }}" data-name="{{ mb_strtolower($sector->name) }}"
                       style="display:flex; gap:0.55rem; align-items:center; margin-bottom:0.45rem; color:var(--text);">
                    <input type="checkbox" name="sector_ids[]" value="{{ $sector->id }}"
                        @checked(in_array((int) $sector->id, $selectedSectors, true))>
                    <span>{{ $sector->name }}</span>
                </label>
            @empty
                <div class="header-meta" id="sector-empty-hint">
                    @if($selectedCity)
                        Nenhum setor nesta cidade. Cadastre setores personalizados em Setores.
                    @else
                        Selecione uma cidade para listar os setores.
                    @endif
                </div>
            @endforelse
        </div>
        <p class="header-meta" id="sector-count" style="margin-top:.45rem;">0 setores selecionados</p>
    </div>
</div>

<div class="grid grid-2">
    <div class="form-group">
        <label for="start_date">Início</label>
        <input class="form-control" type="date" id="start_date" name="start_date"
               value="{{ old('start_date', isset($campaign) ? $campaign->start_date?->format('Y-m-d') : '') }}">
    </div>
    <div class="form-group">
        <label for="end_date">Fim</label>
        <input class="form-control" type="date" id="end_date" name="end_date"
               value="{{ old('end_date', isset($campaign) ? $campaign->end_date?->format('Y-m-d') : '') }}">
    </div>
</div>

<div class="form-group">
    <label for="goal_visits">Meta de visitas</label>
    <input class="form-control" type="number" min="0" id="goal_visits" name="goal_visits"
           value="{{ old('goal_visits', $campaign->goal_visits ?? 0) }}">
</div>

@if(!isset($campaign))
    <div class="form-group">
        <label for="status">Status inicial</label>
        <select class="form-control" id="status" name="status">
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
@endif

<div class="form-group">
    <label>Vendedores</label>
    <div class="card" style="padding:0.85rem; max-height:220px; overflow:auto;">
        @forelse($sellers as $seller)
            <label style="display:flex; gap:0.55rem; align-items:center; margin-bottom:0.45rem; color:var(--text);">
                <input type="checkbox" name="user_ids[]" value="{{ $seller->id }}"
                    @checked(in_array($seller->id, $selectedUsers, true))>
                <span>{{ $seller->name }} <small style="color:var(--muted);">({{ $seller->role?->name }})</small></span>
            </label>
        @empty
            <div class="header-meta">Nenhum vendedor disponível.</div>
        @endforelse
    </div>
</div>

<script>
    (function () {
        const citySelect = document.getElementById('city_id');
        const container = document.getElementById('sector-options');
        const modeAll = document.getElementById('territory-mode-all');
        const modeSectors = document.getElementById('territory-mode-sectors');
        const picker = document.getElementById('sector-picker');
        const search = document.getElementById('sector-search');
        const countEl = document.getElementById('sector-count');
        if (!citySelect || !container) return;

        const sectorsUrl = citySelect.dataset.sectorsUrl;
        let loadToken = 0;

        function optionNodes() {
            return Array.from(container.querySelectorAll('.sector-option'));
        }

        function updateCount() {
            if (!countEl) return;
            const n = optionNodes().filter((opt) => {
                const input = opt.querySelector('input[type="checkbox"]');
                return input && input.checked && opt.style.display !== 'none';
            }).length;
            countEl.textContent = n + (n === 1 ? ' setor selecionado' : ' setores selecionados');
        }

        function syncModeUi() {
            const selectMode = modeSectors && modeSectors.checked;
            if (picker) picker.style.display = selectMode ? '' : 'none';
            if (!selectMode) {
                optionNodes().forEach((option) => {
                    const input = option.querySelector('input[type="checkbox"]');
                    if (input) input.checked = false;
                });
            }
            updateCount();
        }

        function applySearch() {
            const q = (search?.value || '').trim().toLowerCase();
            optionNodes().forEach((option) => {
                const match = !q || (option.dataset.name || '').includes(q);
                option.style.display = match ? 'flex' : 'none';
            });
            updateCount();
        }

        function bindOptionEvents() {
            optionNodes().forEach((option) => {
                option.querySelector('input[type="checkbox"]')?.addEventListener('change', updateCount);
            });
        }

        function renderSectors(sectors, selectedIds) {
            const selected = new Set((selectedIds || []).map((id) => String(id)));
            if (!sectors.length) {
                container.innerHTML = '<div class="header-meta" id="sector-empty-hint">Nenhum setor nesta cidade. Cadastre setores personalizados em Setores.</div>';
                updateCount();
                return;
            }

            container.innerHTML = sectors.map((sector) => {
                const checked = selected.has(String(sector.id)) ? ' checked' : '';
                const name = String(sector.name || '');
                const safeName = name.replace(/[&<>"']/g, (ch) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
                }[ch]));
                const nameAttr = name.toLowerCase().replace(/"/g, '');
                return (
                    '<label class="sector-option" data-city-id="' + sector.city_id + '" data-name="' + nameAttr + '"' +
                    ' style="display:flex; gap:0.55rem; align-items:center; margin-bottom:0.45rem; color:var(--text);">' +
                    '<input type="checkbox" name="sector_ids[]" value="' + sector.id + '"' + checked + '>' +
                    '<span>' + safeName + '</span></label>'
                );
            }).join('');

            bindOptionEvents();
            applySearch();
        }

        async function loadSectorsForCity(cityId, preserveSelected) {
            if (!cityId || !sectorsUrl) {
                container.innerHTML = '<div class="header-meta" id="sector-empty-hint">Selecione uma cidade para listar os setores.</div>';
                updateCount();
                return;
            }

            const token = ++loadToken;
            container.innerHTML = '<div class="header-meta">Carregando setores…</div>';

            try {
                const url = new URL(sectorsUrl, window.location.origin);
                url.searchParams.set('city_id', cityId);
                const response = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error('Falha ao carregar setores');
                const payload = await response.json();
                if (token !== loadToken) return;
                const selected = preserveSelected
                    ? (container.dataset.selected || '').split(',').filter(Boolean)
                    : [];
                renderSectors(payload.data || [], selected);
                container.dataset.selected = '';
            } catch (err) {
                if (token !== loadToken) return;
                container.innerHTML = '<div class="header-meta">Não foi possível carregar os setores.</div>';
                updateCount();
            }
        }

        citySelect.addEventListener('change', function () {
            if (search) search.value = '';
            loadSectorsForCity(citySelect.value, false);
        });

        modeAll?.addEventListener('change', syncModeUi);
        modeSectors?.addEventListener('change', syncModeUi);
        search?.addEventListener('input', applySearch);

        bindOptionEvents();
        syncModeUi();
        updateCount();
    })();
</script>
