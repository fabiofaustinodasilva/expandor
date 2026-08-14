/**
 * mobileApi contract:
 * - Uses apiFetch (raw Response).
 * - Parses JSON and returns the FULL envelope: { success, message, data, meta? }.
 * - Does NOT return only `data` — callers use `payload.data`.
 */
import { apiFetch } from './api-fetch.js';
import { MobileAuthService } from './mobile-auth-service.js';

async function json(path, options = {}) {
    if (typeof navigator !== 'undefined' && navigator.onLine === false && options.method && options.method !== 'GET') {
        const error = new Error('Sem conexão. Esta ação ainda não pode ser concluída offline.');
        error.code = 'network_offline';
        throw error;
    }

    const response = await apiFetch(path, options);
    if (response.status === 204) {
        return {};
    }

    let payload = {};
    try {
        const text = await response.text();
        payload = text && text.trim() ? JSON.parse(text) : {};
    } catch {
        payload = {};
    }

    if (response.status === 401) {
        await MobileAuthService.handleUnauthorized(payload);
    }

    if (! response.ok) {
        const error = new Error(payload.message || 'Não foi possível concluir.');
        error.code = payload.code || 'error';
        error.status = response.status;
        error.payload = payload;
        throw error;
    }

    return payload;
}

function query(params) {
    const search = new URLSearchParams();
    Object.entries(params || {}).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            search.set(key, String(value));
        }
    });
    const suffix = search.toString();

    return suffix ? `?${suffix}` : '';
}

export const mobileApi = {
    bootstrap: () => json('/api/mobile/v1/bootstrap'),
    mapConfig: () => json('/api/mobile/v1/map/config'),
    markers: (params) => json(`/api/mobile/v1/markers${query(params)}`),
    points: (params) => json(`/api/mobile/v1/points${query(params)}`),
    point: (id) => json(`/api/mobile/v1/points/${id}`),
    adjustPointLocation: (id, body) => json(`/api/mobile/v1/points/${id}/location`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    }),
    createPoint: (body) => json('/api/mobile/v1/points', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    }),
    firstApproach: (body) => json('/api/mobile/v1/first-approach', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    }),
    visit: (id, body) => json(`/api/mobile/v1/points/${id}/visits`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    }),
    sale: (id, body) => json(`/api/mobile/v1/points/${id}/sales`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    }),
    agenda: (params) => json(`/api/mobile/v1/agenda${query(params)}`),
    completeFollowUp: (id, body) => json(`/api/mobile/v1/follow-ups/${id}/complete`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    }),
    products: (params) => json(`/api/mobile/v1/products${query(params)}`),
    commissions: (params) => json(`/api/mobile/v1/commissions${query(params)}`),
    results: () => json('/api/mobile/v1/results'),
    territory: (params) => json(`/api/mobile/v1/territory${query(params)}`),
    saleHandoff: (id) => json(`/api/mobile/v1/sales/${id}/handoff`),
    handoffCopied: (id) => json(`/api/mobile/v1/sales/${id}/handoff/copied`, { method: 'POST' }),
    handoffOpened: (id) => json(`/api/mobile/v1/sales/${id}/handoff/opened`, { method: 'POST' }),
};

if (typeof window !== 'undefined') {
    window.ExpandorMobileApi = mobileApi;
}
