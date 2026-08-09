@php
    use App\Domains\Sales\Territory\Services\TerritoryService;

    $selectedUsers = old('user_ids', isset($campaign) ? $campaign->users->pluck('id')->all() : []);
    $rawSelectedSectors = collect(old('sector_ids', isset($campaign) ? $campaign->sectors->pluck('id')->all() : []))
        ->map(fn ($id) => (int) $id)
        ->all();
    $territory = app(TerritoryService::class);
    $selectedSectors = collect(isset($campaign) ? $campaign->sectors : [])
        ->reject(fn ($s) => $territory->isReservedWholeCitySectorName($s->name))
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->all();
    if (old('sector_ids') !== null) {
        $selectedSectors = $rawSelectedSectors;
    }
    $hadOnlyReserved = isset($campaign)
        && $campaign->sectors->isNotEmpty()
        && collect($selectedSectors)->isEmpty()
        && old('sector_ids') === null;
    $territoryMode = old(
        'territory_mode',
        ($hadOnlyReserved || count($selectedSectors) === 0) ? 'all' : 'sectors'
    );
    $selectedUf = old('geo_state_uf', $selectedUf ?? '');
    $selectedGeoId = old('geo_municipality_id', $selectedGeoMunicipalityId ?? '');
@endphp

<div class="form-group">
    <label for="name">Nome</label>
    <input class="form-control" id="name" name="name" value="{{ old('name', $campaign->name ?? '') }}" required>
</div>

<div class="form-group">
    <label for="description">Descrição</label>
    <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $campaign->description ?? '') }}</textarea>
</div>

<div class="form-group" style="margin-top:1rem;">
    <label style="font-size:1.05rem;">Território</label>
    <p class="header-meta">Onde a equipe vai trabalhar nesta campanha.</p>
</div>

<div class="grid grid-2">
    <div class="form-group">
        <label for="geo_state_uf">Estado</label>
        <select class="form-control" id="geo_state_uf" name="geo_state_uf" required
                data-municipalities-url="{{ $municipalitiesUrl }}">
            <option value="">Selecione</option>
            @foreach($geoStates as $state)
                <option value="{{ $state->uf }}" @selected(strtoupper((string) $selectedUf) === $state->uf)>
                    {{ $state->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label for="geo_municipality_search">Cidade</label>
        <input type="search" id="geo_municipality_search" class="form-control" placeholder="Buscar cidade…"
               autocomplete="off" aria-label="Buscar cidade"
               value="{{ old('geo_municipality_label', $selectedGeoMunicipalityName ?? '') }}">
        <input type="hidden" name="geo_municipality_id" id="geo_municipality_id" value="{{ $selectedGeoId }}"
               data-areas-url="{{ $areasForMunicipalityUrl }}"
               data-store-area-url="{{ $storeAreaUrl }}">
        @if(!empty($legacyCityId))
            <input type="hidden" name="city_id" value="{{ $legacyCityId }}">
        @endif
        <div id="municipality-results" class="card" style="display:none; padding:0.55rem; max-height:200px; overflow:auto; margin-top:.35rem;"></div>
        <p class="header-meta" id="municipality-hint" style="margin-top:.35rem;">Escolha o estado e busque a cidade.</p>
    </div>
</div>

<div class="form-group" id="campaign-territory-block">
    <label>Onde a equipe vai trabalhar?</label>
    <div style="display:grid; gap:.55rem; margin-bottom:.75rem;">
        <label style="display:flex; gap:.55rem; align-items:flex-start; color:var(--text);">
            <input type="radio" name="territory_mode" value="all" id="territory-mode-all"
                @checked($territoryMode === 'all')>
            <span>
                <strong>Toda a cidade</strong>
                <div class="header-meta">A campanha cobre a cidade inteira.</div>
            </span>
        </label>
        <label style="display:flex; gap:.55rem; align-items:flex-start; color:var(--text);">
            <input type="radio" name="territory_mode" value="sectors" id="territory-mode-sectors"
                @checked($territoryMode === 'sectors')>
            <span>
                <strong>Áreas específicas</strong>
                <div class="header-meta">Escolha uma ou mais áreas, ou crie uma nova.</div>
            </span>
        </label>
    </div>

    <div id="sector-picker" style="{{ $territoryMode === 'sectors' ? '' : 'display:none;' }}">
        <input type="search" id="sector-search" class="form-control" placeholder="Buscar área…"
               style="margin-bottom:.55rem;" autocomplete="off" aria-label="Buscar área">
        <div class="card" style="padding:0.85rem; max-height:220px; overflow:auto;" id="sector-options"
             data-selected="{{ implode(',', $selectedSectors) }}">
            @forelse($initialAreas as $area)
                <label class="sector-option" data-name="{{ mb_strtolower($area->name) }}"
                       style="display:flex; gap:0.55rem; align-items:center; margin-bottom:0.45rem; color:var(--text);">
                    <input type="checkbox" name="sector_ids[]" value="{{ $area->id }}"
                        @checked(in_array((int) $area->id, $selectedSectors, true))>
                    <span>{{ $area->name }}</span>
                </label>
            @empty
                <div class="header-meta" id="sector-empty-hint">Nenhuma área cadastrada ainda.</div>
            @endforelse
        </div>
        <p class="header-meta" id="sector-count" style="margin-top:.45rem;">0 áreas selecionadas</p>

        <div style="margin-top:.75rem;">
            <button type="button" class="btn btn-ghost" id="btn-toggle-new-area">+ Criar área</button>
            <div id="new-area-form" style="display:none; margin-top:.65rem;" class="card">
                <div class="form-group">
                    <label for="new-area-name">Nome da área</label>
                    <input class="form-control" id="new-area-name" type="text" maxlength="120" placeholder="Ex.: Zona Rural Norte">
                </div>
                <div class="form-group">
                    <label for="new-area-description">Descrição (opcional)</label>
                    <input class="form-control" id="new-area-description" type="text" maxlength="1000">
                </div>
                <div class="actions" style="margin-top:.35rem;">
                    <button type="button" class="btn btn-primary" id="btn-add-area">Adicionar</button>
                    <button type="button" class="btn btn-ghost" id="btn-cancel-new-area">Cancelar</button>
                </div>
                <p class="header-meta" id="new-area-error" style="color:#b91c1c; display:none; margin-top:.4rem;"></p>
            </div>
        </div>
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
        const stateSelect = document.getElementById('geo_state_uf');
        const munSearch = document.getElementById('geo_municipality_search');
        const munIdInput = document.getElementById('geo_municipality_id');
        const munResults = document.getElementById('municipality-results');
        const munHint = document.getElementById('municipality-hint');
        const container = document.getElementById('sector-options');
        const modeAll = document.getElementById('territory-mode-all');
        const modeSectors = document.getElementById('territory-mode-sectors');
        const picker = document.getElementById('sector-picker');
        const search = document.getElementById('sector-search');
        const countEl = document.getElementById('sector-count');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value;
        if (!stateSelect || !munIdInput || !container) return;

        let munTimer = null;
        let areasToken = 0;

        function optionNodes() {
            return Array.from(container.querySelectorAll('.sector-option'));
        }

        function updateCount() {
            if (!countEl) return;
            const n = optionNodes().filter((opt) => {
                const input = opt.querySelector('input[type="checkbox"]');
                return input && input.checked && opt.style.display !== 'none';
            }).length;
            countEl.textContent = n + (n === 1 ? ' área selecionada' : ' áreas selecionadas');
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

        function renderAreas(areas, selectedIds) {
            const selected = new Set((selectedIds || []).map((id) => String(id)));
            if (!areas.length) {
                container.innerHTML = '<div class="header-meta" id="sector-empty-hint">Nenhuma área cadastrada ainda.</div>';
                updateCount();
                return;
            }
            container.innerHTML = areas.map((area) => {
                const checked = selected.has(String(area.id)) ? ' checked' : '';
                const name = String(area.name || '');
                const safe = name.replace(/[&<>"']/g, (ch) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
                }[ch]));
                const nameAttr = name.toLowerCase().replace(/"/g, '');
                return '<label class="sector-option" data-name="' + nameAttr + '"' +
                    ' style="display:flex; gap:0.55rem; align-items:center; margin-bottom:0.45rem; color:var(--text);">' +
                    '<input type="checkbox" name="sector_ids[]" value="' + area.id + '"' + checked + '>' +
                    '<span>' + safe + '</span></label>';
            }).join('');
            bindOptionEvents();
            applySearch();
        }

        async function loadAreas(preserveSelected) {
            const geoId = munIdInput.value;
            if (!geoId) {
                container.innerHTML = '<div class="header-meta">Selecione uma cidade para ver as áreas.</div>';
                updateCount();
                return;
            }
            const token = ++areasToken;
            container.innerHTML = '<div class="header-meta">Carregando áreas…</div>';
            try {
                const url = new URL(munIdInput.dataset.areasUrl, window.location.origin);
                url.searchParams.set('geo_municipality_id', geoId);
                const response = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error('fail');
                const payload = await response.json();
                if (token !== areasToken) return;
                const selected = preserveSelected
                    ? (container.dataset.selected || '').split(',').filter(Boolean)
                    : [];
                renderAreas(payload.data?.areas || [], selected);
                container.dataset.selected = '';
            } catch (e) {
                if (token !== areasToken) return;
                container.innerHTML = '<div class="header-meta">Não foi possível carregar as áreas.</div>';
                updateCount();
            }
        }

        async function searchMunicipalities() {
            const uf = stateSelect.value;
            const q = (munSearch?.value || '').trim();
            if (!uf) {
                munResults.style.display = 'none';
                munResults.innerHTML = '';
                if (munHint) munHint.textContent = 'Escolha o estado e busque a cidade.';
                return;
            }
            const url = new URL(stateSelect.dataset.municipalitiesUrl, window.location.origin);
            url.searchParams.set('uf', uf);
            if (q) url.searchParams.set('q', q);
            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!response.ok) return;
            const payload = await response.json();
            const items = payload.data || [];
            if (!items.length) {
                munResults.style.display = 'block';
                munResults.innerHTML = '<div class="header-meta">Nenhuma cidade encontrada.</div>';
                return;
            }
            munResults.style.display = 'block';
            munResults.innerHTML = items.map((item) =>
                '<button type="button" class="mun-option" data-id="' + item.id + '" data-name="' +
                String(item.name).replace(/"/g, '&quot;') +
                '" style="display:block; width:100%; text-align:left; background:transparent; border:0; color:var(--text); padding:.4rem .2rem; cursor:pointer;">' +
                String(item.name).replace(/[&<>]/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[ch])) +
                '</button>'
            ).join('');
            munResults.querySelectorAll('.mun-option').forEach((btn) => {
                btn.addEventListener('click', () => {
                    munIdInput.value = btn.dataset.id;
                    if (munSearch) munSearch.value = btn.dataset.name || '';
                    munResults.style.display = 'none';
                    if (munHint) munHint.textContent = 'Cidade selecionada.';
                    loadAreas(false);
                });
            });
        }

        stateSelect.addEventListener('change', () => {
            munIdInput.value = '';
            if (munSearch) munSearch.value = '';
            munResults.style.display = 'none';
            container.innerHTML = '<div class="header-meta">Selecione uma cidade para ver as áreas.</div>';
            updateCount();
            if (stateSelect.value) searchMunicipalities();
        });

        munSearch?.addEventListener('input', () => {
            clearTimeout(munTimer);
            munTimer = setTimeout(searchMunicipalities, 220);
        });
        munSearch?.addEventListener('focus', () => {
            if (stateSelect.value) searchMunicipalities();
        });

        modeAll?.addEventListener('change', syncModeUi);
        modeSectors?.addEventListener('change', () => {
            syncModeUi();
            if (modeSectors.checked) loadAreas(true);
        });
        search?.addEventListener('input', applySearch);

        const newForm = document.getElementById('new-area-form');
        const btnToggle = document.getElementById('btn-toggle-new-area');
        const btnAdd = document.getElementById('btn-add-area');
        const btnCancel = document.getElementById('btn-cancel-new-area');
        const errEl = document.getElementById('new-area-error');

        btnToggle?.addEventListener('click', () => {
            if (newForm) newForm.style.display = newForm.style.display === 'none' ? '' : 'none';
        });
        btnCancel?.addEventListener('click', () => {
            if (newForm) newForm.style.display = 'none';
            if (errEl) errEl.style.display = 'none';
        });
        btnAdd?.addEventListener('click', async () => {
            if (errEl) { errEl.style.display = 'none'; errEl.textContent = ''; }
            const geoId = munIdInput.value;
            const name = document.getElementById('new-area-name')?.value?.trim();
            const description = document.getElementById('new-area-description')?.value?.trim() || '';
            if (!geoId) {
                if (errEl) { errEl.textContent = 'Selecione a cidade antes.'; errEl.style.display = ''; }
                return;
            }
            if (!name) {
                if (errEl) { errEl.textContent = 'Informe o nome da área.'; errEl.style.display = ''; }
                return;
            }
            try {
                const response = await fetch(munIdInput.dataset.storeAreaUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf || '',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        geo_municipality_id: Number(geoId),
                        name,
                        description: description || null,
                    }),
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const msg = payload?.errors?.name?.[0] || payload?.message || 'Não foi possível criar a área.';
                    if (errEl) { errEl.textContent = msg; errEl.style.display = ''; }
                    return;
                }
                const area = payload.data?.area;
                const selected = optionNodes()
                    .filter((o) => o.querySelector('input')?.checked)
                    .map((o) => o.querySelector('input').value);
                if (area) selected.push(String(area.id));
                await loadAreas(false);
                // re-check after reload
                setTimeout(() => {
                    optionNodes().forEach((opt) => {
                        const input = opt.querySelector('input');
                        if (input && selected.includes(String(input.value))) input.checked = true;
                    });
                    updateCount();
                }, 50);
                if (document.getElementById('new-area-name')) document.getElementById('new-area-name').value = '';
                if (document.getElementById('new-area-description')) document.getElementById('new-area-description').value = '';
                if (newForm) newForm.style.display = 'none';
                if (!modeSectors.checked) {
                    modeSectors.checked = true;
                    syncModeUi();
                }
            } catch (e) {
                if (errEl) { errEl.textContent = 'Falha de rede ao criar área.'; errEl.style.display = ''; }
            }
        });

        bindOptionEvents();
        syncModeUi();
        updateCount();
        if (munIdInput.value && modeSectors?.checked) {
            // keep server-rendered initial areas; dataset.selected already applied
        }
    })();
</script>
