/**
 * Shared EXP Vendedor bake/release URL rules.
 * QA/release never fall back to APP_URL (that is how 127.0.0.1 leaked into APKs).
 */
export const QA_API_ORIGIN = 'https://expandor.unicanetwork.com.br';
export const QA_WEB_ORIGIN = 'https://expandor.unicanetwork.com.br';
export const DEFAULT_APP_VERSION = '8.2.34';

export const BLOCKED_HOSTS = new Set([
    '127.0.0.1',
    'localhost',
    '0.0.0.0',
    '10.0.2.2',
    '::1',
    '[::1]',
    'localhost.localdomain',
]);

export function normalizeBuildMode(raw) {
    const value = String(raw || 'development').trim().toLowerCase();
    if (value === 'qa' || value === 'release' || value === 'development' || value === 'dev') {
        return value === 'dev' ? 'development' : value;
    }

    throw new Error(`[EXP Release] BLOCKED: unknown EXP_BUILD_MODE=${raw}`);
}

export function isDistributionMode(mode) {
    return mode === 'qa' || mode === 'release';
}

export function isBlockedHost(hostname) {
    const host = String(hostname || '').trim().toLowerCase().replace(/^\[|\]$/g, '');
    if (!host) {
        return true;
    }
    if (BLOCKED_HOSTS.has(host) || BLOCKED_HOSTS.has(`[${host}]`)) {
        return true;
    }
    if (host.endsWith('.local')) {
        return true;
    }

    return false;
}

export function parseBakedRuntime(source) {
    const text = String(source || '');
    const api = text.match(/window\.EXPANDOR_API_BASE\s*=\s*["']([^"']*)["']/);
    const web = text.match(/window\.EXPANDOR_WEB_ORIGIN\s*=\s*["']([^"']*)["']/);

    return {
        api: api ? api[1].replace(/\/$/, '') : '',
        web: web ? web[1].replace(/\/$/, '') : '',
    };
}

export function assertDistributionUrl(label, value, { requireHttps = true } = {}) {
    const raw = String(value || '').trim();
    if (!raw) {
        throw new Error(`[EXP Release] BLOCKED: ${label} missing`);
    }

    let url;
    try {
        url = new URL(raw);
    } catch {
        throw new Error(`[EXP Release] BLOCKED: ${label} is not a valid URL`);
    }

    if (isBlockedHost(url.hostname)) {
        throw new Error(`[EXP Release] BLOCKED: ${label} points to ${url.hostname}`);
    }

    if (requireHttps && url.protocol !== 'https:') {
        throw new Error(`[EXP Release] BLOCKED: ${label} must use HTTPS`);
    }

    return url.toString().replace(/\/$/, '');
}

export function assertDistributionPair(api, web, { requireHttps = true } = {}) {
    const apiUrl = assertDistributionUrl('API', api, { requireHttps });
    const webUrl = assertDistributionUrl('Web origin', web, { requireHttps });

    return { api: apiUrl, web: webUrl };
}

export function resolveBakeUrls(env = process.env) {
    const mode = normalizeBuildMode(env.EXP_BUILD_MODE);
    if (isDistributionMode(mode)) {
        const api = String(env.CAP_API_URL || '').trim().replace(/\/$/, '');
        const web = String(env.CAP_WEB_ORIGIN || '').trim().replace(/\/$/, '');
        if (!api) {
            throw new Error('[EXP Release] BLOCKED: QA/release requires CAP_API_URL (will not fall back to APP_URL)');
        }
        if (!web) {
            throw new Error('[EXP Release] BLOCKED: QA/release requires CAP_WEB_ORIGIN (will not fall back to APP_URL)');
        }

        return { mode, ...assertDistributionPair(api, web, { requireHttps: true }) };
    }

    const api = String(env.CAP_API_URL || env.APP_URL || '').trim().replace(/\/$/, '');
    const web = String(env.CAP_WEB_ORIGIN || env.APP_URL || api).trim().replace(/\/$/, '');

    return { mode, api, web };
}

export function runSelfTests() {
    const cases = [];
    const check = (name, fn) => {
        try {
            fn();
            cases.push({ name, ok: true });
        } catch (error) {
            cases.push({ name, ok: false, error: error.message });
        }
    };

    check('A production https passes', () => {
        assertDistributionPair(QA_API_ORIGIN, QA_WEB_ORIGIN);
    });
    check('B 127.0.0.1 fails', () => {
        let failed = false;
        try {
            assertDistributionUrl('API', 'http://127.0.0.1:8000');
        } catch {
            failed = true;
        }
        if (!failed) {
            throw new Error('expected failure');
        }
    });
    check('C localhost fails', () => {
        let failed = false;
        try {
            assertDistributionUrl('API', 'http://localhost:8000');
        } catch {
            failed = true;
        }
        if (!failed) {
            throw new Error('expected failure');
        }
    });
    check('D 10.0.2.2 fails', () => {
        let failed = false;
        try {
            assertDistributionUrl('API', 'http://10.0.2.2:8000');
        } catch {
            failed = true;
        }
        if (!failed) {
            throw new Error('expected failure');
        }
    });
    check('E http in release fails', () => {
        let failed = false;
        try {
            assertDistributionUrl('API', 'http://expandor.unicanetwork.com.br', { requireHttps: true });
        } catch {
            failed = true;
        }
        if (!failed) {
            throw new Error('expected failure');
        }
    });
    check('F good API + local web fails', () => {
        let failed = false;
        try {
            assertDistributionPair(QA_API_ORIGIN, 'http://127.0.0.1:8000');
        } catch {
            failed = true;
        }
        if (!failed) {
            throw new Error('expected failure');
        }
    });
    check('G missing runtime fields fail', () => {
        const parsed = parseBakedRuntime('');
        let failed = false;
        try {
            assertDistributionPair(parsed.api, parsed.web);
        } catch {
            failed = true;
        }
        if (!failed) {
            throw new Error('expected failure');
        }
    });
    check('H QA without CAP_API_URL fails', () => {
        let failed = false;
        try {
            resolveBakeUrls({ EXP_BUILD_MODE: 'qa', APP_URL: 'http://127.0.0.1:8000' });
        } catch {
            failed = true;
        }
        if (!failed) {
            throw new Error('expected failure');
        }
    });

    const failed = cases.filter((row) => !row.ok);
    for (const row of cases) {
        console.log(`${row.ok ? 'OK' : 'FAIL'} ${row.name}${row.error ? ` — ${row.error}` : ''}`);
    }
    if (failed.length) {
        throw new Error(`[EXP Release] self-test failed (${failed.length})`);
    }
}

if (process.argv.includes('--self-test')) {
    try {
        runSelfTests();
        process.exit(0);
    } catch (error) {
        console.error(error.message || error);
        process.exit(1);
    }
}
