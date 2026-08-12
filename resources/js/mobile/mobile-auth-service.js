import { apiFetch } from './api-fetch.js';
import { SecureAuthStorage } from './secure-auth-storage.js';

const SESSION_REPLACED_MESSAGE =
    'Sua conta foi acessada em outro dispositivo. Por segurança, esta sessão foi encerrada.';

async function readJson(response) {
    try {
        return await response.json();
    } catch {
        return {};
    }
}

function platformHint() {
    const cap = window.Capacitor?.getPlatform?.();
    if (cap) {
        return cap;
    }

    const ua = navigator.userAgent || '';
    if (/android/i.test(ua)) {
        return 'android';
    }
    if (/iphone|ipad|ipod/i.test(ua)) {
        return 'ios';
    }

    return 'web';
}

function deviceName() {
    if (/android/i.test(navigator.userAgent || '')) {
        return 'Android';
    }
    if (/iphone|ipad|ipod/i.test(navigator.userAgent || '')) {
        return 'iPhone';
    }

    return 'Web';
}

export const MobileAuthService = {
    currentUser: null,
    lastAuthMessage: null,
    handlingUnauthorized: false,

    async login(email, password) {
        const deviceId = await SecureAuthStorage.ensureDeviceId();
        const response = await apiFetch('/api/mobile/v1/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email,
                password,
                device_id: deviceId,
                device_name: deviceName(),
                platform: platformHint(),
                app_version: window.EXPANDOR_APP_VERSION || '8.2.33',
            }),
        });

        const payload = await readJson(response);

        if (!response.ok) {
            const error = new Error(payload.message || 'Não foi possível entrar.');
            error.code = payload.code || (response.status === 401 ? 'invalid_credentials' : 'error');
            error.status = response.status;
            error.payload = payload;
            throw error;
        }

        const token = payload?.data?.token;
        if (!token) {
            throw new Error('Resposta de login inválida.');
        }

        await SecureAuthStorage.setToken(token);
        this.currentUser = payload.data;
        this.lastAuthMessage = null;

        return payload.data;
    },

    async getCurrentUser() {
        const token = await SecureAuthStorage.getToken();
        if (!token) {
            this.currentUser = null;

            return null;
        }

        const response = await apiFetch('/api/mobile/v1/me');
        const payload = await readJson(response);

        if (response.status === 401) {
            await this.handleUnauthorized(payload);

            return null;
        }

        if (!response.ok) {
            const error = new Error(payload.message || 'Não foi possível validar a sessão.');
            error.code = payload.code || 'error';
            error.status = response.status;
            throw error;
        }

        this.currentUser = payload.data;
        this.lastAuthMessage = null;

        return payload.data;
    },

    async logout() {
        try {
            await apiFetch('/api/mobile/v1/logout', { method: 'POST' });
        } catch {
            /* still clear locally */
        }

        await this.clearLocalAuth();
    },

    async clearLocalAuth() {
        await SecureAuthStorage.clearAuth();
        this.currentUser = null;
    },

    async handleUnauthorized(payload = {}) {
        if (this.handlingUnauthorized) {
            return;
        }

        this.handlingUnauthorized = true;
        try {
            const code = payload.code || 'unauthenticated';
            this.lastAuthMessage = code === 'session_replaced'
                ? (payload.message || SESSION_REPLACED_MESSAGE)
                : null;
            await this.clearLocalAuth();
            window.dispatchEvent(new CustomEvent('expandor:auth-cleared', {
                detail: { code, message: this.lastAuthMessage },
            }));
        } finally {
            this.handlingUnauthorized = false;
        }
    },

    forgotPasswordUrl() {
        const origin = String(window.EXPANDOR_WEB_ORIGIN || window.EXPANDOR_API_BASE || '').replace(/\/$/, '');

        return `${origin}/esqueci-minha-senha`;
    },

    openForgotPassword() {
        const url = this.forgotPasswordUrl();
        const browser = window.Capacitor?.Plugins?.Browser;
        if (browser?.open) {
            return browser.open({ url });
        }

        window.open(url, '_blank', 'noopener,noreferrer');
    },
};

if (typeof window !== 'undefined') {
    window.ExpandorMobileAuth = MobileAuthService;
}
