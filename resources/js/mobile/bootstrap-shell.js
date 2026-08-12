import { MobileAuthService } from './mobile-auth-service.js';
import { mobileApi } from './mobile-api.js';
import { LocationService } from './location-service.js';
import { MapAdapter } from './map-adapter.js';

const GPS_FAIL = 'Não foi possível acessar sua localização.';
const OFFLINE_MUTATION = 'Sem conexão. Esta ação ainda não pode ser concluída offline.';
let bootstrap = null;
let searchTimer = null;

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
    el.textContent = online ? '' : 'Offline';
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
        requestAnimationFrame(() => window.L && MapAdapter.map?.invalidateSize?.());
    }
}

function paintUser(data) {
    const name = data?.user?.name || data?.name || '';
    const email = data?.user?.email || data?.email || '';
    const company = data?.company?.name || data?.user?.company?.name || '';
    const hello = $('signed-hello');
    if (hello) {
        hello.textContent = name ? `Olá, ${name}` : 'Sessão ativa';
    }
    const meta = $('signed-meta');
    if (meta) {
        meta.textContent = [company, email].filter(Boolean).join(' · ');
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
        amount.textContent = `R$ ${Number(payload.amount || 0).toFixed(2)}`;
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

function renderList(targetId, items, emptyText, row) {
    const el = $(targetId);
    if (!el) {
        return;
    }
    if (!items?.length) {
        el.innerHTML = `<p class="muted">${emptyText}</p>`;

        return;
    }
    el.innerHTML = items.map(row).join('');
}

async function loadMarkers() {
    const bbox = MapAdapter.boundsQuery();
    const payload = await mobileApi.markers(bbox);
    const markers = payload.data?.markers || payload.data || [];
    MapAdapter.renderMarkers(Array.isArray(markers) ? markers : [], openPoint);
}

async function openPoint(item) {
    const id = item?.property_id || item?.id;
    if (!id) {
        return;
    }

    const payload = await mobileApi.point(id);
    const point = payload.data || {};
    $('point-title').textContent = point.resident_name || point.name || `Ponto #${id}`;
    $('point-meta').textContent = [
        point.status_label || point.status,
        point.address,
        point.resident_phone,
    ].filter(Boolean).join(' · ');
    $('point-id').value = id;
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
}

async function loadAgenda() {
    const payload = await mobileApi.agenda({ scope: 'today' });
    renderList('agenda-list', payload.data || [], 'Nenhum retorno para hoje.', (item) => `
        <button type="button" class="row" data-open-point="${item.property_id || ''}">
            <strong>${item.address || 'Retorno'}</strong>
            <span>${item.scheduled_label || item.scheduled_at || ''}</span>
        </button>
    `);
}

async function loadClients(q = '') {
    const payload = await mobileApi.points({ q, per_page: 24 });
    renderList('clients-list', payload.data || [], 'Nenhum cliente encontrado.', (item) => `
        <button type="button" class="row" data-open-point="${item.property_id || item.id}">
            <strong>${item.resident_name || item.name || `Ponto #${item.id}`}</strong>
            <span>${item.status_label || item.status || ''} · ${item.address || ''}</span>
        </button>
    `);
}

async function loadResults() {
    const payload = await mobileApi.results();
    const data = payload.data || {};
    const el = $('results-list');
    if (el) {
        el.innerHTML = `
            <p>Visitas hoje: <strong>${data.visits_today ?? '—'}</strong></p>
            <p>Retornos pendentes: <strong>${data.pending_follow_ups ?? '—'}</strong></p>
            <p>Campanhas ativas: <strong>${data.active_campaigns ?? '—'}</strong></p>
        `;
    }
}

async function loadCommissions() {
    const payload = await mobileApi.commissions();
    const items = payload.data?.items || [];
    renderList('commissions-list', items, 'Nenhuma comissão no período.', (item) => `
        <div class="row">
            <strong>${item.product || 'Comissão'}</strong>
            <span>R$ ${Number(item.commission_amount || 0).toFixed(2)} · ${item.status || ''}</span>
        </div>
    `);
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

async function onCreatePoint(event) {
    event.preventDefault();
    if (navigator.onLine === false) {
        toast(OFFLINE_MUTATION, 'error');

        return;
    }

    const body = {
        city_id: Number($('point-city')?.value),
        sector_id: $('point-sector')?.value ? Number($('point-sector').value) : null,
        street: $('point-street')?.value,
        number: $('point-number')?.value,
        latitude: Number($('point-lat')?.value),
        longitude: Number($('point-lng')?.value),
        status: $('point-status')?.value || 'new',
        contact_name: $('point-contact')?.value,
        contact_phone: $('point-phone')?.value,
        notes: $('point-notes')?.value,
    };

    try {
        const created = await mobileApi.createPoint(body);
        toast('Ponto salvo.', 'status');
        show('create-sheet', false);
        MapAdapter.renderMarkers([created.data].filter(Boolean), openPoint);
        await loadMarkers();
    } catch (error) {
        toast(error.message || OFFLINE_MUTATION, 'error');
    }
}

async function onVisit(status) {
    if (navigator.onLine === false) {
        toast(OFFLINE_MUTATION, 'error');

        return;
    }

    const id = $('point-id')?.value;
    const campaignId = Number($('point-campaign')?.value || bootstrap?.data?.active_campaign_id);
    if (!id) {
        return;
    }

    const body = {
        status,
        campaign_id: campaignId || Number($('visit-campaign')?.value),
        notes: $('visit-notes')?.value,
        follow_up_at: $('visit-followup')?.value,
        customer_name: $('sale-name')?.value,
        customer_phone: $('sale-phone')?.value,
        items: $('sale-product')?.value
            ? [{ product_id: Number($('sale-product').value), quantity: 1 }]
            : undefined,
    };

    try {
        const payload = status === 'installation_requested'
            ? await mobileApi.sale(id, body)
            : await mobileApi.visit(id, body);
        toast('Visita registrada.', 'status');
        show('point-sheet', false);
        if (payload.data?.commission_awarded) {
            showReward(payload.data.commission_awarded);
        }
        await loadMarkers();
    } catch (error) {
        toast(error.message || OFFLINE_MUTATION, 'error');
    }
}

async function hydrateCatalog() {
    try {
        const [territory, products, campaigns] = await Promise.all([
            mobileApi.territory(),
            mobileApi.products(),
            fetchCampaigns(),
        ]);
        fillSelect('point-city', territory.data?.cities || [], 'name');
        fillSelect('point-sector', territory.data?.sectors || [], 'name');
        fillSelect('sale-product', products.data || [], 'name');
        fillSelect('visit-campaign', campaigns, 'name');
        fillSelect('point-campaign', campaigns, 'name');
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
        `<option value="${item.id}">${item[labelKey] || item.id}</option>`
    ).join('');
    if (current) {
        el.value = current;
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
        if (!MapAdapter.map) {
            MapAdapter.init('seller-map', { zoom: 13 });
        }
        await hydrateCatalog();
        await loadMarkers();
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
    $('create-point-open')?.addEventListener('click', () => show('create-sheet', true));
    $('create-point-cancel')?.addEventListener('click', () => show('create-sheet', false));
    $('create-point-form')?.addEventListener('submit', onCreatePoint);
    $('visit-interested')?.addEventListener('click', () => onVisit('interested'));
    $('visit-return')?.addEventListener('click', () => onVisit('return_later'));
    $('visit-no-interest')?.addEventListener('click', () => onVisit('no_interest'));
    $('visit-sale')?.addEventListener('click', () => onVisit('installation_requested'));
    $('point-sheet-close')?.addEventListener('click', () => show('point-sheet', false));
    $('reward-close')?.addEventListener('click', hideReward);
    $('clients-q')?.addEventListener('input', (event) => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            loadClients(event.target.value).catch((error) => toast(error.message, 'error'));
        }, 400);
    });
    document.addEventListener('click', (event) => {
        const btn = event.target.closest?.('[data-open-point]');
        if (btn?.dataset.openPoint) {
            openPoint({ id: btn.dataset.openPoint }).catch((error) => toast(error.message, 'error'));
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
    window.addEventListener('expandor:auth-cleared', (event) => {
        showLogin(event.detail?.message || '');
    });
    bindApp();

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
