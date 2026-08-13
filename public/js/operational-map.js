(function () {
    const page = document.getElementById('map-page');
    if (!page || typeof L === 'undefined') {
        return;
    }

    const markersUrl = page.dataset.markersUrl;
    const visitStoreTemplate = page.dataset.visitStoreTemplate;
    const opportunityUrl = page.dataset.opportunityUrl;
    const messagesUrl = page.dataset.messagesUrl;
    const followUpsUrl = page.dataset.followUpsUrl;
    const propertyResidentsTemplate = page.dataset.propertyResidentsTemplate;
    const canVisit = page.dataset.canVisit === '1';
    const canWhatsapp = page.dataset.canWhatsapp === '1';
    const canCrm = page.dataset.canCrm === '1';
    const canHistory = page.dataset.canHistory === '1';
    const legend = JSON.parse(page.dataset.legend || '[]');
    const pointStoreUrl = page.dataset.pointStoreUrl;
    const firstApproachUrl = page.dataset.firstApproachUrl || '';
    const pointShowTemplate = page.dataset.pointShowTemplate;
    const pointAdjustTemplate = page.dataset.pointAdjustTemplate;
    const canCreatePoint = page.dataset.canCreatePoint === '1';
    const canEditPoint = page.dataset.canEditPoint === '1';
    const sellerName = page.dataset.sellerName || '';
    const currentUserId = page.dataset.currentUserId ? Number(page.dataset.currentUserId) : null;
    const isFieldSeller = page.dataset.isFieldSeller === '1';
    const pointsVisibility = page.dataset.pointsVisibility || 'company';
    const allowsUiFilters = page.dataset.allowsUiFilters === '1';
    const noCampaignMessage = page.dataset.noCampaignMessage
        || 'Você não possui uma campanha ativa. Solicite ao gestor a atribuição de uma campanha.';
    let sellerCampaigns = [];
    try {
        sellerCampaigns = JSON.parse(page.dataset.sellerCampaigns || '[]');
    } catch (e) {
        sellerCampaigns = [];
    }
    const saleRegisteredToast = page.dataset.saleRegisteredToast || 'Venda registrada';
    let saleRequiredFields = [];
    let saleFieldLabels = {};
    try { saleRequiredFields = JSON.parse(page.dataset.saleRequiredFields || '[]'); } catch (e) { saleRequiredFields = []; }
    try { saleFieldLabels = JSON.parse(page.dataset.saleFieldLabels || '{}'); } catch (e) { saleFieldLabels = {}; }
    const openNewPointOnLoad = page.dataset.openNewPoint === '1';
    const CONTRACT_PRODUCT_KEY = 'expandor.contract_product';

    let pointDetails = null;
    let lastGpsAccuracy = null;
    const commercial = window.ExpandorCommercialLayer || null;

    /** Data obrigatória + horário opcional → Y-m-d ou Y-m-dTH:i */
    function combineFollowUpAt(dateId, timeId) {
        const date = document.getElementById(dateId)?.value || '';
        if (!date) return '';
        const time = document.getElementById(timeId)?.value || '';
        return time ? `${date}T${time}` : date;
    }

    function localDatePlusDays(days) {
        const d = new Date();
        d.setHours(12, 0, 0, 0);
        d.setDate(d.getDate() + days);
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    function applyReturnShortcut(target, days) {
        const dateEl = document.getElementById(`${target}-follow-up-date`);
        const timeEl = document.getElementById(`${target}-follow-up-time`);
        if (!dateEl) return;
        if (days === 'pick') {
            dateEl.focus();
            try { dateEl.showPicker?.(); } catch (e) { /* ignore */ }
            return;
        }
        dateEl.value = localDatePlusDays(Number(days) || 1);
        if (timeEl && !timeEl.value) {
            timeEl.value = '18:00';
        }
    }

    function ensureReturnDefaults(target) {
        const dateEl = document.getElementById(`${target}-follow-up-date`);
        if (dateEl && !dateEl.value) {
            dateEl.value = localDatePlusDays(1);
        }
        const timeEl = document.getElementById(`${target}-follow-up-time`);
        if (timeEl && !timeEl.value) {
            timeEl.value = '18:00';
        }
    }
    const form = document.getElementById('map-filters-form');
    const citySelect = document.getElementById('filter-city');
    const sectorSelect = document.getElementById('filter-sector');
    const campaignSelect = document.getElementById('filter-campaign');
    const sellerSelect = document.getElementById('filter-seller');
    const statusSelect = document.getElementById('filter-status');
    const searchInput = document.getElementById('map-search');
    const searchResultsEl = document.getElementById('map-search-results');
    const countEl = document.getElementById('map-marker-count');
    const viewportEl = document.getElementById('metric-viewport');
    const statusEl = document.getElementById('map-status-message');
    const breakdownEl = document.getElementById('metric-status-breakdown');
    const toastEl = document.getElementById('op-toast');
    const myTeamFilter = document.getElementById('filter-my-team');

    const drawer = document.getElementById('marker-drawer');
    const drawerBackdrop = document.getElementById('drawer-backdrop');
    const metricsPanel = document.getElementById('metrics-panel');
    const visitModal = document.getElementById('visit-modal');
    const visitForm = document.getElementById('visit-form');
    const visitError = document.getElementById('visit-error');
    const pointModal = document.getElementById('point-modal');
    const pointForm = document.getElementById('point-form');
    const pointError = document.getElementById('point-error');
    const adjustBanner = document.getElementById('adjust-banner');
    const adjustConfirmModal = document.getElementById('adjust-confirm-modal');
    const postCreateAdjustModal = document.getElementById('post-create-adjust-modal');

    const allSectorOptions = Array.from(sectorSelect.options).slice(1);
    let markersCache = [];
    let layerByPropertyId = new Map();
    let selectedMarker = null;
    let lastFollowUpUrl = followUpsUrl;
    let suppressMoveLoad = false;
    let initialFitDone = false;
    let loadSeq = 0;
    let ignoreMapClickUntil = 0;
    let adjustState = null;
    let pendingPostCreatePropertyId = null;
    let lastSummary = null;
    let regionSelectMode = false;
    let regionRect = null;
    let regionStartLatLng = null;
    let sellerGps = null;
    let visitedInSession = new Set();
    let teamViewActive = false;
    const postVisitModal = document.getElementById('post-visit-modal');
    const sellersMeta = JSON.parse(page.dataset.sellers || '[]');

    const desiredMapProvider = page.dataset.mapProvider || 'leaflet_osm';
    const forceGoogleFailure = page.dataset.mapForceGoogleFailure === '1';
    const providerFallbackUrl = page.dataset.mapProviderFallbackUrl || '';
    const wantsGoogleVisual = desiredMapProvider === 'google_maps' && !forceGoogleFailure;

    // maxZoom must be set on the map itself (not only on tile layers).
    // MarkerCluster reads map.getMaxZoom() on addLayer; without this it throws
    // "Map has no maxZoom specified" and aborts boot before tiles load.
    // Value 19 matches existing OSM/Esri tileLayer maxZoom in map-provider.js
    // (GoogleMutant layer maxZoom 21 is capped by this map ceiling — intentional
    // parity with the Leaflet fallback absolute provider).
    const map = L.map('operational-map', {
        zoomControl: false,
        // Seller: Leaflet attribution control off. GoogleMutant keeps Google branding in its own pane (ToS).
        attributionControl: !isFieldSeller,
        preferCanvas: true,
        maxZoom: 19,
    }).setView([-14.235, -51.9253], 4);

    // Seller: sem botões +/- . Manager: zoom bottom-left (área livre — legenda/filtros sob demanda).
    if (!isFieldSeller) {
        L.control.zoom({ position: 'bottomleft' }).addTo(map);
    } else {
        // Seller: filtros comerciais ocultos; legenda compacta permanece disponível.
        document.getElementById('commercial-filters')?.classList.add('hidden');
        document.getElementById('commercial-filters')?.classList.remove('open');
        map.whenReady(() => {
            map.attributionControl?.remove?.();
            document.querySelectorAll(
                '#operational-map .leaflet-control-zoom, #operational-map .leaflet-control-attribution'
            ).forEach((el) => el.remove());
        });
    }

    let mapProvider = null;
    let basemapInitToken = 0;

    function createLeafletFallbackTiles() {
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: isFieldSeller ? '' : '&copy; OpenStreetMap',
        }).addTo(map);
    }

    function destroyCurrentProvider() {
        if (mapProvider && typeof mapProvider.destroy === 'function') {
            try { mapProvider.destroy(); } catch (e) { /* noop */ }
        }
        mapProvider = null;
    }

    function attachLeafletProvider() {
        destroyCurrentProvider();
        if (window.ExpandorMapProvider) {
            mapProvider = window.ExpandorMapProvider.create(map, {
                provider: 'leaflet_osm',
                hideAttribution: isFieldSeller,
            });
        } else {
            createLeafletFallbackTiles();
        }
        return mapProvider;
    }

    function reportProviderFallback(code, message) {
        if (!providerFallbackUrl) return;
        try {
            fetch(providerFallbackUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-XSRF-TOKEN': xsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    code: String(code || 'runtime_error').slice(0, 64),
                    message: String(message || 'runtime_fallback').slice(0, 300),
                }),
            }).catch(() => { /* noop */ });
        } catch (e) { /* noop */ }
    }

    function activateLeafletFallback(code, message, notify) {
        const token = ++basemapInitToken;
        destroyCurrentProvider();
        attachLeafletProvider();
        if (token !== basemapInitToken) return;
        if (notify) {
            toast('Mapa padrão ativado temporariamente.', 'success');
        }
        reportProviderFallback(code, message);
    }

    function keepLeafletAndWarn(code, message) {
        // Leaflet already mounted — do not destroy/recreate (avoids blank canvas).
        toast('Mapa padrão ativado temporariamente.', 'success');
        reportProviderFallback(code, message);
    }

    function initBasemapProvider() {
        const token = ++basemapInitToken;

        // Leaflet+OSM must already be mounted before MarkerCluster (see boot order below).
        // This function only handles optional Google upgrade / failure paths.

        if (forceGoogleFailure && desiredMapProvider === 'google_maps') {
            keepLeafletAndWarn('forced_failure', 'dev_force_google_failure');
            return;
        }

        if (!wantsGoogleVisual) {
            return;
        }

        if (!window.ExpandorMapProvider?.waitForGoogleMaps) {
            keepLeafletAndWarn('adapter_missing', 'ExpandorMapProvider unavailable');
            return;
        }

        const previousGmAuthFailure = window.gm_authFailure;
        window.gm_authFailure = function gmAuthFailureExpandor() {
            try {
                if (typeof previousGmAuthFailure === 'function') {
                    previousGmAuthFailure();
                }
            } catch (e) { /* noop */ }
            activateLeafletFallback('gm_auth_failure', 'Google Maps authentication failed', true);
        };

        window.ExpandorMapProvider.waitForGoogleMaps(12000)
            .then(() => {
                if (token !== basemapInitToken) return;
                try {
                    // Swap: remove provisional OSM, mount GoogleMutant (map.maxZoom already set).
                    destroyCurrentProvider();
                    mapProvider = window.ExpandorMapProvider.create(map, {
                        provider: 'google_maps',
                        hideAttribution: false,
                    });
                } catch (err) {
                    activateLeafletFallback(
                        err?.message || 'google_init_error',
                        String(err?.message || err || 'google_init_error'),
                        true
                    );
                }
            })
            .catch((err) => {
                if (token !== basemapInitToken) return;
                // Provisional Leaflet remains active — only warn.
                keepLeafletAndWarn(
                    err?.message || 'google_maps_timeout',
                    String(err?.message || err || 'google_maps_timeout')
                );
            });
    }

    // BOOT ORDER (required):
    // 1) L.map with explicit maxZoom
    // 2) Leaflet+OSM base layer addTo(map)
    // 3) MarkerCluster addTo(map)
    // 4) helpers / toast
    // 5) async Google upgrade (base layer swap only)
    attachLeafletProvider();

    const clusterGroup = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 55,
        spiderfyOnMaxZoom: true,
        // Uncluster at typical field zoom so saved pins stay clickable (was 18).
        disableClusteringAtZoom: 16,
        iconCreateFunction(cluster) {
            const children = cluster.getAllChildMarkers();
            const counts = { customer: 0, interested: 0, visited: 0, new: 0 };
            children.forEach((layer) => {
                const group = layer.options?.commercialGroup
                    || commercial?.groupOf(layer.options?.markerData)
                    || 'new';
                counts[group] = (counts[group] || 0) + 1;
            });
            if (commercial?.clusterIcon) {
                return commercial.clusterIcon(counts, children.length);
            }
            return L.divIcon({
                className: 'commercial-cluster',
                html: `<div class="commercial-cluster-bubble" style="--cluster-color:#ef4444"><strong>${children.length}</strong></div>`,
                iconSize: [40, 40],
                iconAnchor: [20, 20],
            });
        },
    });
    map.addLayer(clusterGroup);

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function xsrfToken() {
        const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    function toast(message, type = 'success') {
        if (!toastEl) return;
        toastEl.textContent = message;
        toastEl.classList.remove('success', 'error');
        toastEl.classList.add(type === 'error' ? 'error' : 'success');
        toastEl.classList.add('show');
        clearTimeout(toast._t);
        toast._t = setTimeout(() => toastEl.classList.remove('show'), 2800);
    }

    function mapDebug(step, detail = {}) {
        try {
            console.info('[Map]', step, detail);
        } catch (_) { /* optional */ }
    }

    function mapClickTrace(step, detail = {}) {
        try {
            console.info('[MapClickTrace]', step, detail);
        } catch (_) { /* optional */ }
    }

    function openMapModal(el) {
        if (!el) return;
        el.classList.remove('hidden');
        el.classList.add('open');
    }

    function closeMapModal(el) {
        if (!el) return;
        el.classList.remove('open');
        el.classList.add('hidden');
    }

    /** Sprint 8.2.25 — sessão única Seller / sessão invalidada */
    function handleAuthSessionLost(response) {
        if (!response || (response.status !== 401 && response.status !== 419)) {
            return false;
        }
        const loginUrl = '/login';
        try {
            toast('Sua conta foi acessada em outro dispositivo. Por segurança, esta sessão foi encerrada.', 'error');
        } catch (_) {}
        window.setTimeout(() => {
            window.location.href = loginUrl;
        }, 600);
        return true;
    }

    async function mapFetch(url, options) {
        const response = await fetch(url, options);
        if (handleAuthSessionLost(response)) {
            const err = new Error('session_replaced');
            err.sessionReplaced = true;
            throw err;
        }
        return response;
    }

    /** Sprint 8.2.23 hotfix — celebration only after backend confirms commission_awarded.awarded */
    const commissionRewardEl = document.getElementById('commission-reward');
    const commissionRewardAmountEl = document.getElementById('commission-reward-amount');
    let commissionAudio = null;
    let commissionAudioUnlocked = false;

    function formatBrl(amount) {
        const n = Number(amount);
        const safe = Number.isFinite(n) ? n : 0;
        try {
            return safe.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        } catch (_) {
            return 'R$ ' + safe.toFixed(2).replace('.', ',');
        }
    }

    function ensureCommissionAudio() {
        if (commissionAudio) return commissionAudio;
        try {
            commissionAudio = new Audio('/sounds/commission-coins.wav');
            commissionAudio.preload = 'auto';
            commissionAudio.volume = 0.45;
        } catch (_) {
            commissionAudio = null;
        }
        return commissionAudio;
    }

    function unlockCommissionAudio() {
        if (commissionAudioUnlocked) return;
        const audio = ensureCommissionAudio();
        if (!audio) return;
        try {
            const p = audio.play();
            if (p && typeof p.then === 'function') {
                p.then(() => {
                    audio.pause();
                    audio.currentTime = 0;
                    commissionAudioUnlocked = true;
                }).catch(() => { /* still locked — ok */ });
            }
        } catch (_) { /* ignore */ }
    }

    ['pointerdown', 'touchstart', 'click', 'keydown'].forEach((evt) => {
        document.addEventListener(evt, unlockCommissionAudio, { once: true, passive: true });
    });

    function playCommissionCoinSound() {
        try {
            const audio = ensureCommissionAudio();
            if (!audio) return;
            audio.currentTime = 0;
            const playPromise = audio.play();
            if (playPromise && typeof playPromise.catch === 'function') {
                playPromise.catch(() => { /* never break sale */ });
            }
        } catch (_) {
            /* audio must never break the sale flow */
        }
    }

    function rewardStorageKey(awarded) {
        if (awarded?.commission_id != null) {
            return 'expandor.commission_reward_shown_' + String(awarded.commission_id);
        }
        if (awarded?.visit_id != null) {
            return 'expandor.commission_rewarded.' + String(awarded.visit_id);
        }
        return null;
    }

    function wasRewardShown(key) {
        if (!key) return false;
        try {
            return sessionStorage.getItem(key) === '1';
        } catch (_) {
            return false;
        }
    }

    function markRewardShown(key) {
        if (!key) return;
        try {
            sessionStorage.setItem(key, '1');
        } catch (_) { /* ignore */ }
    }

    function showCommissionRewardOverlay(amount) {
        if (!commissionRewardEl || !commissionRewardAmountEl) {
            toast('🪙 Venda fechada! Você ganhou ' + formatBrl(amount) + ' de comissão');
            return;
        }
        commissionRewardAmountEl.textContent = formatBrl(amount);
        commissionRewardEl.hidden = false;
        commissionRewardEl.classList.add('is-visible');
        commissionRewardEl.classList.remove('is-leaving');
        clearTimeout(showCommissionRewardOverlay._t);
        clearTimeout(showCommissionRewardOverlay._t2);
        showCommissionRewardOverlay._t = setTimeout(() => {
            commissionRewardEl.classList.add('is-leaving');
            showCommissionRewardOverlay._t2 = setTimeout(() => {
                commissionRewardEl.classList.remove('is-visible', 'is-leaving');
                commissionRewardEl.hidden = true;
            }, 260);
        }, 2600);
    }

    function celebrateCommissionAward(awarded) {
        if (!awarded) return;
        const ok = awarded.awarded === true || awarded.play_reward === true;
        if (!ok) return;
        const amount = Number(awarded.amount);
        if (!(amount > 0)) return;

        const key = rewardStorageKey(awarded);
        if (wasRewardShown(key)) return;
        markRewardShown(key);

        showCommissionRewardOverlay(amount);
        playCommissionCoinSound();
    }

    function consumeCommissionAwardFromResponse(data) {
        const awarded = data?.commission_awarded;
        if (awarded) celebrateCommissionAward(awarded);
    }

    // One-time flash after redirect/full page load (seller map only).
    try {
        const rawFlash = page.dataset.commissionAwardedFlash;
        if (rawFlash && rawFlash !== 'null' && rawFlash !== '') {
            const flashed = JSON.parse(rawFlash);
            // Defer so map chrome is ready; still gated by sessionStorage idempotency.
            setTimeout(() => celebrateCommissionAward(flashed), 350);
        }
    } catch (_) { /* ignore bad flash */ }

    initBasemapProvider();

    function debounce(fn, wait) {
        let t;
        return function (...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    function dash(value) {
        return value && String(value).trim() !== '' ? value : '—';
    }

    function filterSectorsByCity() {
        const cityId = citySelect.value;
        const current = sectorSelect.value;
        sectorSelect.innerHTML = '<option value="">Setor</option>';
        allSectorOptions.forEach((option) => {
            if (!cityId || option.dataset.cityId === cityId) {
                sectorSelect.appendChild(option.cloneNode(true));
            }
        });
        if (current && Array.from(sectorSelect.options).some((o) => o.value === current)) {
            sectorSelect.value = current;
        } else {
            sectorSelect.value = '';
        }
    }

    function locationKindMeta(kind) {
        if (kind === 'adjusted') {
            return { color: '#3b82f6', label: '🔵 Ajustado manualmente', className: 'kind-adjusted' };
        }
        if (kind === 'low_accuracy') {
            return { color: '#eab308', label: '🟡 Baixa precisão', className: 'kind-low_accuracy' };
        }
        return { color: '#22c55e', label: '🟢 GPS original', className: 'kind-gps' };
    }

    function accuracyClass(meters) {
        if (meters == null || Number.isNaN(meters)) return null;
        if (meters <= 10) return { label: 'Alta precisão', tone: 'text-emerald-400' };
        if (meters <= 50) return { label: 'Boa precisão', tone: 'text-sky-400' };
        return { label: 'Baixa precisão', tone: 'text-amber-400' };
    }

    function coloredIcon(color, locationKind = 'gps', mark = '', opts = {}) {
        const html = (commercial && typeof commercial.pinHtml === 'function')
            ? commercial.pinHtml(color, mark, locationKind, opts)
            : `<div class="map-house-pin kind-${locationKind || 'gps'}${opts.draft ? ' is-draft' : ''}" style="--pin-color:${color || '#9ca3af'}">`
                + `<svg class="map-house-pin-svg" viewBox="0 0 28 36" width="28" height="36" aria-hidden="true">`
                + `<path class="map-house-pin-body" d="M14 1.6C8.15 1.6 3.4 6.5 3.4 12.6c0 7.35 10.6 21.9 10.6 21.9s10.6-14.55 10.6-21.9C24.6 6.5 19.85 1.6 14 1.6z"/>`
                + `<path class="map-house-pin-house" d="M9.15 16.35 14 12.1l4.85 4.25V21.2h-2.75v-3.25h-4.2V21.2H9.15z"/>`
                + `</svg>${mark ? `<span class="map-marker-mark" aria-hidden="true">${mark}</span>` : ''}</div>`;

        return L.divIcon({
            className: 'map-house-pin-icon',
            html,
            iconSize: [28, 36],
            iconAnchor: [14, 34],
            popupAnchor: [0, -30],
        });
    }

    function boundsParams() {
        const b = map.getBounds();
        return {
            min_latitude: b.getSouth(),
            max_latitude: b.getNorth(),
            min_longitude: b.getWest(),
            max_longitude: b.getEast(),
        };
    }

    function buildQuery(includeBbox) {
        const params = new URLSearchParams();
        if (citySelect.value) params.set('city_id', citySelect.value);
        if (sectorSelect.value) params.set('sector_id', sectorSelect.value);
        if (statusSelect.value) params.set('property_status', statusSelect.value);
        if (campaignSelect.value) params.set('campaign_id', campaignSelect.value);
        const teamUserId = resolveTeamUserId();
        if (teamUserId) params.set('user_id', String(teamUserId));
        if (includeBbox && map.getZoom() >= 10) {
            const bbox = boundsParams();
            params.set('min_latitude', String(bbox.min_latitude));
            params.set('max_latitude', String(bbox.max_latitude));
            params.set('min_longitude', String(bbox.min_longitude));
            params.set('max_longitude', String(bbox.max_longitude));
        }
        return params.toString();
    }

    function resolveTeamUserId() {
        // Visibilidade de pontos é política da empresa (servidor). Vendedor não força user_id.
        if (isFieldSeller) {
            return null;
        }
        if (myTeamFilter?.checked) {
            if (sellerSelect.value) return Number(sellerSelect.value);
            return null;
        }
        if (sellerSelect.value) return Number(sellerSelect.value);
        return null;
    }

    function enabledCommercialGroups() {
        if (!allowsUiFilters && isFieldSeller) {
            return ['customer', 'interested', 'visited', 'new'];
        }
        return Array.from(document.querySelectorAll('.commercial-filter:checked'))
            .map((el) => el.dataset.group)
            .filter(Boolean);
    }

    function matchesCommercialFilters(marker) {
        const groups = enabledCommercialGroups();
        if (groups.length === 0) return false;
        const group = commercial?.groupOf(marker) || marker.commercial_group || 'new';
        if (!groups.includes(group)) return false;

        if (isFieldSeller) {
            return true;
        }

        if (myTeamFilter?.checked) {
            if (sellerSelect.value) {
                return Number(marker.owner_user_id) === Number(sellerSelect.value);
            }
        } else if (sellerSelect.value) {
            return Number(marker.owner_user_id) === Number(sellerSelect.value);
        }
        return true;
    }

    function matchesSearch(marker, query) {
        if (!query) return true;
        const q = query.trim().toLowerCase();
        if (!q) return true;

        const gpsMatch = q.match(/(-?\d+\.?\d*)\s*[,;\s]\s*(-?\d+\.?\d*)/);
        if (gpsMatch) {
            const lat = parseFloat(gpsMatch[1]);
            const lng = parseFloat(gpsMatch[2]);
            return Math.abs(marker.latitude - lat) < 0.001 && Math.abs(marker.longitude - lng) < 0.001;
        }

        const digits = q.replace(/\D+/g, '');
        const hay = [
            marker.resident_name,
            marker.resident_phone,
            marker.resident_whatsapp,
            marker.resident_document,
            marker.address,
            marker.sold_product,
            marker.status_label,
            marker.status,
            String(marker.latitude),
            String(marker.longitude),
        ].join(' ').toLowerCase();

        if (hay.includes(q)) return true;
        if (digits.length >= 3) {
            const compact = [
                marker.resident_phone,
                marker.resident_whatsapp,
                marker.resident_document,
            ].join('').replace(/\D+/g, '');
            if (compact.includes(digits)) return true;
        }
        return false;
    }

    function hideSearchResults() {
        if (!searchResultsEl) return;
        searchResultsEl.classList.add('hidden');
        searchResultsEl.innerHTML = '';
        searchInput?.setAttribute('aria-expanded', 'false');
    }

    function showSearchResults(hits) {
        if (!searchResultsEl) return;
        if (!hits.length) {
            hideSearchResults();
            return;
        }
        searchResultsEl.innerHTML = hits.slice(0, 12).map((hit, index) => {
            const title = hit.resident_name || hit.address || `Ponto #${hit.property_id}`;
            const meta = [hit.resident_phone || hit.resident_whatsapp, hit.address]
                .filter(Boolean)
                .join(' · ');
            return `<button type="button" role="option" data-search-index="${index}">
                <div class="font-semibold text-sm">${escapeHtml(title)}</div>
                ${meta ? `<div class="search-hit-meta">${escapeHtml(meta)}</div>` : ''}
            </button>`;
        }).join('');
        searchResultsEl.dataset.hits = JSON.stringify(hits.slice(0, 12));
        searchResultsEl.classList.remove('hidden');
        searchInput?.setAttribute('aria-expanded', 'true');
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function highlightSelectedMarker() {
        layerByPropertyId.forEach(({ layer }, propertyId) => {
            const selected = selectedMarker
                && Number(propertyId) === Number(selectedMarker.property_id);
            const iconEl = layer.getElement?.() || layer._icon;
            if (iconEl) {
                iconEl.classList.toggle('map-marker-selected', !!selected);
            }
            if (selected && layer.setZIndexOffset) {
                layer.setZIndexOffset(1000);
            } else if (layer.setZIndexOffset) {
                layer.setZIndexOffset(0);
            }
        });
    }

    function focusSearchHit(hit) {
        if (!hit) return;
        suppressMoveLoad = true;
        map.flyTo([hit.latitude, hit.longitude], Math.max(map.getZoom(), 17), { duration: 0.65 });
        setTimeout(() => { suppressMoveLoad = false; }, 700);
        openDrawer(hit);
        highlightSelectedMarker();
        hideSearchResults();
    }

    function updateMetrics(markers, summary) {
        countEl.textContent = String(markers.length);
        if (viewportEl) viewportEl.textContent = String(markers.length);

        const counts = { customer: 0, interested: 0, visited: 0, new: 0 };
        markers.forEach((m) => {
            const group = commercial?.groupOf(m) || m.commercial_group || 'new';
            counts[group] = (counts[group] || 0) + 1;
        });

        const fromApi = summary || lastSummary;
        const display = {
            total: markers.length,
            customer: counts.customer,
            interested: counts.interested,
            visited: counts.visited,
            new: counts.new,
        };

        const setText = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = String(value);
        };
        setText('region-points', display.total);
        setText('region-customers', display.customer);
        setText('region-interested', display.interested);
        setText('region-visited', display.visited);
        setText('region-new', display.new);

        const opp = commercial?.opportunityFromCounts(display)
            || { level: fromApi?.opportunity || 'unknown', label: fromApi?.opportunity_label || 'Sem dados na região' };
        const oppEl = document.getElementById('region-opportunity');
        if (oppEl) {
            oppEl.textContent = opp.label;
            oppEl.className = 'mt-3 rounded-lg px-3 py-2 text-xs font-medium '
                + (opp.level === 'high'
                    ? 'bg-emerald-500/15 text-emerald-300'
                    : opp.level === 'low'
                        ? 'bg-slate-800 text-slate-400'
                        : opp.level === 'medium'
                            ? 'bg-sky-500/15 text-sky-300'
                            : 'bg-slate-800 text-slate-300');
        }

        if (breakdownEl) {
            const legendCommercial = JSON.parse(page.dataset.commercialLegend || '[]');
            breakdownEl.innerHTML = (legendCommercial.length ? legendCommercial : legend).map((item) => {
                const key = item.group || item.status;
                let total;
                if (item.status) {
                    total = markers.filter((m) => m.status === item.status).length;
                } else if (counts[key] != null) {
                    total = counts[key];
                } else {
                    total = markers.filter((m) => m.status === item.status).length;
                }
                const mark = item.mark
                    ? `<span class="map-legend-mark" aria-hidden="true">${item.mark}</span>`
                    : '';
                const swatch = (commercial && typeof commercial.legendPinHtml === 'function')
                    ? commercial.legendPinHtml(item.color, item.mark)
                    : `<span class="map-legend-pin" style="--pin-color:${item.color}">${mark}</span>`;
                return `<div class="flex items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-1.5">${swatch}${item.label}</span>
                    <span class="text-slate-300">${total}</span>
                </div>`;
            }).join('');
        }
    }

    function setDrawerLocationKind(kind, label) {
        const box = document.getElementById('drawer-location-kind');
        const dot = document.getElementById('drawer-location-dot');
        const text = document.getElementById('drawer-location-label');
        if (!box || !dot || !text) return;
        const meta = locationKindMeta(kind || 'gps');
        box.classList.remove('hidden');
        dot.style.background = meta.color;
        text.textContent = label || meta.label;
    }

    function openDrawer(marker) {
        mapClickTrace('openPointDetails', { propertyId: marker?.property_id || null });
        mapDebug('openPointDetails', { propertyId: marker?.property_id || null });
        if (!drawer) {
            mapClickTrace('drawer-open', { ok: false, reason: 'missing-drawer' });
            return;
        }
        selectedMarker = marker;
        pointDetails = null;
        const contactName = dash(marker.resident_name);
        const address = dash(marker.address);
        document.getElementById('drawer-name').textContent = contactName !== '—'
            ? contactName
            : (address !== '—' ? address : 'Residência');
        const contactEl = document.getElementById('drawer-contact');
        if (contactEl) contactEl.textContent = contactName;
        document.getElementById('drawer-phone').textContent = dash(marker.resident_phone);
        const waEl = document.getElementById('drawer-whatsapp');
        if (waEl) waEl.textContent = dash(marker.resident_whatsapp || marker.resident_phone);
        document.getElementById('drawer-address').textContent = address;
        document.getElementById('drawer-updated').textContent = dash(marker.updated_at);
        const resultEl = document.getElementById('drawer-last-visit-result');
        if (resultEl) resultEl.textContent = '…';
        const nextActionEl = document.getElementById('drawer-next-action');
        if (nextActionEl) nextActionEl.textContent = '…';
        const soldEl = document.getElementById('drawer-sold-product');
        if (soldEl) soldEl.textContent = dash(marker.sold_product);
        document.getElementById('drawer-status-label').textContent = dash(marker.status_label || marker.status);
        const statusText = document.getElementById('drawer-status-text');
        if (statusText) statusText.textContent = dash(marker.status_label || marker.status);
        document.getElementById('drawer-status-dot').style.background = marker.color || '#9ca3af';
        document.getElementById('drawer-gps').textContent = `${marker.latitude}, ${marker.longitude}`;
        setDrawerLocationKind(marker.location_kind, marker.location_label);
        updateDrawerDistance(marker);
        ['drawer-responsible', 'drawer-campaign', 'drawer-created-by', 'drawer-created-at', 'drawer-last-visit', 'drawer-next-follow-up'].forEach((id) => {
            const el = document.getElementById(id);
            if (el) el.textContent = '…';
        });
        const histBox = document.getElementById('drawer-history');
        if (histBox) histBox.innerHTML = '<div class="text-slate-500">Carregando histórico…</div>';

        const visitBtn = document.getElementById('action-visit');
        visitBtn.disabled = !canVisit;
        visitBtn.classList.toggle('opacity-40', !canVisit);

        const phoneDigits = String(marker.resident_phone || '').replace(/\D/g, '');
        const callBtn = document.getElementById('action-call');
        const wa = document.getElementById('action-whatsapp');
        if (callBtn) {
            if (phoneDigits) {
                callBtn.href = `tel:+${phoneDigits.startsWith('55') ? phoneDigits : '55' + phoneDigits}`;
                callBtn.classList.remove('pointer-events-none', 'opacity-40');
            } else {
                callBtn.href = '#';
                callBtn.classList.add('pointer-events-none', 'opacity-40');
            }
        }
        if (wa) {
            if (canWhatsapp && phoneDigits) {
                wa.href = `https://wa.me/55${phoneDigits.replace(/^55/, '')}`;
                wa.classList.remove('pointer-events-none', 'opacity-40');
            } else if (canWhatsapp) {
                wa.href = messagesUrl || '#';
                wa.classList.toggle('opacity-40', !messagesUrl);
            } else {
                wa.href = '#';
                wa.classList.add('pointer-events-none', 'opacity-40');
            }
        }

        const route = document.getElementById('action-route');
        if (route) {
            route.href = `https://www.google.com/maps/dir/?api=1&destination=${marker.latitude},${marker.longitude}`;
        }

        const editBtn = document.getElementById('action-edit');
        const deleteBtn = document.getElementById('action-delete');
        const adjustBtn = document.getElementById('action-adjust');
        if (editBtn) {
            editBtn.disabled = !canEditPoint;
            editBtn.classList.toggle('opacity-40', !canEditPoint);
        }
        if (deleteBtn) {
            deleteBtn.disabled = true;
            deleteBtn.classList.add('opacity-40');
        }
        if (adjustBtn) {
            adjustBtn.disabled = true;
            adjustBtn.classList.add('opacity-40');
        }

        drawer.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
        drawerBackdrop?.classList.add('open');
        mapClickTrace('drawer-open', {
            ok: true,
            propertyId: marker?.property_id || null,
            hasOpenClass: drawer.classList.contains('open'),
            ariaHidden: drawer.getAttribute('aria-hidden'),
        });
        if (window.lucide) window.lucide.createIcons();
        highlightSelectedMarker();

        if (marker.property_id && pointShowTemplate) {
            loadPointDetails(marker.property_id);
        } else {
            mapClickTrace('fetch-point-detail', {
                skipped: true,
                propertyId: marker?.property_id || null,
                hasTemplate: !!pointShowTemplate,
            });
        }
    }

    async function loadPointDetails(propertyId) {
        const url = pointShowTemplate.replace('__PROPERTY__', propertyId);
        mapClickTrace('fetch-point-detail', { propertyId: propertyId || null, url });
        mapDebug('detailRequest', { propertyId: propertyId || null });
        try {
            const response = await mapFetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-XSRF-TOKEN': xsrfToken(),
                },
            });
            mapClickTrace('fetch-point-detail', {
                propertyId: propertyId || null,
                status: response.status,
            });
            if (!response.ok) {
                mapDebug('detailRequest', { propertyId: propertyId || null, status: response.status });
                throw new Error('Não foi possível abrir esta residência.');
            }
            const payload = await response.json();
            const data = payload.data || {};
            pointDetails = data;
            if (!selectedMarker || Number(selectedMarker.property_id) !== Number(propertyId)) return;

            const contactName = dash(data.resident_name || selectedMarker.resident_name);
            const address = dash(data.address || selectedMarker.address);
            document.getElementById('drawer-name').textContent = contactName !== '—'
                ? contactName
                : (address !== '—' ? address : 'Residência');
            const contactEl = document.getElementById('drawer-contact');
            if (contactEl) contactEl.textContent = contactName;
            document.getElementById('drawer-phone').textContent = dash(data.resident_phone || selectedMarker.resident_phone);
            const waEl = document.getElementById('drawer-whatsapp');
            if (waEl) {
                waEl.textContent = dash(data.resident_whatsapp || data.resident_phone || selectedMarker.resident_whatsapp);
            }
            document.getElementById('drawer-address').textContent = address;
            document.getElementById('drawer-status-label').textContent = dash(data.status_label);
            const statusText = document.getElementById('drawer-status-text');
            if (statusText) statusText.textContent = dash(data.status_label);
            document.getElementById('drawer-updated').textContent = dash(data.last_visit_at || data.updated_at);
            const resultEl = document.getElementById('drawer-last-visit-result');
            if (resultEl) resultEl.textContent = dash(data.last_visit_result);
            const nextActionEl = document.getElementById('drawer-next-action');
            if (nextActionEl) {
                nextActionEl.textContent = dash(data.next_action || (data.next_follow_up_at ? 'Retorno agendado' : null));
            }
            const soldEl = document.getElementById('drawer-sold-product');
            if (soldEl) soldEl.textContent = dash(data.sold_product);
            const nextFuEl = document.getElementById('drawer-next-follow-up');
            if (nextFuEl) {
                const label = data.next_follow_up_at || data.next_follow_up_relative;
                const hint = data.next_follow_up_time_hint;
                nextFuEl.textContent = dash(label);
                if (hint) {
                    nextFuEl.textContent = `${dash(label)} · ${hint}`;
                }
                if (data.next_follow_up_notes) {
                    nextFuEl.title = data.next_follow_up_notes;
                } else {
                    nextFuEl.removeAttribute('title');
                }
            }
            document.getElementById('drawer-responsible').textContent = dash(data.responsible);
            document.getElementById('drawer-campaign').textContent = dash(data.campaign);
            document.getElementById('drawer-created-by').textContent = dash(data.created_by);
            document.getElementById('drawer-created-at').textContent = dash(data.created_at);
            document.getElementById('drawer-last-visit').textContent = dash(data.last_visit_relative || data.last_visit_at);
            document.getElementById('drawer-gps').textContent = `${data.latitude}, ${data.longitude}`;
            setDrawerLocationKind(data.location_kind, data.location_label);

            const phoneDigits = String(data.resident_whatsapp || data.resident_phone || '').replace(/\D/g, '');
            const callBtn = document.getElementById('action-call');
            const wa = document.getElementById('action-whatsapp');
            if (callBtn && phoneDigits) {
                callBtn.href = `tel:+${phoneDigits.startsWith('55') ? phoneDigits : '55' + phoneDigits}`;
                callBtn.classList.remove('pointer-events-none', 'opacity-40');
            }
            if (wa && canWhatsapp && phoneDigits) {
                wa.href = `https://wa.me/55${phoneDigits.replace(/^55/, '')}`;
                wa.classList.remove('pointer-events-none', 'opacity-40');
            }

            const histEl = document.getElementById('drawer-history');
            const rows = (data.history || []).slice(0, 8);
            histEl.innerHTML = rows.length
                ? rows.map((h) => `<div><span class="text-slate-300">${dash(h.at)}</span> · ${dash(h.user)} — ${dash(h.description || h.to)}</div>`).join('')
                : '<div class="text-slate-500">Ainda sem histórico nesta casa.</div>';

            const editBtn = document.getElementById('action-edit');
            const deleteBtn = document.getElementById('action-delete');
            const adjustBtn = document.getElementById('action-adjust');
            if (editBtn) {
                editBtn.disabled = !data.can_edit;
                editBtn.classList.toggle('opacity-40', !data.can_edit);
            }
            if (deleteBtn) {
                deleteBtn.disabled = !data.can_delete;
                deleteBtn.classList.toggle('opacity-40', !data.can_delete);
            }
            if (adjustBtn) {
                adjustBtn.disabled = !data.can_adjust;
                adjustBtn.classList.toggle('opacity-40', !data.can_adjust);
            }

            selectedMarker = { ...selectedMarker, ...data, color: selectedMarker.color };
        } catch (error) {
            console.error(error);
            const histEl = document.getElementById('drawer-history');
            if (histEl) histEl.innerHTML = '<div class="text-rose-400">Não foi possível carregar o histórico.</div>';
        }
    }

    function closeDrawer() {
        drawer.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
        drawerBackdrop.classList.remove('open');
    }

    const SALE_CUSTOMER_IDS = {
        name: 'customer-name',
        phone: 'customer-phone',
        whatsapp: 'customer-whatsapp',
        document: 'customer-document',
        rg: 'customer-rg',
        email: 'customer-email',
        notes: 'sale-notes',
    };

    /** @type {Record<string, Array<{uid: string, productId: number, quantity: number}>>} */
    const saleCarts = { visit: [], point: [], agenda: [] };

    function moneyBr(value) {
        const n = Number(value) || 0;
        return n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function productsCatalog(prefix) {
        const root = document.getElementById(`${prefix}-sale-finalize`);
        if (!root) return [];
        try {
            return JSON.parse(root.dataset.products || '[]');
        } catch (e) {
            return [];
        }
    }

    function findProduct(prefix, productId) {
        return productsCatalog(prefix).find((p) => Number(p.id) === Number(productId)) || null;
    }

    function clearSaleFinalizeFields(prefix) {
        Object.values(SALE_CUSTOMER_IDS).forEach((suffix) => {
            const el = document.getElementById(`${prefix}-${suffix}`);
            if (el) el.value = '';
        });
        saleCarts[prefix] = [];
        renderSaleCart(prefix);
    }

    function cartLineTotal(prefix, line) {
        const product = findProduct(prefix, line.productId);
        if (!product) return 0;
        return (Number(product.price) || 0) * (Number(line.quantity) || 1);
    }

    function cartGrandTotal(prefix) {
        return (saleCarts[prefix] || []).reduce((sum, line) => sum + cartLineTotal(prefix, line), 0);
    }

    function renderSaleCart(prefix) {
        const wrap = document.getElementById(`${prefix}-sale-cart-lines`);
        const empty = document.getElementById(`${prefix}-sale-cart-empty`);
        const totalEl = document.getElementById(`${prefix}-sale-cart-total`);
        if (!wrap) return;
        const lines = saleCarts[prefix] || [];
        const catalog = productsCatalog(prefix);
        wrap.innerHTML = '';
        lines.forEach((line) => {
            const product = findProduct(prefix, line.productId);
            const row = document.createElement('div');
            row.className = 'rounded-xl border border-slate-700 bg-slate-900/80 p-2 space-y-2';
            row.dataset.uid = line.uid;

            const select = document.createElement('select');
            select.className = 'w-full h-11 rounded-lg bg-slate-950 border border-slate-700 px-2 text-sm';
            select.innerHTML = '<option value="">Selecione o produto</option>';
            catalog.forEach((p) => {
                const opt = document.createElement('option');
                opt.value = String(p.id);
                const stockHint = p.stock_control ? ` (estoque: ${p.stock_quantity})` : '';
                opt.textContent = `${p.name} — ${moneyBr(p.price)}${stockHint}`;
                opt.disabled = !p.available && Number(p.id) !== Number(line.productId);
                if (Number(p.id) === Number(line.productId)) opt.selected = true;
                select.appendChild(opt);
            });
            select.addEventListener('change', () => {
                const nextId = Number(select.value) || 0;
                const next = findProduct(prefix, nextId);
                if (nextId && next && next.stock_control && Number(next.stock_quantity) < Number(line.quantity || 1)) {
                    toast(`Estoque insuficiente para ${next.name}.`, 'error');
                    select.value = line.productId ? String(line.productId) : '';
                    return;
                }
                line.productId = nextId;
                renderSaleCart(prefix);
            });

            const meta = document.createElement('div');
            meta.className = 'flex items-center justify-between gap-2';
            const priceLabel = document.createElement('div');
            priceLabel.className = 'text-xs text-slate-400';
            priceLabel.textContent = product
                ? `Preço: ${moneyBr(product.price)} · Linha: ${moneyBr(cartLineTotal(prefix, line))}`
                : 'Selecione um produto';

            const qtyWrap = document.createElement('div');
            qtyWrap.className = 'flex items-center gap-1';
            const qtyInput = document.createElement('input');
            qtyInput.type = 'number';
            qtyInput.min = '1';
            qtyInput.max = '9999';
            qtyInput.value = String(line.quantity || 1);
            qtyInput.className = 'w-16 h-9 rounded-lg bg-slate-950 border border-slate-700 px-2 text-sm';
            qtyInput.addEventListener('change', () => {
                let qty = Math.max(1, parseInt(qtyInput.value, 10) || 1);
                const p = findProduct(prefix, line.productId);
                if (p && p.stock_control && qty > Number(p.stock_quantity)) {
                    toast(`Estoque insuficiente (${p.stock_quantity} disponível).`, 'error');
                    qty = Math.max(1, Number(p.stock_quantity) || 1);
                }
                line.quantity = qty;
                qtyInput.value = String(qty);
                renderSaleCart(prefix);
            });
            qtyWrap.appendChild(qtyInput);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'h-9 px-2 rounded-lg border border-rose-700/50 text-rose-300 text-xs';
            removeBtn.textContent = 'Excluir';
            removeBtn.addEventListener('click', () => {
                saleCarts[prefix] = (saleCarts[prefix] || []).filter((l) => l.uid !== line.uid);
                renderSaleCart(prefix);
            });

            meta.appendChild(priceLabel);
            meta.appendChild(qtyWrap);
            meta.appendChild(removeBtn);
            row.appendChild(select);
            row.appendChild(meta);
            wrap.appendChild(row);
        });

        if (empty) empty.classList.toggle('hidden', lines.length > 0);
        if (totalEl) totalEl.textContent = moneyBr(cartGrandTotal(prefix));
    }

    function addSaleCartLine(prefix) {
        const catalog = productsCatalog(prefix).filter((p) => p.available);
        if (catalog.length === 0) {
            toast('Nenhum produto disponível para venda.', 'error');
            return;
        }
        if (!saleCarts[prefix]) saleCarts[prefix] = [];
        saleCarts[prefix].push({
            uid: `${prefix}-${Date.now()}-${Math.random().toString(16).slice(2)}`,
            productId: 0,
            quantity: 1,
        });
        renderSaleCart(prefix);
    }

    /** Prefill cart with a known sellable product (presentation → Contratar). */
    function seedSaleCartWithProduct(prefix, productId) {
        const pid = Number(productId);
        if (!pid) return false;
        const product = findProduct(prefix, pid);
        if (!product || product.available === false) {
            toast('Produto indisponível para contratação.', 'error');
            return false;
        }
        if (!saleCarts[prefix]) saleCarts[prefix] = [];
        saleCarts[prefix] = [{
            uid: `${prefix}-contract-${pid}`,
            productId: pid,
            quantity: 1,
        }];
        renderSaleCart(prefix);
        return true;
    }

    function rememberContractProduct(productId) {
        const pid = Number(productId);
        if (!pid) return;
        try { sessionStorage.setItem(CONTRACT_PRODUCT_KEY, String(pid)); } catch (e) {}
    }

    function clearRememberedContractProduct() {
        try { sessionStorage.removeItem(CONTRACT_PRODUCT_KEY); } catch (e) {}
    }

    function pendingContractProductId() {
        try {
            return Number(sessionStorage.getItem(CONTRACT_PRODUCT_KEY) || 0) || 0;
        } catch (e) {
            return 0;
        }
    }

    function applyPendingContractProduct() {
        if (!isFieldSeller) return;
        const pid = pendingContractProductId();
        if (!pid) return;
        const statusEl = document.getElementById('point-visit-status');
        if (statusEl) statusEl.value = 'installation_requested';
        syncPointOutcomeUi('installation_requested');
        seedSaleCartWithProduct('point', pid);
    }

    function markPointGpsPending() {
        const title = document.getElementById('point-gps-title');
        if (title) title.textContent = 'Obtendo localização...';
        const gpsLabel = document.getElementById('point-gps-label');
        if (gpsLabel) {
            gpsLabel.textContent = 'Aguarde a localização GPS';
            gpsLabel.classList.remove('font-mono');
        }
        const latEl = document.getElementById('point-latitude');
        const lngEl = document.getElementById('point-longitude');
        if (latEl) latEl.value = '';
        if (lngEl) lngEl.value = '';
        const accEl = document.getElementById('point-gps-accuracy');
        if (accEl) accEl.value = '';
        const adjustOnMap = document.getElementById('point-adjust-on-map');
        if (adjustOnMap) adjustOnMap.classList.add('hidden');
    }

    function collectSaleFinalizeFields(prefix) {
        const out = {};
        const val = (suffix) => (document.getElementById(`${prefix}-${suffix}`)?.value || '').trim();
        out.customer_name = val('customer-name');
        out.customer_phone = val('customer-phone');
        out.customer_whatsapp = val('customer-whatsapp');
        out.customer_document = val('customer-document');
        out.customer_rg = val('customer-rg');
        out.customer_email = val('customer-email');
        out.sale_notes = val('sale-notes');
        out.items = (saleCarts[prefix] || [])
            .filter((l) => Number(l.productId) > 0)
            .map((l) => ({
                product_id: Number(l.productId),
                quantity: Math.max(1, Number(l.quantity) || 1),
            }));
        return out;
    }

    function validateSaleFinalizeFields(prefix) {
        for (const key of saleRequiredFields) {
            if (key === 'product') {
                const items = (saleCarts[prefix] || []).filter((l) => Number(l.productId) > 0);
                if (items.length === 0) {
                    return 'Adicione ao menos um produto à venda.';
                }
                const qtyByProduct = {};
                for (const line of items) {
                    const pid = Number(line.productId);
                    qtyByProduct[pid] = (qtyByProduct[pid] || 0) + Math.max(1, Number(line.quantity) || 1);
                }
                for (const [pid, qty] of Object.entries(qtyByProduct)) {
                    const p = findProduct(prefix, pid);
                    if (!p) return 'Produto inválido no carrinho.';
                    if (p.stock_control && Number(p.stock_quantity) < Number(qty)) {
                        return `Estoque insuficiente para ${p.name}.`;
                    }
                }
                continue;
            }
            const suffix = SALE_CUSTOMER_IDS[key];
            if (!suffix) continue;
            const el = document.getElementById(`${prefix}-${suffix}`);
            const value = (el?.value || '').trim();
            if (!value) {
                const label = saleFieldLabels[key] || key;
                return `Informe: ${label}.`;
            }
        }
        // Always require at least one product when sale finalize is open (business rule)
        const root = document.getElementById(`${prefix}-sale-finalize`);
        if (root && root.dataset.requireProduct === '1') {
            const items = (saleCarts[prefix] || []).filter((l) => Number(l.productId) > 0);
            if (items.length === 0) return 'Adicione ao menos um produto à venda.';
        }
        return null;
    }

    function appendFormPayload(body, data, prefix = '') {
        Object.entries(data).forEach(([k, v]) => {
            const key = prefix ? `${prefix}[${k}]` : k;
            if (Array.isArray(v)) {
                v.forEach((item, i) => {
                    if (item && typeof item === 'object') {
                        appendFormPayload(body, item, `${key}[${i}]`);
                    } else if (item != null && item !== '') {
                        body.append(`${key}[${i}]`, item);
                    }
                });
            } else if (v != null && v !== '') {
                body.append(key, v);
            }
        });
    }

    function formatApiErrors(result, fallback) {
        if (result?.errors && typeof result.errors === 'object') {
            const lines = Object.values(result.errors).flat().filter(Boolean);
            if (lines.length) return lines.join(' · ');
        }
        return result?.message || fallback || 'Não foi possível salvar.';
    }

    function revealReturnScheduleBlock(blockEl, target) {
        if (!blockEl) return;
        blockEl.classList.remove('hidden');
        ensureReturnDefaults(target);
        requestAnimationFrame(() => {
            try {
                blockEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } catch (_) { /* optional */ }
        });
    }

    function syncVisitContractBlock(status) {
        const block = document.getElementById('visit-sale-finalize');
        const returnBlock = document.getElementById('visit-return-block');
        const notesHint = document.getElementById('visit-notes-hint');
        const submitBtn = document.getElementById('visit-submit');
        const isContract = status === 'installation_requested';
        const isReturn = status === 'return_later';
        const isInterested = status === 'interested';
        block?.classList.toggle('hidden', !isContract);
        if (isReturn) {
            revealReturnScheduleBlock(returnBlock, 'visit');
        } else {
            returnBlock?.classList.add('hidden');
        }
        if (notesHint) {
            if (isContract) notesHint.textContent = '(opcional — observação da visita)';
            else if (isInterested || isReturn) notesHint.textContent = '(recomendado)';
            else notesHint.textContent = '(opcional)';
        }
        if (submitBtn) {
            submitBtn.textContent = isContract ? 'Confirmar venda' : 'Salvar visita';
        }
        if (!isContract) {
            clearSaleFinalizeFields('visit');
        } else if ((saleCarts.visit || []).length === 0) {
            renderSaleCart('visit');
        }
        if (!isReturn) {
            const fuDate = document.getElementById('visit-follow-up-date');
            const fuTime = document.getElementById('visit-follow-up-time');
            if (fuDate) fuDate.value = '';
            if (fuTime) fuTime.value = '';
        }
    }

    function syncPointOutcomeUi(status) {
        const contract = document.getElementById('point-sale-finalize');
        const ret = document.getElementById('point-return-block');
        const notesHint = document.getElementById('point-notes-hint');
        const submitBtn = document.getElementById('point-submit');
        contract?.classList.toggle('hidden', status !== 'installation_requested');
        if (status === 'return_later') {
            revealReturnScheduleBlock(ret, 'point');
        } else {
            ret?.classList.add('hidden');
        }
        if (notesHint) {
            if (status === 'installation_requested') notesHint.textContent = '(opcional — observação da visita)';
            else if (status === 'interested' || status === 'return_later') notesHint.textContent = '(recomendado)';
            else notesHint.textContent = '(opcional)';
        }
        if (submitBtn && isFieldSeller) {
            submitBtn.textContent = status === 'installation_requested' ? 'Confirmar venda' : 'Salvar ponto';
        }
        if (status !== 'installation_requested') {
            clearSaleFinalizeFields('point');
        } else if ((saleCarts.point || []).length === 0) {
            renderSaleCart('point');
        }
        if (status !== 'return_later') {
            const fuDate = document.getElementById('point-follow-up-date');
            const fuTime = document.getElementById('point-follow-up-time');
            if (fuDate) fuDate.value = '';
            if (fuTime) fuTime.value = '';
        }
        document.querySelectorAll('.point-outcome').forEach((btn) => {
            btn.classList.toggle('is-selected', btn.dataset.status === status);
            btn.classList.toggle('border-sky-400', btn.dataset.status === status);
        });
    }

    function prepareSellerCampaignSelect() {
        const select = document.getElementById('point-campaign-id');
        const hint = document.getElementById('point-campaign-hint');
        const block = document.getElementById('point-campaign-block');
        if (!select || !isFieldSeller) return;

        if (sellerCampaigns.length === 0) {
            block?.classList.remove('hidden');
            select.disabled = true;
            if (hint) hint.textContent = noCampaignMessage;
            return;
        }

        select.disabled = false;
        if (sellerCampaigns.length === 1) {
            select.value = String(sellerCampaigns[0].id);
            block?.classList.add('hidden');
            if (hint) hint.textContent = '';
            return;
        }

        block?.classList.remove('hidden');
        if (!select.value && campaignSelect?.value) {
            select.value = campaignSelect.value;
        }
        if (hint) hint.textContent = 'Você tem mais de uma campanha — escolha antes de salvar.';
    }

    function openVisitModal() {
        if (!selectedMarker || !canVisit) return;
        document.getElementById('visit-property-id').value = selectedMarker.property_id;
        const campaignEl = document.getElementById('visit-campaign-id');
        if (campaignSelect.value) {
            campaignEl.value = campaignSelect.value;
        } else if (!campaignEl.value && campaignEl.options.length === 2) {
            campaignEl.selectedIndex = 1;
        }
        document.getElementById('visit-status').value = '';
        document.getElementById('visit-notes').value = '';
        const product = document.getElementById('visit-product');
        const cNotes = document.getElementById('visit-contract-notes');
        if (product) product.value = '';
        if (cNotes) cNotes.value = '';
        syncVisitContractBlock('');
        document.querySelectorAll('.visit-quick').forEach((btn) => btn.classList.remove('is-selected'));
        visitError.classList.add('hidden');

        const subtitle = document.getElementById('visit-modal-subtitle');
        if (subtitle) {
            const name = (selectedMarker.resident_name || selectedMarker.address || '').trim();
            subtitle.textContent = name !== ''
                ? name
                : 'Como foi a abordagem?';
        }
        const submit = document.getElementById('visit-submit');
        if (submit) submit.textContent = 'Salvar visita';

        setMapOperationOpen(true);
        visitModal.classList.add('open');
    }

    function closeVisitModal() {
        visitModal.classList.remove('open');
        if (!pointModal?.classList.contains('open')) {
            setMapOperationOpen(false);
        }
    }

    function openPostVisitModal() {
        postVisitModal?.classList.add('open');
    }

    function closePostVisitModal() {
        postVisitModal?.classList.remove('open');
    }

    function formatDistance(meters) {
        if (meters == null || Number.isNaN(meters)) return null;
        if (meters < 1000) return `${Math.round(meters)} m`;
        return `${(meters / 1000).toFixed(1)} km`;
    }

    function distanceMeters(from, to) {
        if (!from || to?.latitude == null || to?.longitude == null) return null;
        try {
            return L.latLng(from.latitude ?? from.lat, from.longitude ?? from.lng)
                .distanceTo(L.latLng(to.latitude, to.longitude));
        } catch (e) {
            return null;
        }
    }

    function updateDrawerDistance(marker) {
        const el = document.getElementById('drawer-distance');
        if (!el) return;
        const origin = sellerGps || {
            latitude: map.getCenter().lat,
            longitude: map.getCenter().lng,
        };
        const meters = distanceMeters(origin, marker);
        if (meters == null) {
            el.classList.add('hidden');
            el.textContent = '';
            return;
        }
        el.textContent = `📏 ${formatDistance(meters)} de você`;
        el.classList.remove('hidden');
    }

    function pendingPriority(marker) {
        const status = marker.status || '';
        if (status === 'new') return 1;
        if (status === 'return_later') return 2;
        if (status === 'interested') return 3;
        return 99;
    }

    function isPendingHouse(marker) {
        const status = marker.status || '';
        return status === 'new' || status === 'return_later' || status === 'interested';
    }

    function candidateHouses(excludePropertyId) {
        return markersCache
            .filter((m) => matchesCommercialFilters(m))
            .filter((m) => isPendingHouse(m))
            .filter((m) => Number(m.property_id) !== Number(excludePropertyId || 0))
            .filter((m) => !visitedInSession.has(Number(m.property_id)));
    }

    function findNextHouse(origin, excludePropertyId) {
        const from = origin || sellerGps || {
            latitude: map.getCenter().lat,
            longitude: map.getCenter().lng,
        };
        const candidates = candidateHouses(excludePropertyId);
        if (candidates.length === 0) return null;

        const scored = candidates.map((m) => {
            const dist = distanceMeters(from, m);
            const ownBoost = (isFieldSeller && currentUserId && Number(m.owner_user_id) === Number(currentUserId))
                ? -50
                : 0;
            return {
                marker: m,
                dist: dist == null ? Number.POSITIVE_INFINITY : dist,
                priority: pendingPriority(m) + ownBoost,
            };
        });

        scored.sort((a, b) => {
            if (a.priority !== b.priority) return a.priority - b.priority;
            return a.dist - b.dist;
        });

        return scored[0]?.marker || null;
    }

    async function ensureSellerGps() {
        if (sellerGps) return sellerGps;
        try {
            const gps = await getGps();
            sellerGps = { latitude: gps.latitude, longitude: gps.longitude, accuracy: gps.accuracy };
            return sellerGps;
        } catch (e) {
            return null;
        }
    }

    async function goToNextHouse({ fromVisit = false } = {}) {
        const excludeId = selectedMarker?.property_id;
        const btn = document.getElementById('btn-next-house');
        if (btn) btn.disabled = true;
        try {
            await ensureSellerGps();
            const next = findNextHouse(sellerGps || selectedMarker, excludeId);
            if (!next) {
                toast(fromVisit
                    ? 'Visita salva. Não há outra casa pendente por perto.'
                    : 'Nenhuma casa pendente por perto.');
                closeDrawer();
                return;
            }
            const meters = distanceMeters(sellerGps || selectedMarker || map.getCenter(), next);
            suppressMoveLoad = true;
            const latlng = L.latLng(next.latitude, next.longitude);
            map.flyToBounds(latlng.toBounds(90), {
                maxZoom: 17,
                duration: 0.7,
                paddingTopLeft: [24, isFieldSeller ? 80 : 40],
                paddingBottomRight: [24, isFieldSeller ? 160 : 90],
            });
            setTimeout(() => { suppressMoveLoad = false; }, 800);
            openDrawer(next);
            toast(meters != null
                ? `Próxima casa · ${formatDistance(meters)}`
                : 'Próxima casa encontrada');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    let emptyAreaHintShown = false;
    let emptyHintHideTimer = null;

    function setMapOperationOpen(open) {
        document.body.classList.toggle('map-operation-open', !!open);
    }

    function showEmptyAreaHintOnce() {
        if (emptyAreaHintShown) return;
        emptyAreaHintShown = true;
        const hint = document.getElementById('map-empty-hint');
        if (hint) {
            hint.classList.remove('hidden');
            hint.classList.add('is-visible');
            clearTimeout(emptyHintHideTimer);
            emptyHintHideTimer = setTimeout(() => {
                hint.classList.remove('is-visible');
                setTimeout(() => hint.classList.add('hidden'), 220);
            }, 4200);
            return;
        }
        toast('Nenhum ponto nesta área. Toque no mapa para adicionar.', 'success');
    }

    function inspectSavedMarkerLayer(propertyId) {
        const entry = layerByPropertyId.get(Number(propertyId));
        if (!entry?.layer) {
            return { found: false, propertyId: Number(propertyId) };
        }
        const layer = entry.layer;
        const el = layer.getElement?.() || layer._icon || null;
        const cs = el ? window.getComputedStyle(el) : null;
        return {
            found: true,
            propertyId: Number(propertyId),
            hasMarkerData: !!layer.options?.markerData,
            interactive: layer.options?.interactive !== false,
            bubblingMouseEvents: layer.options?.bubblingMouseEvents !== false,
            pane: layer.options?.pane || 'markerPane',
            listensClick: typeof layer.listens === 'function' ? !!layer.listens('click') : null,
            iconClass: el?.className || null,
            pointerEvents: cs?.pointerEvents || null,
            zIndex: cs?.zIndex || null,
            opacity: cs?.opacity || null,
            clientRect: el ? (() => {
                const r = el.getBoundingClientRect();
                return { left: r.left, top: r.top, width: r.width, height: r.height };
            })() : null,
        };
    }

    function hitTestSavedMarkerFromMapEvent(event) {
        const oe = event?.originalEvent;
        const x = oe?.clientX;
        const y = oe?.clientY;
        if (x == null || y == null) return null;

        // 1) DOM target (when the icon actually receives the event)
        const target = oe?.target;
        if (target && typeof target.closest === 'function') {
            if (target.closest('.marker-cluster')) {
                mapClickTrace('hit-test-cluster-dom');
                return null;
            }
            const icon = target.closest('.leaflet-marker-icon, .map-house-pin');
            if (icon) {
                for (const { layer, marker } of layerByPropertyId.values()) {
                    const el = layer.getElement?.() || layer._icon;
                    if (el && (el === icon || el.contains(icon))) {
                        mapClickTrace('hit-test-dom', { propertyId: marker.property_id || null });
                        return marker;
                    }
                }
            }
        }

        // 2) Geometric hit-test — required when an overlay (e.g. GoogleMutant) steals DOM clicks
        //    but Leaflet still synthesizes map click at the same coordinates.
        let best = null;
        let bestArea = Infinity;
        layerByPropertyId.forEach(({ layer, marker }) => {
            const el = layer.getElement?.() || layer._icon;
            if (!el) return;
            const rect = el.getBoundingClientRect();
            if (!rect.width || !rect.height) return;
            if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) return;
            const area = rect.width * rect.height;
            if (area < bestArea) {
                bestArea = area;
                best = marker;
            }
        });
        if (best) {
            mapClickTrace('hit-test-geometry', { propertyId: best.property_id || null });
        }
        return best;
    }

    function openSavedMarkerDetails(marker, via) {
        if (!marker?.property_id) return false;
        if (adjustState || regionSelectMode) {
            mapClickTrace('openPointDetails-blocked', {
                via,
                adjust: !!adjustState,
                region: !!regionSelectMode,
            });
            return false;
        }
        ignoreMapClickUntil = Date.now() + 500;
        mapClickTrace(via, { propertyId: marker.property_id || null });
        mapDebug('markerClick', { propertyId: marker.property_id || null, via });
        openDrawer(marker);
        return true;
    }

    function bindSavedMarkerClick(layer, marker) {
        mapClickTrace('bindSavedMarkerClick', {
            propertyId: marker?.property_id || null,
            listensBefore: typeof layer.listens === 'function' ? !!layer.listens('click') : null,
        });

        layer.on('click', (event) => {
            mapClickTrace('leaflet-marker-click', { propertyId: marker.property_id || null });
            if (typeof event?.stopPropagation === 'function') {
                event.stopPropagation();
            }
            if (event?.originalEvent) {
                L.DomEvent.stop(event.originalEvent);
            }
            openSavedMarkerDetails(marker, 'bindSavedMarkerClick');
        });

        // DOM insurance: cluster add/remove remounts the icon; re-bind on every 'add'.
        layer.on('add', () => {
            const el = layer.getElement?.() || layer._icon;
            if (!el) return;
            el.style.pointerEvents = 'auto';
            el.style.cursor = 'pointer';
            el.setAttribute('role', 'button');
            el.dataset.propertyId = String(marker.property_id || '');
            if (el.dataset.mapDetailBound === '1') return;
            el.dataset.mapDetailBound = '1';
            L.DomEvent.on(el, 'click', (domEvent) => {
                L.DomEvent.stop(domEvent);
                mapClickTrace('dom-icon-click', { propertyId: marker.property_id || null });
                openSavedMarkerDetails(marker, 'dom-icon-click');
            });
        });

        mapClickTrace('bindSavedMarkerClick', {
            propertyId: marker?.property_id || null,
            listensAfter: typeof layer.listens === 'function' ? !!layer.listens('click') : null,
        });
    }

    function createSavedMarkerLayer(marker) {
        const group = commercial?.groupOf(marker) || marker.commercial_group || 'new';
        const layer = L.marker([marker.latitude, marker.longitude], {
            icon: coloredIcon(
                commercial?.colorOf?.(marker) || marker.color,
                marker.location_kind || 'gps',
                commercial?.markOf?.(marker) || ''
            ),
            commercialGroup: group,
            markerData: marker,
            interactive: true,
            bubblingMouseEvents: true,
            keyboard: true,
            riseOnHover: true,
        });
        bindSavedMarkerClick(layer, marker);
        return layer;
    }

    function renderMarkers(markers, { fit = false } = {}) {
        const query = searchInput.value;
        let filtered = markers
            .filter((m) => matchesSearch(m, query))
            .filter((m) => matchesCommercialFilters(m));

        clusterGroup.clearLayers();
        layerByPropertyId = new Map();
        const bounds = [];

        filtered.forEach((marker) => {
            if (marker.latitude == null || marker.longitude == null) return;
            if (marker.property_id == null) return;

            const layer = createSavedMarkerLayer(marker);
            clusterGroup.addLayer(layer);
            layerByPropertyId.set(Number(marker.property_id), { layer, marker });
            bounds.push([marker.latitude, marker.longitude]);
        });

        highlightSelectedMarker();

        updateMetrics(filtered, lastSummary);
        statusEl.textContent = filtered.length
            ? `${filtered.length} ponto(s) nesta área`
            : 'Nenhum ponto nesta área.';

        // Sprint 8.2.26: estado vazio não bloqueia; hint discreto 1× por lifecycle.
        const emptyState = document.getElementById('map-empty-state');
        if (emptyState) {
            emptyState.classList.remove('visible');
            emptyState.classList.add('hidden');
        }
        if (filtered.length === 0 && initialFitDone) {
            showEmptyAreaHintOnce();
        }

        if (fit && bounds.length > 0) {
            suppressMoveLoad = true;
            map.fitBounds(bounds, {
                padding: isFieldSeller ? [56, 56] : [48, 48],
                paddingBottomRight: isFieldSeller ? [48, 150] : [48, 48],
                maxZoom: 16,
            });
            setTimeout(() => { suppressMoveLoad = false; }, 500);
            initialFitDone = true;
        } else if (fit && bounds.length === 0) {
            initialFitDone = true;
        }
    }

    function showAdjustBanner(text) {
        if (!adjustBanner) return;
        const label = document.getElementById('adjust-banner-text');
        if (label) label.textContent = text || 'Arraste o ponto até a posição correta.';
        adjustBanner.classList.remove('hidden');
    }

    function hideAdjustBanner() {
        adjustBanner?.classList.add('hidden');
    }

    function openAdjustConfirm(lat, lng) {
        document.getElementById('adjust-confirm-lat').textContent = Number(lat).toFixed(7);
        document.getElementById('adjust-confirm-lng').textContent = Number(lng).toFixed(7);
        document.getElementById('adjust-confirm-error')?.classList.add('hidden');
        openMapModal(adjustConfirmModal);
    }

    function closeAdjustConfirm() {
        closeMapModal(adjustConfirmModal);
    }

    function cleanupAdjustLayer() {
        if (adjustState?.layer) {
            map.removeLayer(adjustState.layer);
        }
    }

    function cancelAdjustMode({ reload = true } = {}) {
        cleanupAdjustLayer();
        adjustState = null;
        hideAdjustBanner();
        closeAdjustConfirm();
        document.body.classList.remove('adjust-mode');
        suppressMoveLoad = false;
        if (reload) {
            loadMarkers({ fit: false, useBbox: true });
        }
    }

    function startAdjustMode({ propertyId, latitude, longitude, color, locationKind, mode }) {
        if (!propertyId && mode !== 'create-draft') return;
        cancelAdjustMode({ reload: false });
        closeDrawer();
        closePointModal();
        closeAdjustConfirm();

        const lat = Number(latitude);
        const lng = Number(longitude);
        if (Number.isNaN(lat) || Number.isNaN(lng)) return;

        suppressMoveLoad = true;
        document.body.classList.add('adjust-mode');

        // Hide original clustered marker while dragging a standalone one.
        const existing = propertyId ? layerByPropertyId.get(Number(propertyId)) : null;
        if (existing?.layer) {
            clusterGroup.removeLayer(existing.layer);
        }

        const layer = L.marker([lat, lng], {
            icon: coloredIcon(
                mode === 'create-draft' ? '#94a3b8' : (color || '#f97316'),
                locationKind || 'gps',
                '',
                mode === 'create-draft' ? { draft: true } : {}
            ),
            draggable: true,
            autoPan: true,
            zIndexOffset: 1000,
        }).addTo(map);

        layer.on('dragstart', () => {
            ignoreMapClickUntil = Date.now() + 800;
            layer.getElement()?.classList.add('map-marker-dragging');
        });
        layer.on('dragend', () => {
            ignoreMapClickUntil = Date.now() + 800;
            layer.getElement()?.classList.remove('map-marker-dragging');
            const pos = layer.getLatLng();
            if (mode === 'create-draft') {
                fillPointCoords(pos.lat, pos.lng, lastGpsAccuracy);
                cleanupAdjustLayer();
                adjustState = null;
                hideAdjustBanner();
                document.body.classList.remove('adjust-mode');
                suppressMoveLoad = false;
                openMapModal(pointModal);
                toast('Posição atualizada. Confira e salve o ponto.');
                return;
            }
            openAdjustConfirm(pos.lat, pos.lng);
        });

        adjustState = {
            mode: mode || 'existing',
            propertyId: propertyId ? Number(propertyId) : null,
            originalLat: lat,
            originalLng: lng,
            color: color || '#f97316',
            locationKind: locationKind || 'gps',
            layer,
        };

        map.setView([lat, lng], Math.max(map.getZoom(), 18));
        showAdjustBanner(mode === 'create-draft'
            ? 'Arraste ou toque no mapa para a posição correta.'
            : 'Arraste o ponto ou toque no mapa para a nova posição.');
        toast(mode === 'create-draft'
            ? 'Arraste ou toque no mapa para ajustar.'
            : 'Arraste o marcador ou toque no mapa para a nova posição.');
    }

    async function saveAdjustedPosition() {
        if (!adjustState || adjustState.mode === 'create-draft') return;
        const pos = adjustState.layer?.getLatLng();
        if (!pos || !adjustState.propertyId || !pointAdjustTemplate) return;

        const btn = document.getElementById('adjust-confirm-save');
        const err = document.getElementById('adjust-confirm-error');
        btn.disabled = true;
        err?.classList.add('hidden');

        const body = new FormData();
        body.append('_method', 'PUT');
        body.append('latitude', String(pos.lat));
        body.append('longitude', String(pos.lng));

        try {
            const url = pointAdjustTemplate.replace('__PROPERTY__', adjustState.propertyId);
            const response = await mapFetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                body,
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(payload?.message || 'Não foi possível salvar a posição.');
            }

            const savedId = adjustState.propertyId;
            cancelAdjustMode({ reload: false });
            toast('Posição salva no mapa.');
            await loadMarkers({ fit: false, useBbox: true });

            const refreshed = markersCache.find((m) => Number(m.property_id) === Number(savedId));
            if (refreshed) {
                openDrawer(refreshed);
            }
        } catch (error) {
            if (err) {
                err.textContent = error.message || 'Erro ao salvar.';
                err.classList.remove('hidden');
            }
            toast(error.message || 'Erro ao salvar.', 'error');
        } finally {
            btn.disabled = false;
        }
    }

    function openPostCreateAdjust(propertyId) {
        pendingPostCreatePropertyId = propertyId;
        postCreateAdjustModal?.classList.add('open');
    }

    function closePostCreateAdjust() {
        pendingPostCreatePropertyId = null;
        postCreateAdjustModal?.classList.remove('open');
    }

    async function loadMarkers({ fit = false, useBbox = true } = {}) {
        const seq = ++loadSeq;
        statusEl.textContent = 'Carregando mapa...';
        const emptyState = document.getElementById('map-empty-state');
        if (emptyState) {
            emptyState.classList.remove('visible');
            emptyState.classList.add('hidden');
        }
        const query = buildQuery(useBbox && initialFitDone);
        const url = query ? `${markersUrl}?${query}` : markersUrl;

        try {
            const response = await mapFetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-XSRF-TOKEN': xsrfToken(),
                },
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const payload = await response.json();
            if (seq !== loadSeq) return;

            markersCache = payload?.data?.markers || [];
            lastSummary = payload?.data?.summary || null;
            renderMarkers(markersCache, { fit: fit || !initialFitDone });
        } catch (error) {
            console.error(error);
            countEl.textContent = '0';
            viewportEl.textContent = '0';
            statusEl.textContent = 'Sem conexão. Verifique a internet e tente de novo.';
            toast('Não foi possível carregar o mapa.', 'error');
        }
    }

    async function submitVisit(event) {
        event.preventDefault();
        const campaignId = document.getElementById('visit-campaign-id').value;
        const propertyId = document.getElementById('visit-property-id').value;
        const status = document.getElementById('visit-status').value;
        const notes = document.getElementById('visit-notes').value;
        const submitBtn = document.getElementById('visit-submit');

        if (!campaignId) {
            visitError.textContent = 'Escolha a campanha desta visita.';
            visitError.classList.remove('hidden');
            return;
        }
        if (!status) {
            visitError.textContent = 'Escolha como foi o atendimento.';
            visitError.classList.remove('hidden');
            return;
        }
        if (status === 'installation_requested') {
            const saleErr = validateSaleFinalizeFields('visit');
            if (saleErr) {
                visitError.textContent = saleErr;
                visitError.classList.remove('hidden');
                return;
            }
        }
        if (status === 'return_later') {
            const followUpAt = combineFollowUpAt('visit-follow-up-date', 'visit-follow-up-time');
            if (!followUpAt) {
                visitError.textContent = 'Informe a data do retorno.';
                visitError.classList.remove('hidden');
                return;
            }
        }

        submitBtn.disabled = true;
        visitError.classList.add('hidden');

        const payload = {
            property_id: propertyId,
            status,
            notes: notes || '',
            campaign_id: campaignId,
        };
        if (status === 'installation_requested') {
            Object.assign(payload, collectSaleFinalizeFields('visit'));
        }
        const followUpAt = combineFollowUpAt('visit-follow-up-date', 'visit-follow-up-time');
        if (status === 'return_later') {
            payload.follow_up_at = followUpAt;
        }
        if (selectedMarker) {
            payload.latitude = selectedMarker.latitude;
            payload.longitude = selectedMarker.longitude;
        }
        if (sellerGps) {
            payload.latitude = sellerGps.latitude;
            payload.longitude = sellerGps.longitude;
        }

        const body = new FormData();
        appendFormPayload(body, payload);

        try {
            if (!navigator.onLine && window.ExpandorOfflineQueue) {
                window.ExpandorOfflineQueue.enqueue('visit.create', payload);
                updateOfflineBadge();
                if (propertyId) visitedInSession.add(Number(propertyId));
                closeVisitModal();
                closeDrawer();
                toast('Sem internet — visita guardada no celular.');
                openPostVisitModal();
                bumpDayMetric('visits');
                if (status === 'interested') bumpDayMetric('interested');
                if (status === 'installation_requested') bumpDayMetric('contracts');
                return;
            }

            const url = visitStoreTemplate.replace('__CAMPAIGN__', campaignId);
            const response = await mapFetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                body,
            });

            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(formatApiErrors(result, 'Não foi possível salvar a visita.'));
            }

            if (propertyId) {
                visitedInSession.add(Number(propertyId));
            }

            closeVisitModal();
            closeDrawer();
            if (status === 'installation_requested' && result?.data?.commission_awarded) {
                consumeCommissionAwardFromResponse(result.data);
                if (!result.data.commission_awarded.awarded && !result.data.commission_awarded.play_reward) {
                    toast(saleRegisteredToast);
                }
            } else {
                toast(status === 'installation_requested' ? saleRegisteredToast : 'Visita registrada');
            }
            await loadMarkers({ fit: false, useBbox: true });

            bumpDayMetric('visits');
            if (status === 'interested') bumpDayMetric('interested');
            if (status === 'installation_requested') bumpDayMetric('contracts');
            if (status === 'return_later') bumpTodayChipIfNeeded(followUpAt);

            openPostVisitModal();
        } catch (error) {
            if (!navigator.onLine && window.ExpandorOfflineQueue) {
                window.ExpandorOfflineQueue.enqueue('visit.create', payload);
                updateOfflineBadge();
                closeVisitModal();
                closeDrawer();
                toast('Sem conexão — visita guardada no celular.');
                openPostVisitModal();
            } else {
                visitError.textContent = error.message || 'Erro ao salvar.';
                visitError.classList.remove('hidden');
            }
        } finally {
            submitBtn.disabled = false;
        }
    }

    function bumpDayMetric(kind) {
        const map = {
            visits: ['metric-visits', 'brief-visits'],
            interested: ['metric-interested', 'brief-interested'],
            contracts: ['metric-installations', 'brief-contracts'],
        };
        (map[kind] || []).forEach((id) => {
            const el = document.getElementById(id);
            if (el) el.textContent = String(Number(el.textContent || 0) + 1);
        });
    }

    function bumpTodayChipIfNeeded(followUpAt) {
        if (!followUpAt || !isFieldSeller) return;
        const today = localDatePlusDays(0);
        const datePart = String(followUpAt).slice(0, 10);
        if (datePart !== today) return;
        const el = document.getElementById('map-today-count');
        if (!el) return;
        el.textContent = String(Number(el.textContent || 0) + 1);
        const chip = document.getElementById('map-today-chip');
        if (chip) chip.title = 'Ver retornos de hoje';
    }

    async function flyToSearchHits() {
        const query = searchInput.value.trim();
        if (!query) {
            hideSearchResults();
            await loadMarkers({ fit: false, useBbox: true });
            return;
        }

        statusEl.textContent = 'Buscando…';
        const params = new URLSearchParams(buildQuery(false));
        params.set('q', query);
        const url = `${markersUrl}?${params.toString()}`;

        try {
            const response = await mapFetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-XSRF-TOKEN': xsrfToken(),
                },
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const payload = await response.json();
            const hits = payload?.data?.markers || [];

            // Mantém hits da busca visíveis mesmo fora do viewport atual.
            const byId = new Map(markersCache.map((m) => [Number(m.property_id), m]));
            hits.forEach((hit) => byId.set(Number(hit.property_id), hit));
            markersCache = Array.from(byId.values());

            renderMarkers(hits);
            showSearchResults(hits);

            if (hits.length === 1) {
                focusSearchHit(hits[0]);
                statusEl.textContent = '1 resultado';
            } else if (hits.length > 1) {
                suppressMoveLoad = true;
                map.fitBounds(hits.map((h) => [h.latitude, h.longitude]), { padding: [48, 48], maxZoom: 16 });
                setTimeout(() => { suppressMoveLoad = false; }, 500);
                statusEl.textContent = `${hits.length} resultado(s)`;
            } else {
                hideSearchResults();
                toast('Nenhum cliente encontrado.');
                statusEl.textContent = 'Nenhum resultado';
            }
        } catch (error) {
            console.error(error);
            toast('Não foi possível buscar no mapa.', 'error');
            statusEl.textContent = 'Falha na busca';
        }
    }

    function nowLabel() {
        const d = new Date();
        return d.toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
    }

    function gpsErrorMessage(error) {
        const code = error && typeof error.code === 'number' ? error.code : null;
        // 1 PERMISSION_DENIED, 2 POSITION_UNAVAILABLE, 3 TIMEOUT
        if (code === 1) {
            return 'Permissão de localização negada. Ative no navegador e tente de novo.';
        }
        if (code === 3) {
            return 'Tempo esgotado ao obter a localização. Tente de novo.';
        }
        if (code === 2) {
            return 'Posição indisponível no momento. Verifique o GPS e tente de novo.';
        }
        if (!navigator.geolocation) {
            return 'Este navegador não oferece localização.';
        }
        return 'Não foi possível acessar sua localização.';
    }

    function getGps() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error(gpsErrorMessage(null)));
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const coords = {
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                        accuracy: pos.coords.accuracy,
                    };
                    sellerGps = coords;
                    resolve(coords);
                },
                (err) => reject(new Error(gpsErrorMessage(err))),
                // Sprint 8.2.11: slightly fresher fix, still one getCurrentPosition call
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 }
            );
        });
    }

    let draftLocationMarker = null;

    function clearDraftLocationMarker() {
        if (draftLocationMarker && map) {
            try { map.removeLayer(draftLocationMarker); } catch (e) {}
        }
        draftLocationMarker = null;
    }

    function showDraftLocationMarker(lat, lng) {
        if (!map || typeof L === 'undefined') return;
        clearDraftLocationMarker();
        const latNum = Number(lat);
        const lngNum = Number(lng);
        if (Number.isNaN(latNum) || Number.isNaN(lngNum)) return;
        // Never steal clicks from saved property markers sitting on the same coords.
        draftLocationMarker = L.circleMarker([latNum, lngNum], {
            radius: 10,
            color: '#0ea5e9',
            weight: 3,
            fillColor: '#38bdf8',
            fillOpacity: 0.85,
            interactive: false,
            keyboard: false,
        }).addTo(map);
        draftLocationMarker.bindTooltip('Minha localização', { permanent: false, direction: 'top' });
    }

    function centerMapOnCoords(lat, lng, zoom = 17, options = {}) {
        if (!map) return;
        const latNum = Number(lat);
        const lngNum = Number(lng);
        if (Number.isNaN(latNum) || Number.isNaN(lngNum)) return;
        const showDraft = options.showDraft !== false;
        suppressMoveLoad = true;
        map.setView([latNum, lngNum], Math.max(map.getZoom?.() || 0, zoom));
        setTimeout(() => { suppressMoveLoad = false; }, 600);
        if (showDraft) {
            showDraftLocationMarker(latNum, lngNum);
        } else {
            clearDraftLocationMarker();
        }
    }

    function fillPointCoords(lat, lng, accuracy) {
        document.getElementById('point-latitude').value = String(lat);
        document.getElementById('point-longitude').value = String(lng);
        lastGpsAccuracy = accuracy != null ? Number(accuracy) : null;
        document.getElementById('point-gps-accuracy').value = lastGpsAccuracy != null ? String(lastGpsAccuracy) : '';
        const title = document.getElementById('point-gps-title');
        if (title) title.textContent = 'Local encontrado.';
        const gpsLabel = document.getElementById('point-gps-label');
        if (gpsLabel) {
            // Sprint 8.2.8: vendedor não vê lat/lng crus — só confirmação amigável.
            gpsLabel.textContent = isFieldSeller
                ? 'Posição pronta para registro'
                : `${Number(lat).toFixed(6)}, ${Number(lng).toFixed(6)}`;
            gpsLabel.classList.toggle('font-mono', !isFieldSeller);
        }
        document.getElementById('point-meta-label').textContent = `Você: ${sellerName} · ${nowLabel()}`;
        const accEl = document.getElementById('point-accuracy-label');
        const classEl = document.getElementById('point-accuracy-class');
        if (accEl) {
            accEl.textContent = lastGpsAccuracy != null
                ? (isFieldSeller
                    ? (lastGpsAccuracy <= 30 ? 'Boa precisão' : `Precisão ~${Math.round(lastGpsAccuracy)} m`)
                    : `Precisão: ${Math.round(lastGpsAccuracy)} metros`)
                : '';
        }
        if (classEl) {
            const cls = accuracyClass(lastGpsAccuracy);
            if (cls) {
                classEl.textContent = `Classificação: ${cls.label}`;
                classEl.className = `text-xs mt-0.5 font-medium ${cls.tone}`;
            } else {
                classEl.textContent = '';
            }
        }
        const adjustOnMap = document.getElementById('point-adjust-on-map');
        if (adjustOnMap) {
            adjustOnMap.classList.toggle('hidden', !(lat && lng));
        }
        if (citySelect.value) {
            document.getElementById('point-city-id').value = citySelect.value;
        }
        if (sectorSelect?.value) {
            const sectorField = document.getElementById('point-sector-id');
            if (sectorField) sectorField.value = sectorSelect.value;
        }
    }

    async function openPointModal(coords, mode = 'create') {
        if (mode === 'create' && !canCreatePoint) return;
        if (mode === 'edit' && !canEditPoint) return;
        if (!pointModal) return;

        pointError.classList.add('hidden');
        pointForm.reset();
        document.getElementById('point-mode').value = mode;
        const eyebrow = document.getElementById('point-modal-eyebrow');
        const subtitle = document.getElementById('point-modal-subtitle');
        if (mode === 'edit') {
            document.getElementById('point-modal-title').textContent = 'Editar residência';
            document.getElementById('point-submit').textContent = 'Salvar alterações';
            if (eyebrow) eyebrow.textContent = 'Editar local';
            if (subtitle) subtitle.textContent = 'Atualize os dados deste ponto.';
        } else {
            document.getElementById('point-modal-title').textContent = 'Novo ponto';
            document.getElementById('point-submit').textContent = 'Salvar ponto';
            if (eyebrow) eyebrow.textContent = 'Adicionar local';
            if (subtitle) subtitle.textContent = 'Cadastre este local para iniciar uma abordagem.';
        }
        const title = document.getElementById('point-gps-title');
        if (title) title.textContent = mode === 'edit' ? 'Localização' : 'Localização';

        setMapOperationOpen(true);
        const managerStatus = document.getElementById('point-manager-status');
        const firstApproach = document.getElementById('point-first-approach');
        if (mode === 'create' && isFieldSeller) {
            managerStatus?.classList.add('hidden');
            firstApproach?.classList.remove('hidden');
            document.getElementById('point-visit-status').value = '';
            syncPointOutcomeUi('');
            prepareSellerCampaignSelect();
            if (sellerCampaigns.length === 0) {
                pointError.textContent = noCampaignMessage;
                pointError.classList.remove('hidden');
            }
        } else {
            managerStatus?.classList.remove('hidden');
            firstApproach?.classList.add('hidden');
            const interested = document.querySelector('#point-status-group input[value="interested"]');
            if (interested) interested.checked = true;
        }

        if (mode === 'edit' && (pointDetails || selectedMarker)) {
            managerStatus?.classList.remove('hidden');
            firstApproach?.classList.add('hidden');
            const data = pointDetails || selectedMarker;
            document.getElementById('point-property-id').value = data.property_id;
            if (data.city_id) document.getElementById('point-city-id').value = data.city_id;
            document.getElementById('point-street').value = data.street || '';
            document.getElementById('point-number').value = data.number || '';
            document.getElementById('point-contact-name').value = data.resident_name || '';
            document.getElementById('point-contact-phone').value = data.resident_phone || '';
            const notesEl = document.getElementById('point-notes');
            if (notesEl) notesEl.value = data.notes || '';
            const statusRadio = document.querySelector(`#point-status-group input[value="${data.status}"]`);
            if (statusRadio) statusRadio.checked = true;
            fillPointCoords(data.latitude, data.longitude, null);
            openMapModal(pointModal);
            if (window.lucide) window.lucide.createIcons();
            return;
        }

        if (coords) {
            fillPointCoords(coords.latitude, coords.longitude, coords.accuracy);
            centerMapOnCoords(coords.latitude, coords.longitude, 17);
            openMapModal(pointModal);
            if (window.lucide) window.lucide.createIcons();
            applyPendingContractProduct();
            return;
        }

        // Sprint 8.2.9: sem coords = não abre cadastro (Meu Local só localiza).
        // Sprint 8.2.13: contratação a partir da apresentação abre e obtém GPS em seguida.
        return;
    }

    /**
     * Sprint 8.2.13 — Contratar from presentation deck.
     * Reuses FirstApproach modal + sale finalize; auto GPS via existing getGps().
     */
    async function openContractRegistration(productId) {
        if (!canCreatePoint || !pointModal) return;
        const pid = Number(productId);
        if (!pid) return;

        rememberContractProduct(pid);

        pointError.classList.add('hidden');
        pointForm.reset();
        document.getElementById('point-mode').value = 'create';
        document.getElementById('point-modal-title').textContent = 'Contratar produto';
        document.getElementById('point-submit').textContent = 'Confirmar venda';
        const eyebrow = document.getElementById('point-modal-eyebrow');
        const subtitle = document.getElementById('point-modal-subtitle');
        if (eyebrow) eyebrow.textContent = 'Produtos e fechamento';
        if (subtitle) subtitle.textContent = 'Revise os produtos antes de finalizar.';

        const managerStatus = document.getElementById('point-manager-status');
        const firstApproach = document.getElementById('point-first-approach');
        managerStatus?.classList.add('hidden');
        firstApproach?.classList.remove('hidden');
        prepareSellerCampaignSelect();
        if (sellerCampaigns.length === 0) {
            pointError.textContent = noCampaignMessage;
            pointError.classList.remove('hidden');
        }

        markPointGpsPending();
        document.getElementById('point-visit-status').value = 'installation_requested';
        syncPointOutcomeUi('installation_requested');
        seedSaleCartWithProduct('point', pid);
        document.getElementById('point-meta-label').textContent = `Você: ${sellerName} · ${nowLabel()}`;
        setMapOperationOpen(true);
        openMapModal(pointModal);
        if (window.lucide) window.lucide.createIcons();

        try {
            const gps = await getGps();
            fillPointCoords(gps.latitude, gps.longitude, gps.accuracy);
            centerMapOnCoords(gps.latitude, gps.longitude, 17);
            pointError.classList.add('hidden');
        } catch (err) {
            const msg = err?.message || 'Não foi possível obter a localização.';
            pointError.textContent = `${msg} Toque no mapa para marcar o ponto, ou use Minha localização (opcional).`;
            pointError.classList.remove('hidden');
            const title = document.getElementById('point-gps-title');
            if (title) title.textContent = 'Localização';
            const gpsLabel = document.getElementById('point-gps-label');
            if (gpsLabel) gpsLabel.textContent = 'GPS indisponível — toque no mapa ou ajuste a posição';
        }
    }

    function closePointModal() {
        closeMapModal(pointModal);
        pointSubmitting = false;
        if (!visitModal?.classList.contains('open')) {
            setMapOperationOpen(false);
        }
    }

    function openCreateAtMapTap(latlng) {
        // Sprint 8.2.7+: toque no mapa → formulário direto (sem etapa intermediária).
        mapDebug('openCreatePoint', {
            hasCoords: !!(latlng && latlng.lat != null),
            canCreate: canCreatePoint,
        });
        if (!canCreatePoint) {
            toast('Sem permissão para adicionar pontos neste mapa.', 'error');
            return;
        }
        if (!latlng) return;
        openPointModal({
            latitude: latlng.lat,
            longitude: latlng.lng,
            accuracy: null,
        });
    }

    function openDeleteModal() {
        if (!selectedMarker?.property_id || !pointDetails?.can_delete) return;
        document.getElementById('delete-point-reason').value = '';
        document.getElementById('delete-point-error').classList.add('hidden');
        document.getElementById('delete-point-modal')?.classList.add('open');
    }

    function closeDeleteModal() {
        document.getElementById('delete-point-modal')?.classList.remove('open');
    }

    async function confirmDeletePoint() {
        if (!selectedMarker?.property_id) return;
        const btn = document.getElementById('delete-point-confirm');
        const err = document.getElementById('delete-point-error');
        btn.disabled = true;
        err.classList.add('hidden');

        const body = new FormData();
        body.append('_method', 'DELETE');
        body.append('reason', document.getElementById('delete-point-reason').value || '');

        try {
            const url = pointShowTemplate.replace('__PROPERTY__', selectedMarker.property_id);
            const response = await mapFetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                body,
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(payload?.message || 'Não foi possível excluir.');
            }
            closeDeleteModal();
            closeDrawer();
            toast('Ponto removido do mapa.');
            await loadMarkers({ fit: false, useBbox: true });
        } catch (error) {
            err.textContent = error.message || 'Não foi possível remover.';
            err.classList.remove('hidden');
        } finally {
            btn.disabled = false;
        }
    }

    let pointSubmitting = false;

    async function submitPoint(event) {
        event.preventDefault();
        if (pointSubmitting) return;
        const mode = document.getElementById('point-mode').value || 'create';
        if (mode === 'create' && !canCreatePoint) return;
        if (mode === 'edit' && !canEditPoint) return;

        const submitBtn = document.getElementById('point-submit');
        const useFirstApproach = mode === 'create' && isFieldSeller && firstApproachUrl;

        let status = null;
        if (useFirstApproach) {
            status = document.getElementById('point-visit-status')?.value || '';
            if (!status) {
                pointError.textContent = 'Escolha o resultado do atendimento.';
                pointError.classList.remove('hidden');
                return;
            }
            if (sellerCampaigns.length === 0) {
                pointError.textContent = noCampaignMessage;
                pointError.classList.remove('hidden');
                return;
            }
            const campaignId = document.getElementById('point-campaign-id')?.value || '';
            if (sellerCampaigns.length > 1 && !campaignId) {
                pointError.textContent = 'Selecione a campanha deste atendimento.';
                pointError.classList.remove('hidden');
                return;
            }
            const productId = (document.getElementById('point-product')?.value || '').trim();
            const notes = (document.getElementById('point-notes')?.value || '').trim();
            if (status === 'installation_requested') {
                const saleErr = validateSaleFinalizeFields('point');
                if (saleErr) {
                    pointError.textContent = saleErr;
                    pointError.classList.remove('hidden');
                    return;
                }
            }
            void productId;
            void notes;
        } else {
            status = pointForm.querySelector('input[name="status"]:checked')?.value;
            if (!status) {
                pointError.textContent = 'Escolha como foi o atendimento.';
                pointError.classList.remove('hidden');
                return;
            }
        }

        submitBtn.disabled = true;
        pointSubmitting = true;
        pointError.classList.add('hidden');

        const payload = {
            city_id: document.getElementById('point-city-id').value,
            street: document.getElementById('point-street').value.trim(),
            number: document.getElementById('point-number').value,
            latitude: document.getElementById('point-latitude').value,
            longitude: document.getElementById('point-longitude').value,
            status,
            contact_name: document.getElementById('point-contact-name').value,
            contact_phone: document.getElementById('point-contact-phone').value,
            notes: document.getElementById('point-notes')?.value || '',
            gps_accuracy: document.getElementById('point-gps-accuracy').value,
        };

        if (useFirstApproach) {
            const campaignId = document.getElementById('point-campaign-id')?.value || '';
            if (campaignId) payload.campaign_id = campaignId;
            if (status === 'installation_requested') {
                Object.assign(payload, collectSaleFinalizeFields('point'));
                if (payload.customer_name && !payload.contact_name) {
                    payload.contact_name = payload.customer_name;
                }
                if (payload.customer_phone && !payload.contact_phone) {
                    payload.contact_phone = payload.customer_phone;
                }
            }
            const followUpAt = combineFollowUpAt('point-follow-up-date', 'point-follow-up-time');
            if (status === 'return_later') {
                if (!followUpAt) {
                    pointError.textContent = 'Informe a data do retorno.';
                    pointError.classList.remove('hidden');
                    submitBtn.disabled = false;
                    pointSubmitting = false;
                    return;
                }
                payload.follow_up_at = followUpAt;
            }
        }

        if (!payload.street) {
            payload.street = isFieldSeller ? 'Posição no mapa' : '';
        }
        if (!payload.street) {
            pointError.textContent = 'Informe a rua.';
            pointError.classList.remove('hidden');
            submitBtn.disabled = false;
            pointSubmitting = false;
            return;
        }
        if (!payload.latitude || !payload.longitude) {
            pointError.textContent = 'Marque a posição no mapa (toque ou ajuste) antes de salvar.';
            pointError.classList.remove('hidden');
            submitBtn.disabled = false;
            pointSubmitting = false;
            return;
        }
        if (!payload.city_id && citySelect.value) {
            payload.city_id = citySelect.value;
            document.getElementById('point-city-id').value = citySelect.value;
        }
        if (!payload.city_id) {
            const cityEl = document.getElementById('point-city-id');
            if (cityEl?.options?.length) {
                payload.city_id = cityEl.options[0].value;
                cityEl.value = payload.city_id;
            }
        }

        const body = new FormData();
        appendFormPayload(body, payload);

        const propertyId = document.getElementById('point-property-id').value;
        let url = useFirstApproach ? firstApproachUrl : pointStoreUrl;
        let method = 'POST';
        if (mode === 'edit' && propertyId) {
            url = pointShowTemplate.replace('__PROPERTY__', propertyId);
            body.append('_method', 'PUT');
            payload.property_id = propertyId;
        }

        try {
            if (!navigator.onLine && window.ExpandorOfflineQueue) {
                window.ExpandorOfflineQueue.enqueue(
                    mode === 'edit' ? 'point.update' : (useFirstApproach ? 'first_approach' : 'point.create'),
                    payload
                );
                updateOfflineBadge();
                closePointModal();
                toast(mode === 'edit'
                    ? 'Sem internet — atualização guardada no celular.'
                    : 'Sem internet — atendimento guardado no celular.');
                return;
            }

            const response = await mapFetch(url, {
                method,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                body,
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(formatApiErrors(result, 'Não foi possível salvar o ponto.'));
            }

            closePointModal();
            clearDraftLocationMarker();
            clearRememberedContractProduct();
            const saleDone = useFirstApproach && status === 'installation_requested';
            if (saleDone && result?.data?.commission_awarded) {
                consumeCommissionAwardFromResponse(result.data);
                if (!result.data.commission_awarded.awarded && !result.data.commission_awarded.play_reward) {
                    toast(saleRegisteredToast || 'Cadastro salvo');
                }
            } else {
                const saveMsg = saleDone
                    ? (saleRegisteredToast || 'Cadastro salvo')
                    : (useFirstApproach
                        ? 'Ponto registrado'
                        : (mode === 'edit' ? 'Cliente atualizado' : 'Ponto registrado'));
                toast(saveMsg);
            }
            await loadMarkers({ fit: false, useBbox: true });
            if (result?.data) {
                const created = {
                    property_id: result.data.property_id,
                    latitude: result.data.latitude,
                    longitude: result.data.longitude,
                    status: result.data.status,
                    status_label: result.data.status_label,
                    address: result.data.address,
                    resident_name: result.data.resident_name,
                    resident_phone: result.data.resident_phone,
                    color: '#f97316',
                    location_kind: lastGpsAccuracy != null && lastGpsAccuracy > 50 ? 'low_accuracy' : 'gps',
                    updated_at: nowLabel(),
                };
                if (mode === 'create') {
                    if (useFirstApproach) {
                        visitedInSession.add(Number(result.data.property_id));
                        bumpDayMetric('visits');
                        if (result.data.visit_status === 'interested') bumpDayMetric('interested');
                        if (result.data.visit_status === 'installation_requested') bumpDayMetric('contracts');
                        if (status === 'return_later') bumpTodayChipIfNeeded(payload.follow_up_at);
                    }
                    // Sprint 8.2.8: vendedor volta ao mapa sem modal de ajuste.
                    // Zoom at/above disableClusteringAtZoom; never re-cover the pin with the draft halo.
                    if (isFieldSeller) {
                        if (created.latitude != null && created.longitude != null) {
                            centerMapOnCoords(created.latitude, created.longitude, 16, { showDraft: false });
                        }
                        clearDraftLocationMarker();
                        closeDrawer();
                    } else {
                        openDrawer(created);
                        openPostCreateAdjust(result.data.property_id);
                    }
                } else {
                    openDrawer(created);
                }
            }
        } catch (error) {
            if (!navigator.onLine && window.ExpandorOfflineQueue) {
                window.ExpandorOfflineQueue.enqueue(
                    mode === 'edit' ? 'point.update' : (useFirstApproach ? 'first_approach' : 'point.create'),
                    payload
                );
                updateOfflineBadge();
                closePointModal();
                toast('Sem conexão — ação guardada no celular.');
            } else {
                const friendlySave = 'Não foi possível salvar. Tente novamente.';
                pointError.textContent = error.message || friendlySave;
                pointError.classList.remove('hidden');
                toast(friendlySave, 'error');
            }
        } finally {
            pointSubmitting = false;
            submitBtn.disabled = false;
        }
    }

    citySelect.addEventListener('change', () => {
        filterSectorsByCity();
        initialFitDone = false;
        loadMarkers({ fit: true, useBbox: false });
    });
    sectorSelect.addEventListener('change', () => {
        initialFitDone = false;
        loadMarkers({ fit: true, useBbox: false });
    });
    statusSelect.addEventListener('change', () => {
        initialFitDone = false;
        loadMarkers({ fit: true, useBbox: false });
    });
    campaignSelect.addEventListener('change', () => {
        document.getElementById('visit-campaign-id').value = campaignSelect.value;
        initialFitDone = false;
        loadMarkers({ fit: true, useBbox: false });
    });
    sellerSelect.addEventListener('change', () => {
        renderMarkers(markersCache);
        if (sellerSelect.value || myTeamFilter?.checked) {
            loadMarkers({ fit: false, useBbox: true });
        }
    });

    document.querySelectorAll('.commercial-filter').forEach((el) => {
        el.addEventListener('change', () => renderMarkers(markersCache));
    });
    myTeamFilter?.addEventListener('change', () => {
        renderMarkers(markersCache);
        loadMarkers({ fit: false, useBbox: true });
    });

    function setBasemap(mode) {
        if (!mapProvider?.setBasemap) return;
        mapProvider.setBasemap(mode);
        document.getElementById('basemap-street')?.classList.toggle('is-active', mode === 'street');
        document.getElementById('basemap-satellite')?.classList.toggle('is-active', mode === 'satellite');
        if (mode === 'satellite' && mapProvider.id === 'leaflet_osm' && mapProvider.satelliteNote) {
            toast(mapProvider.satelliteNote);
        }
    }
    document.getElementById('basemap-street')?.addEventListener('click', () => setBasemap('street'));
    document.getElementById('basemap-satellite')?.addEventListener('click', () => setBasemap('satellite'));

    function clearRegionRect() {
        if (regionRect) {
            map.removeLayer(regionRect);
            regionRect = null;
        }
        regionStartLatLng = null;
    }

    function stopRegionSelect() {
        regionSelectMode = false;
        document.body.classList.remove('region-select-mode');
        clearRegionRect();
    }

    function openRegionCampaignModal(bounds) {
        const el = document.getElementById('region-campaign-bounds');
        if (el && bounds) {
            el.textContent = `${bounds.getSouthWest().lat.toFixed(5)}, ${bounds.getSouthWest().lng.toFixed(5)} → ${bounds.getNorthEast().lat.toFixed(5)}, ${bounds.getNorthEast().lng.toFixed(5)}`;
        }
        document.getElementById('region-campaign-modal')?.classList.add('open');
    }

    document.getElementById('btn-select-region')?.addEventListener('click', () => {
        setCompanyFiltersOpen(false);
        if (regionSelectMode) {
            stopRegionSelect();
            toast('Seleção de área cancelada.');
            return;
        }
        regionSelectMode = true;
        document.body.classList.add('region-select-mode');
        closeDrawer();
        toast('Toque e arraste no mapa para marcar a área.');
    });
    document.getElementById('region-campaign-close')?.addEventListener('click', () => {
        document.getElementById('region-campaign-modal')?.classList.remove('open');
        stopRegionSelect();
    });
    document.getElementById('region-campaign-backdrop')?.addEventListener('click', () => {
        document.getElementById('region-campaign-modal')?.classList.remove('open');
        stopRegionSelect();
    });

    map.on('mousedown', (event) => {
        if (!regionSelectMode || adjustState) return;
        regionStartLatLng = event.latlng;
        clearRegionRect();
        regionRect = L.rectangle(L.latLngBounds(regionStartLatLng, regionStartLatLng), {
            className: 'leaflet-region-select',
            interactive: false,
        }).addTo(map);
        map.dragging.disable();
    });
    map.on('mousemove', (event) => {
        if (!regionSelectMode || !regionStartLatLng || !regionRect) return;
        regionRect.setBounds(L.latLngBounds(regionStartLatLng, event.latlng));
    });
    map.on('mouseup', (event) => {
        if (!regionSelectMode || !regionStartLatLng) return;
        map.dragging.enable();
        const bounds = L.latLngBounds(regionStartLatLng, event.latlng);
        if (regionRect) regionRect.setBounds(bounds);
        regionStartLatLng = null;
        regionSelectMode = false;
        document.body.classList.remove('region-select-mode');
        openRegionCampaignModal(bounds);
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        initialFitDone = false;
        loadMarkers({ fit: true, useBbox: false });
    });

    searchInput.addEventListener('input', debounce(flyToSearchHits, 320));
    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            flyToSearchHits();
        }
        if (event.key === 'Escape') {
            hideSearchResults();
        }
    });
    searchResultsEl?.addEventListener('click', (event) => {
        const btn = event.target.closest('button[data-search-index]');
        if (!btn) return;
        let hits = [];
        try { hits = JSON.parse(searchResultsEl.dataset.hits || '[]'); } catch (e) { hits = []; }
        const hit = hits[Number(btn.dataset.searchIndex)];
        if (hit) focusSearchHit(hit);
    });
    document.addEventListener('click', (event) => {
        if (!searchResultsEl || searchResultsEl.classList.contains('hidden')) return;
        if (event.target.closest('#map-search-wrap')) return;
        hideSearchResults();
    });

    document.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            searchInput.focus();
        }
        if (event.key === 'Escape') {
            if (regionSelectMode || document.getElementById('region-campaign-modal')?.classList.contains('open')) {
                document.getElementById('region-campaign-modal')?.classList.remove('open');
                stopRegionSelect();
                return;
            }
            if (adjustState) {
                if (adjustConfirmModal?.classList.contains('open')) {
                    if (adjustState.layer) {
                        adjustState.layer.setLatLng([adjustState.originalLat, adjustState.originalLng]);
                    }
                    closeAdjustConfirm();
                    return;
                }
                cancelAdjustMode({ reload: true });
                return;
            }
            closeDrawer();
            closeVisitModal();
            closePointModal();
            closeDeleteModal();
            closePostCreateAdjust();
            closePostVisitModal();
        }
    });

    map.on('moveend', debounce(() => {
        if (adjustState || regionSelectMode || suppressMoveLoad || !initialFitDone) return;
        loadMarkers({ fit: false, useBbox: true });
    }, 400));

    map.on('click', (event) => {
        mapDebug('mapClick', {
            lat: event?.latlng?.lat ?? null,
            lng: event?.latlng?.lng ?? null,
            adjust: !!adjustState,
            region: !!regionSelectMode,
            canCreate: canCreatePoint,
        });

        // Saved pin wins over "Novo ponto" — including when overlays steal marker DOM clicks.
        const hitMarker = hitTestSavedMarkerFromMapEvent(event);
        if (hitMarker) {
            mapClickTrace('map-click-resolved-to-marker', { propertyId: hitMarker.property_id || null });
            openSavedMarkerDetails(hitMarker, 'map-click-hit-test');
            return;
        }

        if (adjustState) {
            if (Date.now() < ignoreMapClickUntil) return;
            const layer = adjustState.layer;
            if (!layer || !event?.latlng) return;
            layer.setLatLng(event.latlng);
            ignoreMapClickUntil = Date.now() + 400;
            if (adjustState.mode === 'create-draft') {
                fillPointCoords(event.latlng.lat, event.latlng.lng, lastGpsAccuracy);
                cleanupAdjustLayer();
                adjustState = null;
                hideAdjustBanner();
                document.body.classList.remove('adjust-mode');
                suppressMoveLoad = false;
                openMapModal(pointModal);
                toast('Posição atualizada. Confira e salve o ponto.');
                return;
            }
            openAdjustConfirm(event.latlng.lat, event.latlng.lng);
            return;
        }
        if (regionSelectMode) return;
        if (!canCreatePoint) {
            toast('Sem permissão para adicionar pontos neste mapa.', 'error');
            return;
        }
        if (Date.now() < ignoreMapClickUntil) {
            mapClickTrace('map-click-ignored-window');
            return;
        }
        mapClickTrace('map-click-create');
        openCreateAtMapTap(event.latlng);
    });

    clusterGroup.on('click', (event) => {
        ignoreMapClickUntil = Date.now() + 500;
        if (adjustState || regionSelectMode) return;
        const layer = event.layer;
        // Cluster bubble: let MarkerCluster spiderfy/zoom — do not open drawer.
        if (!layer || typeof layer.getAllChildMarkers === 'function') return;
        const marker = layer.options?.markerData;
        if (!marker?.property_id) return;
        mapClickTrace('leaflet-marker-click', { propertyId: marker.property_id || null, via: 'clusterGroup' });
        openSavedMarkerDetails(marker, 'clusterGroup');
    });

    document.getElementById('drawer-close').addEventListener('click', closeDrawer);
    drawerBackdrop.addEventListener('click', closeDrawer);
    document.getElementById('action-visit').addEventListener('click', openVisitModal);
    document.getElementById('action-edit')?.addEventListener('click', () => openPointModal(null, 'edit'));
    document.querySelectorAll('.visit-quick').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.visit-quick').forEach((b) => b.classList.remove('is-selected'));
            btn.classList.add('is-selected');
            const status = btn.dataset.status || '';
            document.getElementById('visit-status').value = status;
            syncVisitContractBlock(status);
            visitError.classList.add('hidden');
        });
    });
    document.querySelectorAll('.point-outcome').forEach((btn) => {
        btn.addEventListener('click', () => {
            const status = btn.dataset.status || '';
            document.getElementById('point-visit-status').value = status;
            syncPointOutcomeUi(status);
            pointError.classList.add('hidden');
        });
    });
    document.querySelectorAll('.sale-cart-add').forEach((btn) => {
        btn.addEventListener('click', () => {
            const prefix = btn.dataset.prefix || 'visit';
            addSaleCartLine(prefix);
        });
    });
    document.getElementById('btn-next-house')?.addEventListener('click', () => goToNextHouse());

    function tipsStorageKey() {
        return `expandor.seller.tips.v1.${currentUserId || 'anon'}`;
    }

    function routeStartedKey() {
        return `expandor.seller.route.started.${currentUserId || 'anon'}`;
    }

    function tipsSeen() {
        try {
            return localStorage.getItem(tipsStorageKey()) === '1';
        } catch (e) {
            return true;
        }
    }

    function markTipsSeen() {
        try {
            localStorage.setItem(tipsStorageKey(), '1');
        } catch (e) { /* ignore */ }
    }

    function routeStarted() {
        try {
            return sessionStorage.getItem(routeStartedKey()) === '1';
        } catch (e) {
            return false;
        }
    }

    function markRouteStarted() {
        try {
            sessionStorage.setItem(routeStartedKey(), '1');
        } catch (e) { /* ignore */ }
    }

    function openSellerTips() {
        document.getElementById('seller-tips-modal')?.classList.add('open');
    }

    function closeSellerTips() {
        document.getElementById('seller-tips-modal')?.classList.remove('open');
    }

    function openSellerBrief() {
        const el = document.getElementById('seller-day-brief');
        if (!el) return;
        const next = findNextHouse();
        const hint = document.getElementById('brief-next-house');
        if (hint) {
            hint.textContent = 'Toque no mapa para registrar um ponto. Minha localização é opcional.';
        }
        el.classList.add('open');
    }

    function closeSellerBrief() {
        document.getElementById('seller-day-brief')?.classList.remove('open');
    }

    function finishTipsAndShowBrief() {
        markTipsSeen();
        closeSellerTips();
        if (!routeStarted()) openSellerBrief();
    }

    async function startSellerRoute() {
        markRouteStarted();
        closeSellerBrief();
        try {
            const gps = await ensureSellerGps();
            if (gps) {
                centerMapOnCoords(gps.latitude, gps.longitude, 17);
            }
        } catch (e) {}
        toast('Mapa pronto — toque no mapa para registrar um ponto.');
    }

    function updateOfflineBadge() {
        const badge = document.getElementById('offline-queue-badge');
        const countEl = document.getElementById('offline-queue-count');
        if (!badge || !window.ExpandorOfflineQueue) return;
        const n = window.ExpandorOfflineQueue.pendingCount();
        if (countEl) countEl.textContent = String(n);
        badge.classList.toggle('hidden', n < 1);
    }

    async function flushOfflineQueue({ silent } = {}) {
        if (!window.ExpandorOfflineQueue || !navigator.onLine) {
            updateOfflineBadge();
            return;
        }
        const result = await window.ExpandorOfflineQueue.flush({
            pointStoreUrl,
            pointShowTemplate,
            visitStoreTemplate,
            firstApproachUrl,
        });
        updateOfflineBadge();
        if (result.synced > 0) {
            toast(result.synced === 1
                ? '1 ação offline enviada'
                : `${result.synced} ações offline enviadas`);
            await loadMarkers({ fit: false, useBbox: true });
        } else if (!silent && result.failed > 0) {
            toast('Algumas ações offline ainda não sincronizaram.', 'error');
        }
    }

    function setTeamView(active) {
        if (isFieldSeller) return;
        teamViewActive = !!active;
        const btn = document.getElementById('btn-team-view');
        const body = document.getElementById('team-view-body');
        body?.classList.toggle('hidden', !teamViewActive);
        if (btn) {
            btn.textContent = teamViewActive ? 'Desativar' : 'Ativar';
            btn.classList.toggle('bg-sky-500/20', teamViewActive);
        }
        document.body.classList.toggle('team-view-mode', teamViewActive);
        if (teamViewActive) {
            if (myTeamFilter) myTeamFilter.checked = false;
            if (sellerSelect) sellerSelect.value = '';
            toast('Visão da equipe ativa — produtividade no painel.');
        } else {
            toast('Visão da equipe desligada.');
        }
        renderMarkers(markersCache);
        loadMarkers({ fit: false, useBbox: true });
    }

    document.getElementById('btn-team-view')?.addEventListener('click', () => {
        setTeamView(!teamViewActive);
    });

    document.querySelectorAll('.team-seller-row').forEach((row) => {
        row.addEventListener('click', () => {
            if (!teamViewActive) setTeamView(true);
            const id = row.dataset.sellerId;
            if (sellerSelect) sellerSelect.value = id || '';
            if (myTeamFilter) myTeamFilter.checked = false;
            renderMarkers(markersCache);
            loadMarkers({ fit: false, useBbox: true });
            const name = row.querySelector('span')?.textContent || 'Vendedor';
            toast(`Foco na rota: ${name.trim()}`);
        });
    });

    document.getElementById('post-visit-next')?.addEventListener('click', () => {
        closePostVisitModal();
    });
    document.getElementById('post-visit-close')?.addEventListener('click', closePostVisitModal);
    document.getElementById('post-visit-backdrop')?.addEventListener('click', closePostVisitModal);
    document.getElementById('action-adjust')?.addEventListener('click', () => {
        if (!pointDetails?.can_adjust || !pointDetails.property_id) return;
        startAdjustMode({
            propertyId: pointDetails.property_id,
            latitude: pointDetails.latitude,
            longitude: pointDetails.longitude,
            color: selectedMarker?.color || '#f97316',
            locationKind: pointDetails.location_kind || 'gps',
            mode: 'existing',
        });
    });
    document.getElementById('action-delete')?.addEventListener('click', openDeleteModal);
    document.getElementById('visit-modal-close').addEventListener('click', closeVisitModal);
    document.getElementById('visit-modal-cancel')?.addEventListener('click', closeVisitModal);
    document.getElementById('visit-modal-backdrop').addEventListener('click', closeVisitModal);
    visitForm.addEventListener('submit', submitVisit);

    function closeAllSellerPanels() {
        document.getElementById('commercial-filters')?.classList.remove('open');
        document.getElementById('toggle-layers')?.classList.remove('seller-tool-is-active');
        document.getElementById('toggle-layers')?.setAttribute('aria-expanded', 'false');
    }

    function setSellerPanel(name, open) {
        if (name !== 'layers') return;
        if (!open) {
            closeAllSellerPanels();
            return;
        }
        document.getElementById('commercial-filters')?.classList.add('open');
        document.getElementById('toggle-layers')?.classList.add('seller-tool-is-active');
        document.getElementById('toggle-layers')?.setAttribute('aria-expanded', 'true');
    }

    function toggleSellerPanel(name) {
        if (name !== 'layers') return;
        const isOpen = document.getElementById('commercial-filters')?.classList.contains('open');
        if (isOpen) closeAllSellerPanels();
        else setSellerPanel('layers', true);
    }

    document.getElementById('toggle-layers')?.addEventListener('click', () => toggleSellerPanel('layers'));
    document.getElementById('close-layers')?.addEventListener('click', () => setSellerPanel('layers', false));

    /** Sprint 8.2.19 — painéis empresa: Filtros / Legenda / Mais exclusivos. */
    function closeMapMoreTools() {
        const more = document.getElementById('map-more-tools');
        if (more) more.open = false;
    }

    function setCompanyFiltersOpen(open) {
        const panel = document.getElementById('map-company-filters-panel');
        const btn = document.getElementById('btn-map-filters');
        if (!panel || !btn) return;
        panel.classList.toggle('hidden', !open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.classList.toggle('ring-1', open);
        btn.classList.toggle('ring-sky-500/60', open);
    }

    function setCompanyLegendOpen(open) {
        const panel = document.getElementById('map-legend-panel');
        const btn = document.getElementById('btn-map-legend');
        const closeBtn = document.getElementById('toggle-legend');
        if (!panel) return;
        panel.classList.toggle('hidden', !open);
        panel.classList.toggle('is-collapsed', !open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        btn?.setAttribute('aria-expanded', open ? 'true' : 'false');
        closeBtn?.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn?.classList.toggle('ring-1', open);
        btn?.classList.toggle('ring-sky-500/60', open);
    }

    function closeCompanyMapOverlays(except = null) {
        if (except !== 'filters') setCompanyFiltersOpen(false);
        if (except !== 'legend') setCompanyLegendOpen(false);
        if (except !== 'mais') closeMapMoreTools();
    }

    function countActiveMapFilters() {
        const ids = ['filter-city', 'filter-sector', 'filter-campaign', 'filter-seller', 'filter-status'];
        return ids.reduce((n, id) => {
            const el = document.getElementById(id);
            return n + (el && String(el.value || '').trim() !== '' ? 1 : 0);
        }, 0);
    }

    function refreshMapFiltersBadge() {
        const count = countActiveMapFilters();
        const wrap = document.getElementById('map-filters-count-wrap');
        const countEl = document.getElementById('map-filters-count');
        if (countEl) countEl.textContent = String(count);
        if (wrap) wrap.classList.toggle('hidden', count === 0);
    }

    document.getElementById('btn-map-filters')?.addEventListener('click', () => {
        const panel = document.getElementById('map-company-filters-panel');
        const willOpen = panel?.classList.contains('hidden');
        closeCompanyMapOverlays(willOpen ? 'filters' : null);
        setCompanyFiltersOpen(!!willOpen);
    });
    document.getElementById('close-map-filters')?.addEventListener('click', () => setCompanyFiltersOpen(false));
    document.getElementById('btn-map-legend')?.addEventListener('click', () => {
        const panel = document.getElementById('map-legend-panel');
        const willOpen = panel?.classList.contains('hidden');
        closeCompanyMapOverlays(willOpen ? 'legend' : null);
        setCompanyLegendOpen(!!willOpen);
    });
    document.getElementById('toggle-legend')?.addEventListener('click', () => setCompanyLegendOpen(false));

    document.getElementById('map-more-tools')?.addEventListener('toggle', () => {
        const more = document.getElementById('map-more-tools');
        if (more?.open) {
            setCompanyFiltersOpen(false);
            setCompanyLegendOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || isFieldSeller) return;
        closeCompanyMapOverlays();
    });

    document.addEventListener('pointerdown', (event) => {
        if (isFieldSeller) return;
        const t = event.target;
        if (!(t instanceof Element)) return;
        const insideFilters = t.closest('#map-company-filters-panel, #btn-map-filters');
        const insideLegend = t.closest('#map-legend-panel, #btn-map-legend');
        const insideMais = t.closest('#map-more-tools');
        if (!insideFilters) setCompanyFiltersOpen(false);
        if (!insideLegend) setCompanyLegendOpen(false);
        if (!insideMais) closeMapMoreTools();
    });

    ['filter-city', 'filter-sector', 'filter-campaign', 'filter-seller', 'filter-status'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', refreshMapFiltersBadge);
    });
    refreshMapFiltersBadge();

    if (isFieldSeller) {
        map.on('click', () => closeAllSellerPanels());
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeAllSellerPanels();
        });
    }

    document.getElementById('toggle-filters-manager')?.addEventListener('click', () => {
        closeCompanyMapOverlays('filters');
        setCompanyFiltersOpen(true);
    });

    document.getElementById('toggle-metrics')?.addEventListener('click', () => {
        closeCompanyMapOverlays('mais');
        metricsPanel.classList.add('open');
        closeMapMoreTools();
    });
    document.getElementById('close-metrics')?.addEventListener('click', () => {
        metricsPanel.classList.remove('open');
    });

    /** Sprint 8.2.11: único CTA GPS — Minha localização (não cria ponto). */
    let locateInFlight = false;

    function setLocateButtonLoading(btn, loading) {
        if (!btn) return;
        btn.disabled = loading;
        btn.setAttribute('aria-busy', loading ? 'true' : 'false');
        const label = btn.querySelector('span');
        if (!label) return;
        if (loading) {
            if (!btn.dataset.labelDefault) {
                btn.dataset.labelDefault = label.textContent || 'Minha localização';
            }
            label.textContent = 'Localizando...';
        } else if (btn.dataset.labelDefault) {
            label.textContent = btn.dataset.labelDefault;
        }
    }

    async function locateMyPosition({ sourceBtn } = {}) {
        if (locateInFlight) {
            return;
        }
        const btn = sourceBtn || document.getElementById('btn-recenter-location');
        locateInFlight = true;
        setLocateButtonLoading(btn, true);
        try {
            const gps = await getGps();
            centerMapOnCoords(gps.latitude, gps.longitude, 17);
            toast('Localização obtida');
        } catch (error) {
            toast(error.message || 'Não foi possível acessar sua localização.', 'error');
        } finally {
            locateInFlight = false;
            setLocateButtonLoading(btn, false);
        }
    }

    document.getElementById('btn-recenter-location')?.addEventListener('click', () => {
        locateMyPosition({ sourceBtn: document.getElementById('btn-recenter-location') }).catch(() => {});
    });
    document.getElementById('point-modal-close')?.addEventListener('click', closePointModal);
    document.getElementById('point-modal-backdrop')?.addEventListener('click', closePointModal);
    document.getElementById('point-modal-cancel')?.addEventListener('click', closePointModal);
    pointForm?.addEventListener('submit', submitPoint);
    document.querySelectorAll('.return-shortcut').forEach((btn) => {
        btn.addEventListener('click', () => {
            applyReturnShortcut(btn.dataset.target || 'point', btn.dataset.days || '1');
        });
    });
    document.getElementById('point-adjust-on-map')?.addEventListener('click', () => {
        const lat = Number(document.getElementById('point-latitude').value);
        const lng = Number(document.getElementById('point-longitude').value);
        if (Number.isNaN(lat) || Number.isNaN(lng)) return;
        startAdjustMode({
            propertyId: null,
            latitude: lat,
            longitude: lng,
            color: '#94a3b8',
            locationKind: lastGpsAccuracy != null && lastGpsAccuracy > 50 ? 'low_accuracy' : 'gps',
            mode: 'create-draft',
        });
    });

    // empty-spot-modal removido (8.2.7+)

    document.getElementById('delete-point-cancel')?.addEventListener('click', closeDeleteModal);
    document.getElementById('delete-point-backdrop')?.addEventListener('click', closeDeleteModal);
    document.getElementById('delete-point-confirm')?.addEventListener('click', confirmDeletePoint);

    document.getElementById('adjust-banner-cancel')?.addEventListener('click', () => cancelAdjustMode({ reload: true }));
    document.getElementById('adjust-confirm-cancel')?.addEventListener('click', () => {
        if (adjustState?.layer) {
            adjustState.layer.setLatLng([adjustState.originalLat, adjustState.originalLng]);
        }
        closeAdjustConfirm();
    });
    document.getElementById('adjust-confirm-backdrop')?.addEventListener('click', () => {
        document.getElementById('adjust-confirm-cancel')?.click();
    });
    document.getElementById('adjust-confirm-save')?.addEventListener('click', saveAdjustedPosition);

    document.getElementById('post-create-adjust-skip')?.addEventListener('click', closePostCreateAdjust);
    document.getElementById('post-create-adjust-backdrop')?.addEventListener('click', closePostCreateAdjust);
    document.getElementById('post-create-adjust-yes')?.addEventListener('click', () => {
        const id = pendingPostCreatePropertyId;
        const marker = markersCache.find((m) => Number(m.property_id) === Number(id)) || selectedMarker;
        closePostCreateAdjust();
        if (!id || !marker) return;
        startAdjustMode({
            propertyId: id,
            latitude: marker.latitude,
            longitude: marker.longitude,
            color: marker.color || '#f97316',
            locationKind: marker.location_kind || 'gps',
            mode: 'existing',
        });
    });

    document.getElementById('seller-tips-continue')?.addEventListener('click', finishTipsAndShowBrief);
    document.getElementById('seller-tips-skip')?.addEventListener('click', finishTipsAndShowBrief);
    document.getElementById('seller-start-route')?.addEventListener('click', () => {
        startSellerRoute().catch(() => {});
    });

    window.addEventListener('online', () => {
        flushOfflineQueue({ silent: false }).catch(() => {});
    });

    filterSectorsByCity();

    function applyDeepLinkFilters() {
        const params = new URLSearchParams(window.location.search);
        const sectorId = params.get('sector_id');
        const userId = params.get('user_id');

        if (sectorId && sectorSelect) {
            const hasOption = Array.from(sectorSelect.options).some((o) => o.value === String(sectorId));
            if (hasOption) {
                sectorSelect.value = String(sectorId);
            }
        }

        if (userId && !isFieldSeller && sellerSelect) {
            const hasSeller = Array.from(sellerSelect.options).some((o) => o.value === String(userId));
            if (hasSeller) {
                sellerSelect.value = String(userId);
            } else {
                const opt = document.createElement('option');
                opt.value = String(userId);
                opt.textContent = `Vendedor #${userId}`;
                sellerSelect.appendChild(opt);
                sellerSelect.value = String(userId);
            }
            if (myTeamFilter) {
                myTeamFilter.checked = true;
                teamViewActive = true;
            }
        }
    }

    function consumeContractProductDeepLink() {
        const params = new URLSearchParams(window.location.search);
        const raw = params.get('contract_product');
        if (!raw) return 0;
        const pid = Number(raw);
        if (!pid) return 0;
        rememberContractProduct(pid);
        params.delete('contract_product');
        const next = params.toString();
        const clean = `${window.location.pathname}${next ? `?${next}` : ''}${window.location.hash || ''}`;
        try { window.history.replaceState({}, '', clean); } catch (e) {}
        return pid;
    }

    applyDeepLinkFilters();
    const contractProductOnLoad = consumeContractProductDeepLink();

    setTimeout(() => {
        map.invalidateSize();
        loadMarkers({ fit: true, useBbox: false }).then(() => {
            const focusId = new URLSearchParams(window.location.search).get('property');
            if (focusId) {
                const entry = layerByPropertyId.get(Number(focusId))
                    || layerByPropertyId.get(String(focusId));
                if (entry?.marker) {
                    openDrawer(entry.marker);
                    if (entry.layer && typeof map.panTo === 'function') {
                        map.panTo([entry.marker.latitude, entry.marker.longitude]);
                    }
                }
            }
            if (isFieldSeller && !contractProductOnLoad) {
                if (!tipsSeen()) {
                    openSellerTips();
                } else if (!routeStarted()) {
                    openSellerBrief();
                }
            }
        }).catch(() => {});
        if (window.lucide) window.lucide.createIcons();
        if (openNewPointOnLoad) {
            locateMyPosition().catch(() => {});
        }
        if (isFieldSeller) {
            ensureSellerGps().catch(() => {});
            const campaignEl = document.getElementById('visit-campaign-id');
            if (campaignEl && !campaignEl.value && campaignEl.options.length === 2) {
                campaignEl.selectedIndex = 1;
                campaignSelect.value = campaignEl.value;
            }
        }
        if (contractProductOnLoad) {
            openContractRegistration(contractProductOnLoad).catch(() => {});
        }
        updateOfflineBadge();
        flushOfflineQueue({ silent: true }).catch(() => {});
    }, 60);

    try {
        window.__mapInspectProperty = function mapInspectProperty(propertyId) {
            const info = inspectSavedMarkerLayer(propertyId);
            mapClickTrace('inspect-layer', info);
            return info;
        };
        window.__mapOpenProperty = function mapOpenProperty(propertyId) {
            const entry = layerByPropertyId.get(Number(propertyId));
            if (!entry?.marker) {
                mapClickTrace('inspect-open-miss', { propertyId: Number(propertyId) });
                return false;
            }
            return openSavedMarkerDetails(entry.marker, 'manual-inspect');
        };
    } catch (_) { /* optional */ }
})();
