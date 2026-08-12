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
} from './sale-cart.js';

const GPS_FAIL = 'Não foi possível acessar sua localização.';
const GPS_UNAVAILABLE_HINT = 'Localização indisponível. Use Meu Local quando quiser.';
const GPS_PERMISSION_HINT = 'Permita localização para centralizar o mapa, ou use Meu Local.';
const INITIAL_GPS_TIMEOUT_MS = 8000;
const OFFLINE_MUTATION = 'Sem conexão. Esta ação ainda não pode ser concluída offline.';
let bootstrap = null;
let searchTimer = null;
let layersOpen = false;
let initialMapGpsDone = false;
let catalogProducts = [];

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
    const bbox = MapAdapter.boundsQuery();
    const payload = await mobileApi.markers(bbox);
    const markers = payload.data?.markers || payload.data || [];
    const selected = MapAdapter.selectedPropertyId;
    MapAdapter.renderMarkers(Array.isArray(markers) ? markers : [], openPoint);
    if (selected != null) {
        MapAdapter.selectProperty(selected);
    }
}

async function openPoint(item) {
    const id = item?.property_id || item?.id;
    if (!id) {
        return;
    }

    const payload = await mobileApi.point(id);
    const point = payload.data || {};
    $('point-title').textContent = point.resident_name || point.name || `Imóvel #${id}`;
    $('point-meta').textContent = [
        point.status_label || propertyStatusLabel(point.status),
        point.address,
        point.resident_phone,
    ].filter(Boolean).join(' · ');
    $('point-id').value = id;
    MapAdapter.selectProperty(id);
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
}

function openCreateSheet(lat, lng, label) {
    if (lat != null && lng != null) {
        $('point-lat').value = String(lat);
        $('point-lng').value = String(lng);
    }
    const chip = $('create-location-chip');
    if (chip) {
        if (label) {
            chip.textContent = label;
            chip.hidden = false;
        } else if (lat != null && lng != null) {
            chip.textContent = `Local: ${Number(lat).toFixed(5)}, ${Number(lng).toFixed(5)}`;
            chip.hidden = false;
        } else {
            chip.hidden = true;
        }
    }
    show('create-sheet', true);
    paintIcons($('create-sheet'));
}

function paintCampaignContext() {
    const ctx = bootstrap?.data?.campaign_context;
    const label = $('visit-campaign-label');
    const select = $('visit-campaign');
    const warning = $('visit-campaign-warning');

    if (!ctx) {
        return;
    }

    if (!ctx.has_campaign) {
        if (warning) {
            warning.hidden = false;
            warning.textContent = ctx.no_campaign_message
                || 'Você não possui campanha ativa. Solicite ao gestor.';
        }
        if (label) {
            label.hidden = true;
        }
        if (select) {
            select.hidden = true;
            select.required = false;
        }

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

        return;
    }

    if (label) {
        label.hidden = true;
    }
    if (select) {
        select.hidden = false;
        select.required = true;
        fillSelect('visit-campaign', ctx.campaigns || [], 'name');
    }
}

function resolveCampaignIdForSubmit() {
    const ctx = bootstrap?.data?.campaign_context;
    if (ctx?.active_campaign_id && !ctx?.requires_selection) {
        return Number(ctx.active_campaign_id);
    }

    return Number($('visit-campaign')?.value || $('point-campaign')?.value || 0);
}

async function resolveVisitCoordinates(propertyId) {
    const marker = MapAdapter.markerRegistry?.get(Number(propertyId))?.data;
    if (marker?.latitude != null && marker?.longitude != null) {
        return {
            latitude: Number(marker.latitude),
            longitude: Number(marker.longitude),
        };
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
    const el = $('visit-outcome-list');
    if (!el) {
        return;
    }

    el.innerHTML = VISIT_OUTCOMES.map((outcome) => `
        <button type="button" class="outcome-chip" data-status="${escapeHtml(outcome.value)}"
            style="--status-color:${escapeHtml(outcome.color)}" aria-pressed="false">
            <span class="outcome-chip__swatch" aria-hidden="true">${escapeHtml(outcome.mark)}</span>
            <span class="outcome-chip__label">${escapeHtml(outcome.label)}</span>
        </button>
    `).join('');
}

function selectVisitOutcome(status) {
    const value = status || '';
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
    const saleBlock = $('visit-sale-block');
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
        resetSaleForm();
        initSaleForm(catalogProducts, bootstrap?.data?.sale_fields || {});
    }

    if (isReturn) {
        ensureReturnDefaults();
    } else if (!isSale) {
        const dateEl = $('visit-follow-up-date');
        const timeEl = $('visit-follow-up-time');
        if (dateEl) {
            dateEl.value = '';
        }
        if (timeEl) {
            timeEl.value = '';
        }
        document.querySelectorAll('.return-shortcut').forEach((btn) => {
            btn.dataset.active = '0';
        });
    }
}

function ensureReturnDefaults() {
    const dateEl = $('visit-follow-up-date');
    const timeEl = $('visit-follow-up-time');
    if (!dateEl || dateEl.value) {
        return;
    }

    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    dateEl.value = tomorrow.toISOString().slice(0, 10);
    if (timeEl && !timeEl.value) {
        timeEl.value = '09:00';
    }
    document.querySelectorAll('.return-shortcut[data-days="1"]').forEach((btn) => {
        btn.dataset.active = '1';
    });
}

function combineFollowUpAt() {
    const date = $('visit-follow-up-date')?.value;
    const time = $('visit-follow-up-time')?.value || '09:00';
    if (!date) {
        return null;
    }

    return `${date} ${time}:00`;
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

    $('visit-property-id').value = id;
    $('point-id').value = id;
    $('visit-follow-up-id').value = options.followUpId ? String(options.followUpId) : '';
    selectVisitOutcome('');
    resetSaleForm();
    const notes = $('visit-notes');
    if (notes) {
        notes.value = '';
    }
    setVisitError('');
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
        await loadMarkers();
    } catch (error) {
        if (error?.code === 'permission_denied') {
            toast(GPS_PERMISSION_HINT, 'status');

            return;
        }

        toast(GPS_UNAVAILABLE_HINT, 'status');
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
        toast('Selecione um local no mapa ou use Meu Local.', 'error');

        return;
    }

    const body = {
        city_id: Number($('point-city')?.value),
        sector_id: $('point-sector')?.value ? Number($('point-sector').value) : null,
        street: $('point-street')?.value,
        number: $('point-number')?.value,
        latitude: lat,
        longitude: lng,
        contact_name: $('point-contact')?.value,
        contact_phone: $('point-phone')?.value,
    };

    try {
        const created = await mobileApi.createPoint(body);
        toast('Imóvel salvo.', 'status');
        show('create-sheet', false);
        const pointId = created.data?.property_id || created.data?.id;
        await loadMarkers();
        if (pointId) {
            MapAdapter.selectProperty(pointId);
            openVisitSheet(pointId, {
                subtitle: [body.street, body.number].filter(Boolean).join(', ') || 'Como foi a abordagem?',
            });
        }
    } catch (error) {
        toast(error.message || OFFLINE_MUTATION, 'error');
    }
}

async function submitVisit() {
    if (navigator.onLine === false) {
        toast(OFFLINE_MUTATION, 'error');

        return;
    }

    const id = $('visit-property-id')?.value || $('point-id')?.value;
    const status = $('visit-status')?.value;
    const followUpId = $('visit-follow-up-id')?.value;
    const campaignId = resolveCampaignIdForSubmit();
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
    if (status === 'return_later' && !combineFollowUpAt()) {
        setVisitError('Informe a data do retorno.');

        return;
    }
    if (status === 'installation_requested') {
        const saleError = validateSaleForm();
        if (saleError) {
            setVisitError(saleError);

            return;
        }
    }

    const coords = await resolveVisitCoordinates(id);
    const body = {
        status,
        campaign_id: campaignId,
        notes: $('visit-notes')?.value || '',
        ...coords,
    };

    if (status === 'return_later') {
        body.follow_up_at = combineFollowUpAt();
    }
    if (status === 'installation_requested') {
        Object.assign(body, collectSalePayload());
    }

    if (submitBtn) {
        submitBtn.disabled = true;
    }

    try {
        const payload = followUpId
            ? await mobileApi.completeFollowUp(followUpId, body)
            : (status === 'installation_requested'
                ? await mobileApi.sale(id, body)
                : await mobileApi.visit(id, body));

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

        if (payload.data?.commission_awarded) {
            showReward(payload.data.commission_awarded);
        }

        if (followUpId || status === 'return_later') {
            loadAgenda().catch(() => {});
        }

        loadMarkers().catch(() => {});
    } catch (error) {
        setVisitError(error.message || OFFLINE_MUTATION);
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
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
        fillSelect('point-sector', territory.data?.sectors || [], 'name');
        fillSelect('visit-campaign', campaigns, 'name');
        fillSelect('point-campaign', campaigns, 'name');
        initSaleForm(catalogProducts, bootstrap?.data?.sale_fields || {});
        paintCampaignContext();
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
            MapAdapter.onMapClick = (lat, lng) => {
                openCreateSheet(lat, lng);
            };
            syncLayerButtons();
        }
        await hydrateCatalog();
        renderVisitOutcomes();
        await loadMarkers();
        void tryInitialMapGps();
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
        try {
            const position = await LocationService.getCurrentPosition();
            openCreateSheet(position.latitude, position.longitude);
        } catch {
            openCreateSheet(null, null);
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
    $('reward-close')?.addEventListener('click', hideReward);

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

    $('sale-cart-add')?.addEventListener('click', () => addCartLine(true));

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
