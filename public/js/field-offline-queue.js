/**
 * Field offline queue — sync critical seller actions when back online.
 * Types: point.create | point.update | visit.create | first_approach
 */
(function (global) {
    const KEY = 'expandor.field.offline.queue.v1';

    function read() {
        try {
            return JSON.parse(localStorage.getItem(KEY) || '[]');
        } catch (e) {
            return [];
        }
    }

    function write(items) {
        localStorage.setItem(KEY, JSON.stringify(items));
    }

    function enqueue(type, payload) {
        const items = read();
        items.push({
            id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
            type,
            payload,
            created_at: new Date().toISOString(),
            status: 'pending',
        });
        write(items);
        return items.filter((i) => i.status === 'pending').length;
    }

    function pendingCount() {
        return read().filter((i) => i.status === 'pending').length;
    }

    function csrfFromCookie() {
        const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    function csrfMeta() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function toFormData(obj) {
        const body = new FormData();
        Object.entries(obj || {}).forEach(([k, v]) => {
            if (v != null && v !== '') body.append(k, v);
        });
        return body;
    }

    async function postJson(url, body, method) {
        const response = await fetch(url, {
            method: method || 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfMeta(),
                'X-XSRF-TOKEN': csrfFromCookie(),
            },
            body,
        });
        if (!response.ok) {
            const payload = await response.json().catch(() => ({}));
            const msg = payload?.errors
                ? Object.values(payload.errors).flat()[0]
                : (payload?.message || `HTTP ${response.status}`);
            throw new Error(msg);
        }
        return response.json().catch(() => ({}));
    }

    /**
     * @param {{ pointStoreUrl?: string, pointShowTemplate?: string, visitStoreTemplate?: string }} opts
     */
    async function flush(opts) {
        if (!navigator.onLine) {
            return { synced: 0, remaining: pendingCount(), failed: 0 };
        }

        const options = opts || {};
        const items = read();
        const kept = [];
        let synced = 0;
        let failed = 0;

        for (const item of items) {
            if (item.status !== 'pending') {
                kept.push(item);
                continue;
            }

            try {
                if (item.type === 'point.create') {
                    if (!options.pointStoreUrl) throw new Error('URL de ponto ausente');
                    await postJson(options.pointStoreUrl, toFormData(item.payload));
                } else if (item.type === 'point.update') {
                    const id = item.payload?.property_id;
                    if (!options.pointShowTemplate || !id) throw new Error('Ponto inválido na fila');
                    const url = options.pointShowTemplate.replace('__PROPERTY__', String(id));
                    const body = toFormData(item.payload);
                    body.append('_method', 'PUT');
                    await postJson(url, body);
                } else if (item.type === 'visit.create') {
                    const campaignId = item.payload?.campaign_id;
                    if (!options.visitStoreTemplate || !campaignId) throw new Error('Visita inválida na fila');
                    const url = options.visitStoreTemplate.replace('__CAMPAIGN__', String(campaignId));
                    await postJson(url, toFormData(item.payload));
                } else if (item.type === 'first_approach') {
                    if (!options.firstApproachUrl) throw new Error('URL de primeiro atendimento ausente');
                    await postJson(options.firstApproachUrl, toFormData(item.payload));
                } else {
                    // Unknown type — keep for later sprints
                    kept.push(item);
                    continue;
                }
                synced += 1;
            } catch (err) {
                failed += 1;
                item.last_error = String(err?.message || err);
                item.attempts = (item.attempts || 0) + 1;
                if (item.attempts < 8) {
                    kept.push(item);
                }
            }
        }

        write(kept);
        return { synced, remaining: pendingCount(), failed };
    }

    global.ExpandorOfflineQueue = { enqueue, pendingCount, flush, read };
})(window);
