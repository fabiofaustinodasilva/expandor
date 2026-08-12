import { apiFetch } from './api-fetch.js';
import { MobileAuthService } from './mobile-auth-service.js';

async function json(path, options = {}) {
    if (typeof navigator !== 'undefined' && navigator.onLine === false && options.method && options.method !== 'GET') {
        const error = new Error('Sem conexão. Esta ação ainda não pode ser concluída offline.');
        error.code = 'network_offline';
        throw error;
    }

    const response = await apiFetch(path, options);
    let payload = {};
    try {
        payload = await response.json();
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
    createPoint: (body) => json('/api/mobile/v1/points', {
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
};

if (typeof window !== 'undefined') {
    window.ExpandorMobileApi = mobileApi;
}
