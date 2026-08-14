import { MobileAuthService } from './mobile-auth-service.js';
import { mobileApi } from './mobile-api.js';
import { LocationService } from './location-service.js';
import { MapAdapter } from './map-adapter.js';
import { PresentationScreen } from './presentation-screen.js';
import {
    VISIT_OUTCOMES,
    propertyStatusForVisit,
    markerColorForPropertyStatus,
    markerMarkForPropertyStatus,
} from './visit-outcomes.js';
import {
    commissionStatusLabel,
    propertyStatusLabel,
    formatCurrency,
    appVersionLabel,
    escapeHtml,
} from './seller-labels.js';
import {
    initSaleForm,
    validateSaleForm,
    collectSalePayload,
    resetSaleForm,
    addCartLine,
    setSalePrefix,
    prefillSaleForm,
} from './sale-cart.js';
import {
    copyHandoffMessage,
    openOfficeWhatsApp,
    showHandoffText,
    hideHandoffText,
} from './sale-handoff.js';

const GPS_FAIL = 'Não foi possível acessar sua localização.';
const GPS_UNAVAILABLE_HINT = 'Localização indisponível. Use Meu Local quando quiser.';
const GPS_PERMISSION_HINT = 'Permita localização para centralizar o mapa, ou use Meu Local.';
const INITIAL_GPS_TIMEOUT_MS = 8000;
const MAP_MARKERS_DEBOUNCE_MS = 280;
const OFFLINE_MUTATION = 'Sem conexão. Esta ação ainda não pode ser concluída offline.';
const SALE_NETWORK_ERROR = 'Não foi possível concluir a venda. Verifique sua conexão e tente novamente.';
let bootstrap = null;
let searchTimer = null;
let layersOpen = false;
let initialMapGpsDone = false;
let markersRequestSeq = 0;
let mapMarkerReloadTimer = null;
let mapMarkerReloadsBound = false;
let catalogProducts = [];
let adjustUiMode = null; // null | 'create' | 'existing'
let adjustPropertyCanAdjust = false;
let lastOpenedPoint = null;
let currentHandoff = null;

function debugMap(step, detail = {}) {
    try {
        console.info('[EXP MapLoad]', step, detail);
    } catch {
        /* optional */
    }
}

function setMapLoading(visible) {
    const el = $('map-loading-hint');
    if (el) {
        el.hidden = !visible;
    }
}

function mapBoundsLog() {
    const q = MapAdapter.boundsQuery();

    return {
        south: q.min_latitude ?? null,
        west: q.min_longitude ?? null,
        north: q.max_latitude ?? null,
        east: q.max_longitude ?? null,
        zoom: MapAdapter.map?.getZoom?.() ?? null,
    };
}

function debugFlow(step, detail = {}) {
    try {
        console.info('[EXP Vendedor]', step, detail);
    } catch {
        /* optional */
    }
}

function renderOutcomeButtons(targetId, selected = '') {
    const el = $(targetId);
    if (!el) {
        return;
    }

    el.innerHTML = VISIT_OUTCOMES.map((outcome) => `
        <button type="button" class="outcome-chip" data-status="${escapeHtml(outcome.value)}"
            style="--status-color:${escapeHtml(outcome.color)}" aria-pressed="${outcome.value === selected ? 'true' : 'false'}">
            <span class="outcome-chip__swatch" aria-hidden="true">${escapeHtml(outcome.mark)}</span>
            <span class="outcome-chip__label">${escapeHtml(outcome.label)}</span>
        </button>
    `).join('');
}

function paintCampaignInto(prefix) {
    const ctx = bootstrap?.data?.campaign_context;
    const label = $(`${prefix}-campaign-label`);
    const select = $(`${prefix}-campaign`);
    const warning = $(`${prefix}-campaign-warning`);
    const block = $(`${prefix}-campaign-block`);

    if (!ctx) {
        return;
    }

    if (!ctx.has_campaign) {
        if (warning) {
            warning.hidden = false;
            warning.textContent = ctx.no_campaign_message
                || 'Você não possui campanha ativa. Solicite ao gestor.';
        }
        if (label) label.hidden = true;
        if (select) {
            select.hidden = true;
            select.required = false;
        }
        if (block) block.hidden = false;
        return;
    }

    if (warning) {
        warning.hidden = true;
        warning.textContent = '';
    }

    if (!ctx.requires_selection && ctx.active_campaign_id) {
        const campaign = (ctx.campaigns || []).find((row) => Number(row.id) === Number(ctx.active_campaign_id));
        if (label) {
            label.textContent = campaign?.name || 'Campanha ativa';
            label.hidden = false;
        }
        if (select) {
            select.hidden = true;
            select.required = false;
            select.innerHTML = `<option value="${ctx.active_campaign_id}">${escapeHtml(campaign?.name || 'Campanha')}</option>`;
            select.value = String(ctx.active_campaign_id);
        }
        if (block) block.hidden = false;
        return;
    }

    if (label) label.hidden = true;
    if (select) {
        select.hidden = false;
        select.required = true;
        fillSelect(`${prefix}-campaign`, ctx.campaigns || [], 'name');
    }
    if (block) block.hidden = false;
}

function resolveCampaignId(prefix) {
    const ctx = bootstrap?.data?.campaign_context;
    if (ctx?.active_campaign_id && !ctx?.requires_selection) {
        return Number(ctx.active_campaign_id);
    }
    return Number($(`${prefix}-campaign`)?.value || $('visit-campaign')?.value || 0);
}

function syncCreateOutcomeBlocks(status) {
    const isSale = status === 'installation_requested';
    const isReturn = status === 'return_later';
    const returnBlock = $('create-return-block');
    const saleBlock = $('create-sale-block');
    const notesHint = $('create-notes-hint');
    const submit = $('create-point-submit');

    if (returnBlock) returnBlock.hidden = !isReturn;
    if (saleBlock) saleBlock.hidden = !isSale;
    if (notesHint) {
        if (isSale) notesHint.textContent = '(opcional — observação da visita)';
        else if (status === 'interested' || isReturn) notesHint.textContent = '(recomendado)';
        else notesHint.textContent = '(opcional)';
    }
    if (submit) {
        submit.textContent = isSale ? 'Confirmar venda' : 'Salvar ponto';
    }

    if (isSale) {
        resetSaleForm('create-');
        initSaleForm(catalogProducts, bootstrap?.data?.sale_fields || {}, 'create-');
        prefillSaleForm({
            name: $('point-contact')?.value,
            phone: $('point-phone')?.value,
            street: $('point-street')?.value,
            number: $('point-number')?.value,
            neighborhood: $('point-sector-name')?.value,
            ...resolveSaleCity(),
        });
    }
    if (isReturn) {
        ensureReturnDefaults('create-follow-up-date', 'create-follow-up-time', 'create-return-shortcuts');
    }
}

function selectCreateOutcome(status) {
    const value = status || '';
    debugFlow('visitOutcomeSelected', { status: value || null, sheet: 'create' });
    const hidden = $('create-visit-status');
    if (hidden) hidden.value = value;
    document.querySelectorAll('#create-outcome-list .outcome-chip').forEach((button) => {
        const on = button.dataset.status === value;
        button.classList.toggle('is-selected', on);
        button.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    syncCreateOutcomeBlocks(value);
}

function ensureReturnDefaults(dateId = 'visit-follow-up-date', timeId = 'visit-follow-up-time', shortcutsRoot = 'visit-return-shortcuts') {
    const dateEl = $(dateId);
    const timeEl = $(timeId);
    if (!dateEl || dateEl.value) {
        return;
    }
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    dateEl.value = tomorrow.toISOString().slice(0, 10);
    if (timeEl && !timeEl.value) timeEl.value = '09:00';
    document.querySelectorAll(`#${shortcutsRoot} .return-shortcut[data-days="1"]`).forEach((btn) => {
        btn.dataset.active = '1';
    });
}

function combineFollowUpAt(dateId = 'visit-follow-up-date', timeId = 'visit-follow-up-time') {
    const date = $(dateId)?.value;
    const time = $(timeId)?.value || '09:00';
    if (!date) return null;
    return `${date} ${time}:00`;
}

function $(id) {
    return document.getElementById(id);
}

function show(id, on) {
    const el = $(id);
    if (!el) {
        return;
    }

    el.hidden = !on;
}

function paintIcons(root = document) {
    try {
        window.lucide?.createIcons?.({ attrs: { 'stroke-width': 2 } });
    } catch {
        /* optional */
    }
}

function setBanner(text, kind = 'error') {
    const el = $('auth-banner');
    if (!el) {
        return;
    }

    if (!text) {
        el.hidden = true;
        el.textContent = '';

        return;
    }

    el.hidden = false;
    el.dataset.kind = kind;
    el.textContent = text;
}

function setNet(online) {
    const el = $('net-banner');
    if (!el) {
        return;
    }

    el.hidden = online;
    el.textContent = online ? '' : 'Sem conexão';
}

function setPane(name) {
    ['map', 'agenda', 'clients', 'results', 'commissions', 'more'].forEach((key) => {
        show(`pane-${key}`, key === name);
        const btn = $(`nav-${key}`);
        if (btn) {
            btn.dataset.active = key === name ? '1' : '0';
        }
    });

    if (name === 'map') {
        requestAnimationFrame(() => {
            window.L && MapAdapter.refreshLayout?.();
        });
    }
}

function paintUser(data) {
    const name = data?.user?.name || data?.name || '';
    const email = data?.user?.email || data?.email || '';
    const company = data?.company?.name || data?.user?.company?.name || '';
    const hello = $('signed-hello');
    if (hello) {
        hello.textContent = name ? `Olá, ${name.split(' ')[0]}` : 'Olá';
    }
    const companyEl = $('signed-company');
    if (companyEl) {
        companyEl.textContent = company || '';
    }
    const accountName = $('account-name');
    const accountEmail = $('account-email');
    const accountCompany = $('account-company');
    if (accountName) {
        accountName.textContent = name || '—';
    }
    if (accountEmail) {
        accountEmail.textContent = email || '—';
    }
    if (accountCompany) {
        accountCompany.textContent = company || '—';
    }
    const version = $('app-version');
    if (version) {
        version.textContent = `EXP Vendedor · v${appVersionLabel()}`;
    }
    const aboutVersion = $('about-version');
    if (aboutVersion) {
        aboutVersion.textContent = `Versão ${appVersionLabel()}`;
    }
    const aboutBuild = $('about-shell-build');
    if (aboutBuild) {
        const version = document.body?.dataset?.shellVersion || appVersionLabel();
        const builtAt = document.body?.dataset?.shellBuiltAt || '';
        aboutBuild.textContent = builtAt
            ? `Shell ${version} · ${builtAt}`
            : `Shell ${version}`;
    }
}

function toast(message, kind = 'error') {
    const el = $('app-toast');
    if (!el) {
        return;
    }
    el.hidden = !message;
    el.dataset.kind = kind;
    el.textContent = message || '';
}

if (typeof window !== 'undefined') {
    window.ExpandorToast = toast;
}

function humanApiError(error, fallback) {
    const first = error?.payload?.errors
        ? Object.values(error.payload.errors).flat().find(Boolean)
        : null;
    if (error?.code === 'network_offline' || error?.message?.includes('conexão') || error?.message?.includes('internet')) {
        return fallback || SALE_NETWORK_ERROR;
    }

    return first || error?.message || fallback || 'Não foi possível concluir.';
}

function cityNameFromSelect() {
    const select = $('point-city');
    const value = select?.value;
    if (!value) {
        return '';
    }
    const option = select?.selectedOptions?.[0];
    const name = option?.textContent?.trim() || '';
    if (!name || /^selecionar$/i.test(name)) {
        return '';
    }

    return name;
}

function campaignCityForSale() {
    const ctx = bootstrap?.data?.campaign_context;
    const campaignId = resolveCampaignId('visit') || resolveCampaignId('create') || ctx?.active_campaign_id;
    const row = (ctx?.campaigns || []).find((item) => Number(item.id) === Number(campaignId));
    if (row?.city_id && row?.city_name) {
        return { city_id: row.city_id, city: row.city_name, city_locked: true };
    }
    if (ctx?.active_city_id && ctx?.active_city_name) {
        return { city_id: ctx.active_city_id, city: ctx.active_city_name, city_locked: true };
    }

    return null;
}

function resolveSaleCity(property = {}) {
    const fromCampaign = campaignCityForSale();
    if (fromCampaign) {
        return fromCampaign;
    }
    if (property.city_id || property.city) {
        return {
            city_id: property.city_id || null,
            city: property.city || '',
            city_locked: false,
        };
    }
    const fromSelect = cityNameFromSelect();
    if (fromSelect) {
        return {
            city_id: $('point-city')?.value || null,
            city: fromSelect,
            city_locked: false,
        };
    }

    return { city_id: null, city: '', city_locked: false };
}

function commissionBadgeClass(status) {
    const value = String(status || '').toLowerCase();
    if (value === 'paid') {
        return 'badge--paid';
    }
    if (value === 'approved') {
        return 'badge--approved';
    }

    return 'badge--pending';
}

function emptyState(title, text) {
    return `
        <div class="empty-state">
            <p class="empty-state__title">${escapeHtml(title)}</p>
            <p class="empty-state__text">${escapeHtml(text)}</p>
        </div>`;
}

function renderList(targetId, items, emptyTitle, emptyText, row) {
    const el = $(targetId);
    if (!el) {
        return;
    }
    if (!items?.length) {
        el.innerHTML = emptyState(emptyTitle, emptyText);

        return;
    }
    el.innerHTML = items.map(row).join('');
}

async function loadMarkers() {
    const seq = ++markersRequestSeq;
    const bbox = MapAdapter.boundsQuery();
    debugMap('markersRequest', { seq, ...mapBoundsLog() });
    const payload = await mobileApi.markers(bbox);
    if (seq !== markersRequestSeq) {
        debugMap('markersResponse', { seq, stale: true, count: null });

        return;
    }
    const markers = payload.data?.markers || payload.data || [];
    const list = Array.isArray(markers) ? markers : [];
    debugMap('markersResponse', { seq, stale: false, count: list.length });
    const selected = MapAdapter.selectedPropertyId;
    MapAdapter.renderMarkers(list, openPoint);
    if (selected != null) {
        MapAdapter.selectProperty(selected);
    }
}

function scheduleLoadMarkers() {
    if (mapMarkerReloadTimer) {
        clearTimeout(mapMarkerReloadTimer);
    }
    mapMarkerReloadTimer = setTimeout(() => {
        mapMarkerReloadTimer = null;
        loadMarkers().catch(() => {});
    }, MAP_MARKERS_DEBOUNCE_MS);
}

function bindMapMarkerReloads() {
    if (mapMarkerReloadsBound || !MapAdapter.map) {
        return;
    }
    mapMarkerReloadsBound = true;
    MapAdapter.map.on('moveend', scheduleLoadMarkers);
    MapAdapter.map.on('zoomend', scheduleLoadMarkers);
}

async function openPoint(item) {
    const id = item?.property_id || item?.id;
    if (!id) {
        return;
    }
    if (MapAdapter.adjustState) {
        return;
    }

    const payload = await mobileApi.point(id);
    const point = payload.data || {};
    lastOpenedPoint = point;
    $('point-title').textContent = point.resident_name || point.name || `Imóvel #${id}`;
    $('point-meta').textContent = [
        point.status_label || propertyStatusLabel(point.status),
        point.address,
        point.resident_phone,
    ].filter(Boolean).join(' · ');
    $('point-id').value = id;
    MapAdapter.selectProperty(id);
    adjustPropertyCanAdjust = point.can_adjust !== false;
    const adjustBtn = $('point-adjust-map');
    if (adjustBtn) {
        adjustBtn.hidden = !adjustPropertyCanAdjust;
        adjustBtn.disabled = !adjustPropertyCanAdjust;
    }
    const call = $('point-call');
    const wa = $('point-wa');
    if (call) {
        call.href = point.tel || point.actions?.call || '#';
        call.hidden = !point.tel && !point.actions?.call;
    }
    if (wa) {
        wa.href = point.wa || point.actions?.whatsapp || '#';
        wa.hidden = !point.wa && !point.actions?.whatsapp;
    }
    show('point-sheet', true);
    paintIcons($('point-sheet'));
    renderPointHandoff(point);
}

function openAdjustSheet(metaText) {
    const meta = $('adjust-sheet-meta');
    if (meta) {
        meta.textContent = metaText || 'Toque no mapa ou arraste o marcador para a nova posição.';
    }
    show('create-sheet', false);
    show('point-sheet', false);
    show('visit-sheet', false);
    show('adjust-sheet', true);
}

function beginCreatePositionPick() {
    adjustUiMode = 'create';
    MapAdapter.beginAdjust(null, {
        mode: 'create',
        latitude: Number($('point-lat')?.value) || null,
        longitude: Number($('point-lng')?.value) || null,
        onPick: (lat, lng) => {
            debugFlow('adjustCreatePick', { lat, lng });
        },
    });
    openAdjustSheet('Toque no mapa para definir a posição do novo ponto.');
    toast('Toque no mapa para ajustar a posição.', 'status');
}

function beginExistingPositionAdjust() {
    const id = Number($('point-id')?.value || 0);
    if (!id || !adjustPropertyCanAdjust) {
        toast('Sem permissão para ajustar este ponto.', 'error');

        return;
    }
    const entry = MapAdapter.markerRegistry.get(id);
    const lat = entry?.data?.latitude != null ? Number(entry.data.latitude) : null;
    const lng = entry?.data?.longitude != null ? Number(entry.data.longitude) : null;
    adjustUiMode = 'existing';
    MapAdapter.beginAdjust(id, {
        mode: 'existing',
        latitude: lat,
        longitude: lng,
        onPick: (pickLat, pickLng) => {
            debugFlow('adjustExistingPick', { propertyId: id, lat: pickLat, lng: pickLng });
        },
    });
    openAdjustSheet('Toque no mapa ou arraste o marcador. Depois confirme a nova posição.');
    toast('Modo ajustar posição ativo.', 'status');
}

async function confirmAdjustPosition() {
    const pending = MapAdapter.getAdjustPending();
    if (!pending || pending.latitude == null || pending.longitude == null) {
        toast('Toque no mapa para escolher a nova posição.', 'error');

        return;
    }

    if (adjustUiMode === 'create' || pending.mode === 'create') {
        $('point-lat').value = String(pending.latitude);
        $('point-lng').value = String(pending.longitude);
        const statusEl = $('create-location-status');
        if (statusEl) {
            statusEl.textContent = 'Posição pronta para registro';
        }
        MapAdapter.cancelAdjust();
        adjustUiMode = null;
        show('adjust-sheet', false);
        show('create-sheet', true);
        toast('Posição atualizada. Confira e salve o ponto.', 'status');

        return;
    }

    const propertyId = pending.propertyId || Number($('point-id')?.value || 0);
    if (!propertyId) {
        toast('Ponto inválido para ajuste.', 'error');

        return;
    }

    const btn = $('adjust-confirm');
    if (btn) btn.disabled = true;
    try {
        const payload = await mobileApi.adjustPointLocation(propertyId, {
            latitude: pending.latitude,
            longitude: pending.longitude,
        });
        const data = payload.data || {};
        MapAdapter.cancelAdjust();
        adjustUiMode = null;
        show('adjust-sheet', false);
        MapAdapter.updateMarker(propertyId, {
            latitude: data.latitude ?? pending.latitude,
            longitude: data.longitude ?? pending.longitude,
            status: data.status,
            color: data.color,
            mark: data.mark,
            status_label: data.status_label,
        });
        MapAdapter.selectProperty(propertyId);
        MapAdapter.setCenter(
            Number(data.latitude ?? pending.latitude),
            Number(data.longitude ?? pending.longitude),
            17,
        );
        toast('Posição salva no mapa.', 'status');
        await openPoint({ property_id: propertyId });
        loadMarkers().catch(() => {});
    } catch (error) {
        toast(error.message || 'Não foi possível salvar a posição.', 'error');
    } finally {
        if (btn) btn.disabled = false;
    }
}

function cancelAdjustPosition() {
    const wasCreate = adjustUiMode === 'create';
    MapAdapter.cancelAdjust();
    adjustUiMode = null;
    show('adjust-sheet', false);
    if (wasCreate) {
        show('create-sheet', true);
    } else if ($('point-id')?.value) {
        show('point-sheet', true);
    }
    toast('Ajuste cancelado.', 'status');
}

function openCreateSheet(lat, lng, label, source = 'unknown') {
    debugFlow('openCreatePoint', { source, hasCoords: lat != null && lng != null });
    if (lat != null && lng != null) {
        $('point-lat').value = String(lat);
        $('point-lng').value = String(lng);
    }
    const statusEl = $('create-location-status');
    if (statusEl) {
        statusEl.textContent = lat != null && lng != null
            ? 'Posição pronta para registro'
            : 'Selecione um local no mapa ou use Meu Local';
    }
    selectCreateOutcome('');
    resetSaleForm('create-');
    paintCampaignInto('create');
    renderOutcomeButtons('create-outcome-list');
    const notes = $('point-notes');
    if (notes) notes.value = '';
    const err = $('create-error');
    if (err) { err.hidden = true; err.textContent = ''; }
    const submit = $('create-point-submit');
    if (submit) {
        submit.disabled = false;
        submit.textContent = 'Salvar ponto';
    }
    show('visit-sheet', false);
    show('point-sheet', false);
    show('create-sheet', true);
    paintIcons($('create-sheet'));
}

function paintCampaignContext() {
    paintCampaignInto('visit');
    paintCampaignInto('create');
}

function resolveCampaignIdForSubmit() {
    const ctx = bootstrap?.data?.campaign_context;
    if (ctx?.active_campaign_id && !ctx?.requires_selection) {
        return Number(ctx.active_campaign_id);
    }

    return Number($('visit-campaign')?.value || $('point-campaign')?.value || 0);
}

async function resolveVisitCoordinates(propertyId, { allowGps = true } = {}) {
    const marker = MapAdapter.markerRegistry?.get(Number(propertyId))?.data;
    if (marker?.latitude != null && marker?.longitude != null) {
        return {
            latitude: Number(marker.latitude),
            longitude: Number(marker.longitude),
        };
    }

    if (!allowGps) {
        return {};
    }

    try {
        const position = await LocationService.getCurrentPosition({ timeout: 5000, maximumAge: 15000 });

        return {
            latitude: position.latitude,
            longitude: position.longitude,
        };
    } catch {
        return {};
    }
}

function renderVisitOutcomes() {
    renderOutcomeButtons('visit-outcome-list', $('visit-status')?.value || '');
    renderOutcomeButtons('create-outcome-list', $('create-visit-status')?.value || '');
}

function selectVisitOutcome(status) {
    const value = status || '';
    debugFlow('visitOutcomeSelected', { status: value || null });
    const hidden = $('visit-status');
    if (hidden) {
        hidden.value = value;
    }

    document.querySelectorAll('.outcome-chip').forEach((button) => {
        const on = button.dataset.status === value;
        button.classList.toggle('is-selected', on);
        button.setAttribute('aria-pressed', on ? 'true' : 'false');
    });

    syncVisitOutcomeBlocks(value);
}

function syncVisitOutcomeBlocks(status) {
    const isSale = status === 'installation_requested';
    const isReturn = status === 'return_later';
    const returnBlock = $('visit-return-block');
    const saleBlock = $('sale-block');
    const notesHint = $('visit-notes-hint');
    const submit = $('visit-submit');

    if (returnBlock) {
        returnBlock.hidden = !isReturn;
    }
    if (saleBlock) {
        saleBlock.hidden = !isSale;
    }
    if (notesHint) {
        if (isSale) {
            notesHint.textContent = '(opcional — observação da visita)';
        } else if (status === 'interested' || isReturn) {
            notesHint.textContent = '(recomendado)';
        } else {
            notesHint.textContent = '(opcional)';
        }
    }
    if (submit) {
        submit.textContent = isSale ? 'Confirmar venda' : 'Salvar visita';
    }

    if (isSale) {
        resetSaleForm('');
        initSaleForm(catalogProducts, bootstrap?.data?.sale_fields || {}, '');
        prefillSaleForm({
            name: lastOpenedPoint?.resident_name,
            phone: lastOpenedPoint?.resident_phone,
            whatsapp: lastOpenedPoint?.resident_whatsapp,
            document: lastOpenedPoint?.resident_document,
            birth_date: lastOpenedPoint?.resident_birth_date,
            street: lastOpenedPoint?.street,
            number: lastOpenedPoint?.number,
            neighborhood: lastOpenedPoint?.neighborhood,
            reference: lastOpenedPoint?.reference,
            ...resolveSaleCity({
                city: lastOpenedPoint?.city_name,
                city_id: lastOpenedPoint?.city_id,
            }),
        });
    }

    if (isReturn) {
        ensureReturnDefaults('visit-follow-up-date', 'visit-follow-up-time', 'visit-return-shortcuts');
    } else if (!isSale) {
        const dateEl = $('visit-follow-up-date');
        const timeEl = $('visit-follow-up-time');
        if (dateEl) {
            dateEl.value = '';
        }
        if (timeEl) {
            timeEl.value = '';
        }
        document.querySelectorAll('#visit-return-shortcuts .return-shortcut').forEach((btn) => {
            btn.dataset.active = '0';
        });
    }
}

function setVisitError(message) {
    const el = $('visit-error');
    if (!el) {
        return;
    }
    if (!message) {
        el.hidden = true;
        el.textContent = '';

        return;
    }
    el.hidden = false;
    el.textContent = message;
}

function openVisitSheet(propertyId, options = {}) {
    const id = String(propertyId || $('point-id')?.value || '');
    if (!id) {
        return;
    }

    debugFlow('openVisitFlow', {
        propertyId: id,
        followUpId: options.followUpId || null,
        source: options.source || 'unknown',
    });

    $('visit-property-id').value = id;
    $('point-id').value = id;
    $('visit-follow-up-id').value = options.followUpId ? String(options.followUpId) : '';
    selectVisitOutcome('');
    resetSaleForm();
    renderVisitOutcomes();
    const notes = $('visit-notes');
    if (notes) {
        notes.value = '';
    }
    setVisitError('');
    const submit = $('visit-submit');
    if (submit) {
        submit.disabled = false;
        submit.textContent = 'Salvar visita';
    }
    MapAdapter.selectProperty(id);
    paintCampaignContext();

    const subtitle = $('visit-sheet-subtitle');
    if (subtitle) {
        subtitle.textContent = options.subtitle || 'Como foi a abordagem?';
    }

    const campaignDefault = bootstrap?.data?.active_campaign_id || $('point-campaign')?.value;
    const campaignSelect = $('visit-campaign');
    if (campaignDefault && campaignSelect && !campaignSelect.hidden) {
        campaignSelect.value = String(campaignDefault);
    }

    show('point-sheet', false);
    show('visit-sheet', true);
}

function closeVisitSheet() {
    show('visit-sheet', false);
    setVisitError('');
}

async function loadAgenda() {
    const payload = await mobileApi.agenda({ scope: 'today' });
    renderList(
        'agenda-list',
        payload.data || [],
        'Agenda livre hoje',
        'Nenhum retorno agendado para hoje. Use o mapa para visitar novos imóveis.',
        (item) => `
            <article class="card">
                <div class="card__row">
                    <p class="card__title">${escapeHtml(item.scheduled_label || 'Retorno')}</p>
                    <span class="badge badge--pending">Agendado</span>
                </div>
                <p class="card__meta">${escapeHtml(item.address || 'Endereço não informado')}</p>
                ${item.campaign ? `<p class="card__meta">${escapeHtml(item.campaign)}</p>` : ''}
                ${item.notes ? `<p class="card__meta">${escapeHtml(item.notes)}</p>` : ''}
                <div class="sheet-actions" style="margin-top:0.65rem">
                    <button type="button" class="btn btn-accent btn-block" data-complete-follow-up="${item.id}" data-property-id="${item.property_id || ''}">Registrar retorno</button>
                    <button type="button" class="btn btn-ghost btn-block" data-open-point="${item.property_id || ''}">Ver imóvel</button>
                </div>
            </article>
        `,
    );
}

async function loadClients(q = '') {
    const payload = await mobileApi.points({ q, per_page: 24 });
    renderList(
        'clients-list',
        payload.data || [],
        'Nenhum cliente encontrado',
        'Cadastre imóveis pelo mapa ou ajuste a busca.',
        (item) => {
            const status = item.status_label || propertyStatusLabel(item.status);
            const hasMap = item.latitude != null && item.longitude != null;

            return `
                <article class="card">
                    <p class="card__title">${escapeHtml(item.resident_name || item.name || `Imóvel #${item.id}`)}</p>
                    <p class="card__meta">${escapeHtml(status)}</p>
                    <p class="card__meta">${escapeHtml(item.address || 'Endereço não informado')}</p>
                    <div class="sheet-actions" style="margin-top:0.65rem">
                        ${hasMap ? `<button type="button" class="btn btn-ghost" data-map-point="${item.property_id || item.id}" data-lat="${item.latitude}" data-lng="${item.longitude}">Ver no mapa</button>` : ''}
                        <button type="button" class="btn btn-primary" data-open-point="${item.property_id || item.id}">Ver detalhes</button>
                    </div>
                </article>`;
        },
    );
}

async function loadResults() {
    const payload = await mobileApi.results();
    const data = payload.data || {};
    const commissions = data.commissions || {};
    const el = $('results-list');
    if (!el) {
        return;
    }

    el.innerHTML = `
        <div class="stat-grid">
            <article class="stat-card">
                <p class="stat-card__label">Visitas hoje</p>
                <p class="stat-card__value">${data.visits_today ?? '—'}</p>
            </article>
            <article class="stat-card">
                <p class="stat-card__label">Retornos</p>
                <p class="stat-card__value">${data.pending_follow_ups ?? '—'}</p>
            </article>
            <article class="stat-card">
                <p class="stat-card__label">Vendas (30 dias)</p>
                <p class="stat-card__value">${commissions.sales_count ?? '—'}</p>
            </article>
            <article class="stat-card">
                <p class="stat-card__label">Conversão</p>
                <p class="stat-card__value">—</p>
                <p class="card__meta">Indisponível na API mobile</p>
            </article>
            <article class="stat-card" style="grid-column:1/-1">
                <p class="stat-card__label">Comissão (30 dias)</p>
                <p class="stat-card__value">${formatCurrency(commissions.total_amount)}</p>
                <p class="card__meta">Pago: ${formatCurrency(commissions.paid_amount)} · Pendente: ${formatCurrency(commissions.pending_amount)}</p>
            </article>
        </div>
        <p class="card__meta">Campanhas ativas: ${data.active_campaigns ?? '—'}</p>
    `;
}

async function loadCommissions() {
    const payload = await mobileApi.commissions();
    const items = payload.data?.items || [];
    renderList(
        'commissions-list',
        items,
        'Nenhuma comissão',
        'Suas comissões dos últimos 30 dias aparecerão aqui.',
        (item) => `
            <article class="card">
                <div class="card__row">
                    <p class="card__title">${escapeHtml(item.product || 'Comissão')}</p>
                    <span class="badge ${commissionBadgeClass(item.status)}">${escapeHtml(commissionStatusLabel(item.status))}</span>
                </div>
                <p class="card__meta">Valor: ${formatCurrency(item.commission_amount)}</p>
                ${item.earned_at ? `<p class="card__meta">Data da venda: ${escapeHtml(item.earned_at)}</p>` : ''}
            </article>
        `,
    );
}

function toggleLayersMenu(force) {
    layersOpen = force ?? !layersOpen;
    const menu = $('map-layers-menu');
    if (menu) {
        menu.hidden = !layersOpen;
    }
}

function syncLayerButtons() {
    $('layer-street')?.setAttribute('data-active', MapAdapter.activeBasemap === 'street' ? '1' : '0');
    $('layer-satellite')?.setAttribute('data-active', MapAdapter.activeBasemap === 'satellite' ? '1' : '0');
}

async function onGps() {
    toast('');
    try {
        const position = await LocationService.getCurrentPosition();
        MapAdapter.recenterGps(position);
        await loadMarkers();
    } catch (error) {
        toast(error.message || GPS_FAIL, 'error');
    }
}

async function tryInitialMapGps() {
    if (initialMapGpsDone) {
        return;
    }

    initialMapGpsDone = true;

    try {
        const permission = await LocationService.ensureForegroundPermission();
        if (!permission.granted) {
            toast(GPS_PERMISSION_HINT, 'status');

            return;
        }

        const position = await LocationService.getCurrentPosition({
            timeout: INITIAL_GPS_TIMEOUT_MS,
            maximumAge: 0,
        });
        MapAdapter.recenterGps(position);
        debugMap('gpsResolved', {
            accuracy: position.accuracy ?? null,
            ...mapBoundsLog(),
        });
        await MapAdapter.waitForView();
    } catch (error) {
        if (error?.code === 'permission_denied') {
            toast(GPS_PERMISSION_HINT, 'status');

            return;
        }

        toast(GPS_UNAVAILABLE_HINT, 'status');
    }
}

async function prepareInitialMap() {
    debugMap('initialStart');
    MapAdapter.refreshLayout();
    await MapAdapter.waitForView();
    debugMap('mapReady', mapBoundsLog());
    setMapLoading(true);
    try {
        await tryInitialMapGps();
        debugMap('bounds', mapBoundsLog());
        await loadMarkers();
        bindMapMarkerReloads();
        debugMap('initialComplete', mapBoundsLog());
    } finally {
        setMapLoading(false);
    }
}

async function onCreatePoint(event) {
    event.preventDefault();
    if (navigator.onLine === false) {
        toast(OFFLINE_MUTATION, 'error');
        return;
    }

    const lat = Number($('point-lat')?.value);
    const lng = Number($('point-lng')?.value);
    if (Number.isNaN(lat) || Number.isNaN(lng)) {
        setCreateError('Selecione um local no mapa ou use Meu Local.');
        return;
    }

    const status = $('create-visit-status')?.value;
    if (!bootstrap?.data?.campaign_context?.has_campaign) {
        setCreateError(bootstrap?.data?.campaign_context?.no_campaign_message || 'Você não possui campanha ativa.');
        return;
    }
    const campaignId = resolveCampaignId('create');
    if (!campaignId) {
        setCreateError('Escolha a campanha deste atendimento.');
        return;
    }
    if (!status) {
        setCreateError('Escolha a situação / interesse.');
        return;
    }
    if (status === 'return_later' && !combineFollowUpAt('create-follow-up-date', 'create-follow-up-time')) {
        setCreateError('Informe a data do retorno.');
        return;
    }
    if (status === 'installation_requested') {
        setSalePrefix('create-');
        const saleError = validateSaleForm();
        if (saleError) {
            setCreateError(saleError);
            return;
        }
    }

    const citySelect = $('point-city');
    const saleCity = resolveSaleCity();
    let cityId = Number(saleCity.city_id || citySelect?.value || 0);
    if (!cityId && citySelect?.options?.length) {
        const usable = Array.from(citySelect.options).find((option) => option.value);
        if (usable) {
            cityId = Number(usable.value);
            citySelect.value = String(cityId);
        }
    }
    if (saleCity.city_id) {
        cityId = Number(saleCity.city_id);
    }
    if (!cityId) {
        setCreateError('Território sem cidade ativa.');
        return;
    }

    const body = {
        city_id: cityId,
        sector_name: $('point-sector-name')?.value?.trim() || undefined,
        street: $('point-street')?.value?.trim() || 'Posição no mapa',
        number: $('point-number')?.value || undefined,
        latitude: lat,
        longitude: lng,
        status,
        campaign_id: campaignId,
        contact_name: $('point-contact')?.value || undefined,
        contact_phone: $('point-phone')?.value || undefined,
        notes: $('point-notes')?.value || undefined,
    };

    if (status === 'return_later') {
        body.follow_up_at = combineFollowUpAt('create-follow-up-date', 'create-follow-up-time');
    }
    if (status === 'installation_requested') {
        setSalePrefix('create-');
        Object.assign(body, collectSalePayload());
        if (body.customer_name && !body.contact_name) body.contact_name = body.customer_name;
        if (body.customer_phone && !body.contact_phone) body.contact_phone = body.customer_phone;
        if (body.install_street) {
            body.street = body.install_street;
        }
        if (body.install_number) {
            body.number = body.install_number;
        }
        if (body.install_neighborhood && !body.sector_name) {
            body.neighborhood = body.install_neighborhood;
        }
    }

    const submitBtn = $('create-point-submit');
    const originalLabel = submitBtn?.textContent;
    if (submitBtn) {
        submitBtn.disabled = true;
        if (status === 'installation_requested') {
            submitBtn.textContent = 'Confirmando…';
        }
    }
    setCreateError('');

    try {
        const created = await mobileApi.firstApproach(body);
        const pointId = created.data?.property_id || created.data?.id;
        debugFlow('pointCreated', { propertyId: pointId || null, firstApproach: true, status });
        toast(status === 'installation_requested' ? 'Venda registrada.' : 'Ponto salvo.', 'status');
        show('create-sheet', false);

        const propertyStatus = propertyStatusForVisit(status) || created.data?.status;
        if (pointId && propertyStatus) {
            MapAdapter.updateMarker(pointId, {
                status: propertyStatus,
                color: markerColorForPropertyStatus(propertyStatus),
                mark: markerMarkForPropertyStatus(propertyStatus),
                status_label: propertyStatusLabel(propertyStatus),
                latitude: lat,
                longitude: lng,
            });
            MapAdapter.selectProperty(pointId);
            MapAdapter.setCenter(lat, lng, 17);
        }

        if (status === 'installation_requested') {
            handleSaleSuccess(created.data);
        } else if (created.data?.commission_awarded) {
            showReward(created.data.commission_awarded);
        }
        if (status === 'return_later') {
            loadAgenda().catch(() => {});
        }
        loadMarkers().catch(() => {});
        if (submitBtn) {
            submitBtn.textContent = originalLabel || 'Salvar ponto';
        }
    } catch (error) {
        setCreateError(humanApiError(error, status === 'installation_requested' ? SALE_NETWORK_ERROR : OFFLINE_MUTATION));
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalLabel || (status === 'installation_requested' ? 'Confirmar venda' : 'Salvar ponto');
        }
    }
}

function setCreateError(message) {
    const el = $('create-error');
    if (!el) {
        if (message) toast(message, 'error');
        return;
    }
    if (!message) {
        el.hidden = true;
        el.textContent = '';
        return;
    }
    el.hidden = false;
    el.textContent = message;
}

async function submitVisit() {
    if (navigator.onLine === false) {
        toast(OFFLINE_MUTATION, 'error');

        return;
    }

    const id = $('visit-property-id')?.value || $('point-id')?.value;
    const status = $('visit-status')?.value;
    const followUpId = $('visit-follow-up-id')?.value;
    const campaignId = resolveCampaignId('visit');
    const submitBtn = $('visit-submit');

    setVisitError('');

    if (!bootstrap?.data?.campaign_context?.has_campaign) {
        setVisitError(bootstrap?.data?.campaign_context?.no_campaign_message
            || 'Você não possui campanha ativa.');

        return;
    }
    if (!campaignId) {
        setVisitError('Escolha a campanha desta visita.');

        return;
    }
    if (!status) {
        setVisitError('Escolha como foi a abordagem.');

        return;
    }
    if (status === 'return_later' && !combineFollowUpAt('visit-follow-up-date', 'visit-follow-up-time')) {
        setVisitError('Informe a data do retorno.');

        return;
    }
    if (status === 'installation_requested') {
        setSalePrefix('');
        const saleError = validateSaleForm();
        if (saleError) {
            setVisitError(saleError);

            return;
        }
    }

    const coords = await resolveVisitCoordinates(id, { allowGps: status !== 'installation_requested' });
    const body = {
        status,
        campaign_id: campaignId,
        notes: $('visit-notes')?.value || '',
        ...coords,
    };

    if (status === 'return_later') {
        body.follow_up_at = combineFollowUpAt('visit-follow-up-date', 'visit-follow-up-time');
    }
    if (status === 'installation_requested') {
        setSalePrefix('');
        Object.assign(body, collectSalePayload());
    }

    if (submitBtn) {
        submitBtn.disabled = true;
        if (status === 'installation_requested') {
            submitBtn.dataset.label = submitBtn.textContent;
            submitBtn.textContent = 'Confirmando…';
        }
    }

    try {
        const payload = followUpId
            ? await mobileApi.completeFollowUp(followUpId, body)
            : (status === 'installation_requested'
                ? await mobileApi.sale(id, body)
                : await mobileApi.visit(id, body));

        debugFlow('visitSubmitted', { status, propertyId: id, followUpId: followUpId || null });

        toast(
            status === 'installation_requested' ? 'Venda registrada.' : 'Visita registrada.',
            'status',
        );
        closeVisitSheet();

        const propertyStatus = propertyStatusForVisit(status);
        if (propertyStatus) {
            MapAdapter.updateMarker(id, {
                status: propertyStatus,
                color: markerColorForPropertyStatus(propertyStatus),
                mark: markerMarkForPropertyStatus(propertyStatus),
                status_label: propertyStatusLabel(propertyStatus),
            });
        } else {
            MapAdapter.selectProperty(id);
        }

        if (status === 'installation_requested') {
            handleSaleSuccess(payload.data);
        } else if (payload.data?.commission_awarded) {
            showReward(payload.data.commission_awarded);
        }

        if (followUpId || status === 'return_later') {
            loadAgenda().catch(() => {});
        }

        loadMarkers().catch(() => {});
    } catch (error) {
        setVisitError(humanApiError(error, status === 'installation_requested' ? SALE_NETWORK_ERROR : OFFLINE_MUTATION));
        if (submitBtn) {
            submitBtn.disabled = false;
            if (submitBtn.dataset.label) {
                submitBtn.textContent = submitBtn.dataset.label;
            }
        }
    }
}

function playCommissionAudio() {
    const audio = $('commission-audio') || new Audio('./sounds/commission-coins.wav');
    audio.currentTime = 0;
    audio.play?.().catch(() => {});
}

function showReward(payload) {
    if (!payload?.play_reward && !payload?.awarded) {
        return;
    }

    const overlay = $('reward-overlay');
    const amount = $('reward-amount');
    if (amount) {
        amount.textContent = formatCurrency(payload.amount || 0);
    }
    if (overlay) {
        overlay.hidden = false;
    }
    playCommissionAudio();
}

function hideReward() {
    const overlay = $('reward-overlay');
    if (overlay) {
        overlay.hidden = true;
    }
}

function canSendOfficeHandoff(handoff) {
    if (handoff?.can_send != null) {
        return Boolean(handoff.can_send);
    }

    return Boolean(handoff?.whatsapp_enabled && handoff?.whatsapp_url);
}

function handoffUnavailableHint(handoff) {
    if (canSendOfficeHandoff(handoff)) {
        return '';
    }
    if (handoff?.whatsapp_configured === false) {
        return 'WhatsApp do escritório não configurado.';
    }
    if (handoff?.whatsapp_enabled === false) {
        return 'WhatsApp do escritório não está habilitado.';
    }

    return 'WhatsApp do escritório não configurado.';
}

function paintHandoffButtons(handoff) {
    const canSend = canSendOfficeHandoff(handoff);
    ['sale-success-whatsapp', 'handoff-whatsapp', 'point-handoff-whatsapp'].forEach((id) => {
        const el = $(id);
        if (el) {
            el.hidden = !canSend;
        }
    });
    const hint = handoffUnavailableHint(handoff);
    ['sale-success-handoff-hint', 'point-handoff-hint'].forEach((id) => {
        const el = $(id);
        if (!el) {
            return;
        }
        el.textContent = hint;
        el.hidden = !hint;
    });
    const copyOk = Boolean(handoff?.copy_available ?? handoff?.message);
    const viewOk = Boolean(handoff?.view_available ?? handoff?.message);
    ['sale-success-copy', 'point-handoff-copy', 'handoff-copy'].forEach((id) => {
        const el = $(id);
        if (el) {
            el.hidden = !copyOk;
        }
    });
    ['sale-success-view', 'point-handoff-view'].forEach((id) => {
        const el = $(id);
        if (el) {
            el.hidden = !viewOk;
        }
    });
}

async function hydrateSaleHandoff(data) {
    const fromSale = data?.office_handoff && typeof data.office_handoff === 'object'
        ? data.office_handoff
        : null;
    currentHandoff = fromSale;
    const saleId = Number(data?.sale_id || fromSale?.sale_id || 0) || null;
    if (saleId) {
        try {
            const payload = await mobileApi.saleHandoff(saleId);
            if (payload?.data && typeof payload.data === 'object') {
                currentHandoff = payload.data;
            }
        } catch {
            /* keep sale 2xx handoff */
        }
    }
    debugFlow('saleSuccess', {
        sale_id: saleId,
        handoff_available: Boolean(currentHandoff?.message),
        office_whatsapp_enabled: Boolean(currentHandoff?.whatsapp_enabled),
        office_whatsapp_configured: Boolean(currentHandoff?.whatsapp_configured),
    });
}

async function handleSaleSuccess(data) {
    await hydrateSaleHandoff(data);
    const awarded = data?.commission_awarded;
    if (awarded?.play_reward || awarded?.awarded) {
        playCommissionAudio();
    }
    const label = $('sale-success-label');
    if (label) {
        label.textContent = currentHandoff?.sale_label || `Venda Expandor #${data?.sale_id || ''}`;
    }
    const commission = $('sale-success-commission');
    if (commission) {
        const amount = currentHandoff?.commission_label
            || (awarded?.amount != null ? formatCurrency(awarded.amount) : '—');
        commission.textContent = `Comissão: ${amount}`;
    }
    const status = $('sale-success-status');
    if (status) {
        const raw = currentHandoff?.commission_status || awarded?.status || 'pending';
        status.textContent = `Status: ${commissionStatusLabel(raw)}`;
        status.classList.toggle('badge--pending', String(raw).toLowerCase() !== 'paid');
    }
    paintHandoffButtons(currentHandoff);
    show('sale-success-sheet', true);
    loadResults().catch(() => {});
    loadCommissions().catch(() => {});
    loadClients().catch(() => {});
}

function hideSaleSuccess() {
    show('sale-success-sheet', false);
}

async function renderPointHandoff(point) {
    const block = $('point-handoff-block');
    if (!block) {
        return;
    }
    const saleId = point?.last_sale_id;
    if (!saleId) {
        block.hidden = true;
        return;
    }
    try {
        const payload = await mobileApi.saleHandoff(saleId);
        currentHandoff = payload.data || null;
        block.hidden = !currentHandoff?.message;
        paintHandoffButtons(currentHandoff);
    } catch {
        block.hidden = true;
    }
}

async function hydrateCatalog() {
    try {
        const [territory, products, campaigns, bootPayload] = await Promise.all([
            mobileApi.territory(),
            mobileApi.products(),
            fetchCampaigns(),
            bootstrap?.data ? Promise.resolve(bootstrap) : mobileApi.bootstrap(),
        ]);
        if (!bootstrap?.data && bootPayload?.data) {
            bootstrap = bootPayload;
        }
        catalogProducts = products.data || [];
        fillSelect('point-city', territory.data?.cities || [], 'name');
        const cities = territory.data?.cities || [];
        const cityBlock = $('create-city-block');
        if (cityBlock) {
            cityBlock.hidden = cities.length <= 1;
        }
        if (cities.length === 1 && $('point-city')) {
            $('point-city').value = String(cities[0].id);
        }
        fillSelect('visit-campaign', campaigns, 'name');
        fillSelect('point-campaign', campaigns, 'name');
        fillSelect('create-campaign', campaigns, 'name');
        initSaleForm(catalogProducts, bootstrap?.data?.sale_fields || {}, '');
        paintCampaignContext();
        renderVisitOutcomes();
    } catch {
        /* catalog optional until online */
    }
}

async function fetchCampaigns() {
    try {
        const response = await (await import('./api-fetch.js')).apiFetch('/api/mobile/v1/campaigns');
        const payload = await response.json();

        return payload.data || [];
    } catch {
        return [];
    }
}

function fillSelect(id, items, labelKey) {
    const el = $(id);
    if (!el) {
        return;
    }
    const current = el.value;
    el.innerHTML = '<option value="">Selecionar</option>' + items.map((item) =>
        `<option value="${item.id}">${escapeHtml(item[labelKey] || item.id)}</option>`
    ).join('');
    if (current) {
        el.value = current;
    }
}

function focusMapPoint(lat, lng) {
    setPane('map');
    if (lat != null && lng != null) {
        MapAdapter.setCenter(Number(lat), Number(lng), 17);
    }
}

async function enterApp(data) {
    paintUser(data);
    document.body.classList.add('seller-app-mode');
    show('screen-login', false);
    show('screen-app', true);
    setBanner('');
    setPane('map');

    try {
        bootstrap = await mobileApi.bootstrap();
        paintUser(bootstrap.data || data);
        paintCampaignContext();
        if (!MapAdapter.map) {
            MapAdapter.init('seller-map', { zoom: 13 });
            syncLayerButtons();
        }
        MapAdapter.onMapClick = (lat, lng) => {
            if (MapAdapter.adjustState) {
                return;
            }
            openCreateSheet(lat, lng, null, 'map-tap');
        };
        await hydrateCatalog();
        renderVisitOutcomes();
        await prepareInitialMap();
        paintIcons();
    } catch (error) {
        toast(error.message || 'Não foi possível carregar o app.', 'error');
    }
}

function showLogin(message) {
    document.body.classList.remove('seller-app-mode');
    show('screen-app', false);
    show('screen-login', true);
    if (message) {
        setBanner(message, 'status');
    }
}

async function onSubmit(event) {
    event.preventDefault();
    const button = $('login-submit');
    const email = $('login-email')?.value?.trim();
    const password = $('login-password')?.value ?? '';
    if (button) {
        button.disabled = true;
    }
    setBanner('');

    if (window.Capacitor?.isNativePlatform?.() && ! String(window.EXPANDOR_API_BASE || '').trim()) {
        setBanner('App sem URL da API. Recompile com CAP_API_URL apontando para o servidor.', 'error');
        if (button) {
            button.disabled = false;
        }

        return;
    }

    try {
        const data = await MobileAuthService.login(email, password);
        await enterApp(data);
    } catch (error) {
        if (error.code === 'network_offline' || error.code === 'api_base_missing') {
            setBanner(error.message, 'error');
        } else if (error.status === 500) {
            setBanner('Serviço indisponível. Tente novamente em instantes.', 'error');
        } else {
            setBanner(error.message || 'Não foi possível entrar.', 'error');
        }
    } finally {
        if (button) {
            button.disabled = false;
        }
    }
}

async function onLogout() {
    await MobileAuthService.logout();
    PresentationScreen.close();
    initialMapGpsDone = false;
    showLogin();
    const password = $('login-password');
    if (password) {
        password.value = '';
    }
}

function bindApp() {
    $('nav-map')?.addEventListener('click', () => setPane('map'));
    $('nav-agenda')?.addEventListener('click', () => {
        setPane('agenda');
        loadAgenda().catch((error) => toast(error.message, 'error'));
    });
    $('nav-clients')?.addEventListener('click', () => {
        setPane('clients');
        loadClients($('clients-q')?.value || '').catch((error) => toast(error.message, 'error'));
    });
    $('nav-results')?.addEventListener('click', () => {
        setPane('results');
        loadResults().catch((error) => toast(error.message, 'error'));
    });
    $('nav-commissions')?.addEventListener('click', () => {
        setPane('commissions');
        loadCommissions().catch((error) => toast(error.message, 'error'));
    });
    $('nav-more')?.addEventListener('click', () => setPane('more'));

    $('gps-btn')?.addEventListener('click', onGps);
    $('create-point-open')?.addEventListener('click', async () => {
        debugFlow('openCreatePoint', { source: 'novo-ponto-button' });
        try {
            const position = await LocationService.getCurrentPosition({ timeout: 8000, maximumAge: 15000 });
            openCreateSheet(position.latitude, position.longitude, null, 'novo-ponto-gps');
        } catch {
            openCreateSheet(null, null, null, 'novo-ponto-no-gps');
        }
    });
    $('map-layers-btn')?.addEventListener('click', (event) => {
        event.stopPropagation();
        toggleLayersMenu();
    });
    $('layer-street')?.addEventListener('click', (event) => {
        event.stopPropagation();
        MapAdapter.setBasemap('street');
        syncLayerButtons();
        toggleLayersMenu(false);
    });
    $('layer-satellite')?.addEventListener('click', (event) => {
        event.stopPropagation();
        MapAdapter.setBasemap('satellite');
        syncLayerButtons();
        toggleLayersMenu(false);
    });
    document.querySelector('.map-toolbar')?.addEventListener('click', (event) => event.stopPropagation());
    document.querySelector('.map-toolbar--left')?.addEventListener('click', (event) => event.stopPropagation());
    document.addEventListener('click', () => toggleLayersMenu(false));

    $('create-point-cancel')?.addEventListener('click', () => show('create-sheet', false));
    $('create-point-form')?.addEventListener('submit', onCreatePoint);

    $('point-register-visit')?.addEventListener('click', () => openVisitSheet($('point-id')?.value));
    $('visit-sheet-close')?.addEventListener('click', closeVisitSheet);
    $('visit-submit')?.addEventListener('click', submitVisit);
    $('visit-outcome-list')?.addEventListener('click', (event) => {
        const button = event.target.closest?.('.outcome-chip');
        if (button?.dataset.status) {
            selectVisitOutcome(button.dataset.status);
            setVisitError('');
        }
    });
    $('visit-return-shortcuts')?.addEventListener('click', (event) => {
        const button = event.target.closest?.('.return-shortcut');
        if (!button) {
            return;
        }
        const dateEl = $('visit-follow-up-date');
        const timeEl = $('visit-follow-up-time');
        const days = button.dataset.days;
        document.querySelectorAll('.return-shortcut').forEach((row) => {
            row.dataset.active = row === button ? '1' : '0';
        });
        if (days === 'pick') {
            dateEl?.focus();

            return;
        }
        const offset = Number(days || 1);
        const target = new Date();
        target.setDate(target.getDate() + offset);
        if (dateEl) {
            dateEl.value = target.toISOString().slice(0, 10);
        }
        if (timeEl && !timeEl.value) {
            timeEl.value = '09:00';
        }
    });

    $('point-sheet-close')?.addEventListener('click', () => show('point-sheet', false));
    $('point-adjust-map')?.addEventListener('click', () => beginExistingPositionAdjust());
    $('adjust-confirm')?.addEventListener('click', () => {
        confirmAdjustPosition().catch((error) => toast(error.message || 'Erro ao confirmar.', 'error'));
    });
    $('adjust-cancel')?.addEventListener('click', () => cancelAdjustPosition());
    $('reward-close')?.addEventListener('click', hideReward);
    $('sale-success-close')?.addEventListener('click', hideSaleSuccess);
    $('sale-success-copy')?.addEventListener('click', () => copyHandoffMessage(currentHandoff, mobileApi));
    $('sale-success-view')?.addEventListener('click', () => showHandoffText(currentHandoff));
    $('sale-success-whatsapp')?.addEventListener('click', () => {
        openOfficeWhatsApp(currentHandoff, mobileApi).catch((error) => {
            toast(error.message || 'Não foi possível abrir o WhatsApp. Você pode copiar a mensagem.', 'error');
        });
    });
    $('handoff-copy')?.addEventListener('click', () => copyHandoffMessage(currentHandoff, mobileApi));
    $('handoff-whatsapp')?.addEventListener('click', () => {
        openOfficeWhatsApp(currentHandoff, mobileApi).catch((error) => {
            toast(error.message || 'Não foi possível abrir o WhatsApp. Você pode copiar a mensagem.', 'error');
        });
    });
    $('handoff-close')?.addEventListener('click', hideHandoffText);
    $('point-handoff-copy')?.addEventListener('click', () => copyHandoffMessage(currentHandoff, mobileApi));
    $('point-handoff-view')?.addEventListener('click', () => showHandoffText(currentHandoff));
    $('point-handoff-whatsapp')?.addEventListener('click', () => {
        openOfficeWhatsApp(currentHandoff, mobileApi).catch((error) => {
            toast(error.message || 'Não foi possível abrir o WhatsApp. Você pode copiar a mensagem.', 'error');
        });
    });

    $('profile-btn')?.addEventListener('click', () => show('account-sheet', true));
    $('account-close')?.addEventListener('click', () => show('account-sheet', false));
    $('menu-products')?.addEventListener('click', () => {
        PresentationScreen.open().catch((error) => toast(error.message, 'error'));
    });
    $('map-present-products')?.addEventListener('click', () => {
        PresentationScreen.open().catch((error) => toast(error.message, 'error'));
    });
    $('menu-account')?.addEventListener('click', () => show('account-sheet', true));
    $('menu-about')?.addEventListener('click', () => show('about-sheet', true));
    $('about-close')?.addEventListener('click', () => show('about-sheet', false));
    $('presentation-close')?.addEventListener('click', () => PresentationScreen.close());

    $('clients-q')?.addEventListener('input', (event) => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            loadClients(event.target.value).catch((error) => toast(error.message, 'error'));
        }, 400);
    });

    $('sale-cart-add')?.addEventListener('click', () => {
        setSalePrefix('');
        addCartLine(true);
    });
    $('create-sale-cart-add')?.addEventListener('click', () => {
        setSalePrefix('create-');
        addCartLine(true);
    });
    $('create-adjust-map')?.addEventListener('click', () => {
        beginCreatePositionPick();
    });
    $('create-outcome-list')?.addEventListener('click', (event) => {
        const button = event.target.closest?.('.outcome-chip');
        if (button?.dataset.status) {
            selectCreateOutcome(button.dataset.status);
            setCreateError('');
        }
    });
    $('create-return-shortcuts')?.addEventListener('click', (event) => {
        const button = event.target.closest?.('.return-shortcut');
        if (!button) {
            return;
        }
        const dateEl = $('create-follow-up-date');
        const timeEl = $('create-follow-up-time');
        const days = button.dataset.days;
        document.querySelectorAll('#create-return-shortcuts .return-shortcut').forEach((row) => {
            row.dataset.active = row === button ? '1' : '0';
        });
        if (days === 'pick') {
            dateEl?.focus();
            return;
        }
        const offset = Number(days || 1);
        const target = new Date();
        target.setDate(target.getDate() + offset);
        if (dateEl) {
            dateEl.value = target.toISOString().slice(0, 10);
        }
        if (timeEl && !timeEl.value) {
            timeEl.value = '09:00';
        }
    });

    document.addEventListener('click', (event) => {
        const followBtn = event.target.closest?.('[data-complete-follow-up]');
        if (followBtn?.dataset.completeFollowUp) {
            const propertyId = followBtn.dataset.propertyId;
            if (propertyId) {
                setPane('map');
                openVisitSheet(propertyId, {
                    followUpId: followBtn.dataset.completeFollowUp,
                    subtitle: 'Como foi a abordagem?',
                });
            }

            return;
        }

        const openBtn = event.target.closest?.('[data-open-point]');
        if (openBtn?.dataset.openPoint) {
            openPoint({ id: openBtn.dataset.openPoint }).catch((error) => toast(error.message, 'error'));

            return;
        }

        const mapBtn = event.target.closest?.('[data-map-point]');
        if (mapBtn?.dataset.lat && mapBtn?.dataset.lng) {
            focusMapPoint(mapBtn.dataset.lat, mapBtn.dataset.lng);
        }
    });

    window.addEventListener('online', () => setNet(true));
    window.addEventListener('offline', () => setNet(false));
    setNet(navigator.onLine !== false);
}

async function startup() {
    const loginForm = $('expandor-login-form');
    if (!loginForm) {
        return;
    }

    loginForm.addEventListener('submit', onSubmit);
    $('login-forgot')?.addEventListener('click', (event) => {
        event.preventDefault();
        MobileAuthService.openForgotPassword();
    });
    $('logout-button')?.addEventListener('click', onLogout);
    $('account-logout')?.addEventListener('click', onLogout);
    window.addEventListener('expandor:auth-cleared', (event) => {
        showLogin(event.detail?.message || '');
    });
    bindApp();
    paintIcons();

    try {
        const user = await MobileAuthService.getCurrentUser();
        if (user) {
            await enterApp(user);

            return;
        }
    } catch (error) {
        if (error.code === 'network_offline') {
            setBanner('Sem conexão. Conecte-se para entrar. O modo offline ainda não está disponível.', 'error');
        }
    }

    showLogin(MobileAuthService.lastAuthMessage);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startup);
} else {
    startup();
}
