import { apiFetch } from './api-fetch.js';
import { SecureAuthStorage } from './secure-auth-storage.js';

const SESSION_REPLACED_MESSAGE =
    'Sua conta foi acessada em outro dispositivo. Por segurança, esta sessão foi encerrada.';

async function readJson(response) {
    try {
        const text = await response.text();
        if (! text || ! String(text).trim()) {
            return { __parse_error: 'empty_body' };
        }

        return JSON.parse(text);
    } catch {
        return { __parse_error: 'invalid_json' };
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

function isPlainObject(value) {
    return value !== null && typeof value === 'object' && ! Array.isArray(value);
}

/**
 * Official mobile envelope:
 * { success, message, data: { token, token_type, user, company, permissions, session } }
 * apiFetch does not unwrap — always read from payload.data.
 */
export function extractLoginData(payload) {
    if (! isPlainObject(payload) || payload.__parse_error) {
        return { ok: false, reason: 'parse_error' };
    }

    if (! isPlainObject(payload.data)) {
        return { ok: false, reason: 'missing_data' };
    }

    const data = payload.data;
    const token = typeof data.token === 'string' ? data.token.trim() : '';
    if (! token) {
        return { ok: false, reason: 'missing_token' };
    }

    if (! isPlainObject(data.user)) {
        return { ok: false, reason: 'missing_user' };
    }

    if (! isPlainObject(data.session)) {
        return { ok: false, reason: 'missing_session' };
    }

    return { ok: true, data, token };
}

function loginInvalidError(reason) {
    const error = new Error('Resposta de login inválida.');
    error.code = 'invalid_login_response';
    error.reason = reason;
    return error;
}

function logLoginDev(reason) {
    if (typeof window === 'undefined' || window.EXPANDOR_DEV !== true) {
        return;
    }

    try {
        // Never log token, password, or Authorization.
        console.warn('[ExpandorAuth] login envelope rejected', { reason });
    } catch {
        /* ignore */
    }
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
                app_version: window.EXPANDOR_APP_VERSION || '8.2.34',
            }),
        });

        const payload = await readJson(response);

        if (! response.ok) {
            const error = new Error(payload.message || 'Não foi possível entrar.');
            error.code = payload.code || (response.status === 401 ? 'invalid_credentials' : 'error');
            error.status = response.status;
            error.payload = {
                success: payload.success,
                message: payload.message,
                code: payload.code,
                errors: payload.errors,
            };
            throw error;
        }

        const extracted = extractLoginData(payload);
        if (! extracted.ok) {
            logLoginDev(extracted.reason);
            throw loginInvalidError(extracted.reason);
        }

        await SecureAuthStorage.setToken(extracted.token);
        this.currentUser = extracted.data;
        this.lastAuthMessage = null;

        return extracted.data;
    },

    async getCurrentUser() {
        const token = await SecureAuthStorage.getToken();
        if (! token) {
            this.currentUser = null;

            return null;
        }

        const response = await apiFetch('/api/mobile/v1/me');
        const payload = await readJson(response);

        if (response.status === 401) {
            await this.handleUnauthorized(payload);

            return null;
        }

        if (! response.ok) {
            const error = new Error(payload.message || 'Não foi possível validar a sessão.');
            error.code = payload.code || 'error';
            error.status = response.status;
            throw error;
        }

        if (! isPlainObject(payload.data)) {
            await this.clearLocalAuth();
            throw loginInvalidError('missing_data');
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
    window.ExpandorExtractLoginData = extractLoginData;
}
