import { Preferences } from '@capacitor/preferences';

/**
 * SecureAuthStorage — token and install UUID stay out of browser web storage.
 * Native: Capacitor Preferences (Android SharedPreferences / iOS UserDefaults).
 * No Keystore plugin in this APK — token is not in logs or localStorage.
 */

const TOKEN_KEY = 'expandor.auth.token';
const DEVICE_KEY = 'expandor.auth.device_id';
const SESSION_VERSION_KEY = 'expandor.auth.session_version';
const memory = new Map();

function randomUuid() {
    if (globalThis.crypto?.randomUUID) {
        return globalThis.crypto.randomUUID();
    }

    const bytes = new Uint8Array(16);
    (globalThis.crypto || { getRandomValues: (b) => b }).getRandomValues?.(bytes);
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = [...bytes].map((b) => b.toString(16).padStart(2, '0')).join('');

    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

function nativePlugin(name) {
    if (name === 'Preferences') {
        return globalThis.Capacitor?.Plugins?.Preferences ?? Preferences;
    }

    return globalThis.Capacitor?.Plugins?.[name] ?? null;
}

async function read(key) {
    if (memory.has(key)) {
        return memory.get(key);
    }

    const secure = nativePlugin('SecureStorage');
    if (secure?.get) {
        try {
            const result = await secure.get({ key });
            const value = result?.value ?? null;
            if (value) {
                memory.set(key, value);
            }

            return value;
        } catch {
            return null;
        }
    }

    const preferences = nativePlugin('Preferences');
    if (preferences?.get) {
        try {
            const result = await preferences.get({ key });
            const value = result?.value ?? null;
            if (value) {
                memory.set(key, value);
            }

            return value;
        } catch {
            return null;
        }
    }

    return null;
}

async function write(key, value) {
    memory.set(key, value);

    const secure = nativePlugin('SecureStorage');
    if (secure?.set) {
        await secure.set({ key, value });

        return;
    }

    const preferences = nativePlugin('Preferences');
    if (preferences?.set) {
        await preferences.set({ key, value });
    }
}

async function forget(key) {
    memory.delete(key);

    const secure = nativePlugin('SecureStorage');
    if (secure?.remove) {
        try {
            await secure.remove({ key });
        } catch {
            /* ignore */
        }
    }

    const preferences = nativePlugin('Preferences');
    if (preferences?.remove) {
        try {
            await preferences.remove({ key });
        } catch {
            /* ignore */
        }
    }
}

export const SecureAuthStorage = {
    async getToken() {
        return read(TOKEN_KEY);
    },

    async setToken(token) {
        if (!token) {
            await this.removeToken();

            return;
        }

        await write(TOKEN_KEY, String(token));
    },

    async removeToken() {
        await forget(TOKEN_KEY);
    },

    async getDeviceId() {
        return read(DEVICE_KEY);
    },

    async ensureDeviceId() {
        const existing = await this.getDeviceId();
        if (existing) {
            return existing;
        }

        const deviceId = randomUuid();
        await write(DEVICE_KEY, deviceId);

        return deviceId;
    },

    async clearAuth() {
        await this.removeToken();
        await forget(SESSION_VERSION_KEY);
    },

    async getSessionVersion() {
        return read(SESSION_VERSION_KEY);
    },

    async setSessionVersion(version) {
        if (version == null || version === '') {
            await forget(SESSION_VERSION_KEY);

            return;
        }

        await write(SESSION_VERSION_KEY, String(version));
    },
};

if (typeof window !== 'undefined') {
    window.ExpandorSecureAuthStorage = SecureAuthStorage;
}
