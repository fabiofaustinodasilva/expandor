import { SecureAuthStorage } from './secure-auth-storage.js';

function apiBase() {
    return String(window.EXPANDOR_API_BASE || '').replace(/\/$/, '');
}

function appVersion() {
    return String(window.EXPANDOR_APP_VERSION || '8.2.33');
}

function resolveUrl(path) {
    if (/^https?:\/\//i.test(path)) {
        return path;
    }

    const base = apiBase();
    const suffix = path.startsWith('/') ? path : `/${path}`;

    return `${base}${suffix}`;
}

export async function apiFetch(path, options = {}) {
    const headers = new Headers(options.headers || {});
    headers.set('Accept', 'application/json');
    headers.set('X-App-Version', appVersion());
    headers.delete('X-CSRF-TOKEN');
    headers.delete('X-XSRF-TOKEN');
    headers.delete('X-Requested-With');

    const deviceId = await SecureAuthStorage.ensureDeviceId();
    headers.set('X-Device-Id', deviceId);

    const token = await SecureAuthStorage.getToken();
    if (token) {
        headers.set('Authorization', `Bearer ${token}`);
    }

    const init = {
        ...options,
        headers,
        credentials: 'omit',
    };

    let response;
    try {
        response = await fetch(resolveUrl(path), init);
    } catch {
        const error = new Error('Sem conexão. Verifique a internet e tente novamente.');
        error.code = 'network_offline';
        throw error;
    }

    return response;
}

if (typeof window !== 'undefined') {
    window.ExpandorApiFetch = apiFetch;
}
