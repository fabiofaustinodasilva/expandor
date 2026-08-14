/**
 * Handoff ao escritório — mensagem sempre vem do backend (SaleHandoffFormatter).
 */

function toast(message, kind = 'status') {
    window.ExpandorToast?.(message, kind);
}

function debugHandoff(step, detail = {}) {
    try {
        console.info('[EXP Vendedor]', step, detail);
    } catch {
        /* optional */
    }
}

async function copyText(text) {
    const value = String(text || '');
    const clip = window.Capacitor?.Plugins?.Clipboard;
    if (clip?.write) {
        await clip.write({ string: value });

        return;
    }
    if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(value);

        return;
    }
    const area = document.createElement('textarea');
    area.value = value;
    area.setAttribute('readonly', 'true');
    area.style.position = 'fixed';
    area.style.left = '-9999px';
    document.body.appendChild(area);
    area.select();
    document.execCommand('copy');
    area.remove();
}

async function openExternalUrl(url) {
    if (!url) {
        throw new Error('missing_url');
    }
    const native = Boolean(window.Capacitor?.isNativePlatform?.());
    const app = window.Capacitor?.Plugins?.App;
    if (app?.openUrl) {
        try {
            await app.openUrl({ url });
            debugHandoff('handoffExternalOpen', { method: 'app', native });

            return;
        } catch {
            /* WebView / intent fallback */
        }
    }
    const browser = window.Capacitor?.Plugins?.Browser;
    if (browser?.open) {
        try {
            await browser.open({ url });
            debugHandoff('handoffExternalOpen', { method: 'browser', native });

            return;
        } catch {
            /* window.open fallback */
        }
    }
    const opened = window.open(url, '_blank', 'noopener,noreferrer');
    debugHandoff('handoffExternalOpen', { method: 'window', native, opened: Boolean(opened) });
    if (!opened && !native) {
        throw new Error('blocked');
    }
}

export async function copyHandoffMessage(handoff, api) {
    const message = handoff?.message;
    if (!message) {
        toast('Mensagem indisponível.', 'error');

        return;
    }
    try {
        await copyText(message);
        toast('Mensagem copiada', 'status');
        const saleId = handoff.sale_id;
        if (saleId && api?.handoffCopied) {
            api.handoffCopied(saleId).catch(() => {});
        }
    } catch {
        toast('Não foi possível copiar a mensagem.', 'error');
    }
}

export async function openOfficeWhatsApp(handoff, api) {
    debugHandoff('handoffSendStart', { sale_id: handoff?.sale_id || null });
    const canSend = Boolean(handoff?.can_send ?? (handoff?.whatsapp_enabled && handoff?.whatsapp_url));
    if (!canSend) {
        debugHandoff('handoffSendError', { reason: 'cannot_send' });
        toast(
            handoff?.whatsapp_configured === false
                ? 'WhatsApp do escritório não configurado.'
                : 'WhatsApp do escritório não está habilitado.',
            'error',
        );

        return;
    }
    try {
        await openExternalUrl(handoff.whatsapp_url);
        debugHandoff('handoffSendResult', { ok: true });
        toast('WhatsApp aberto.', 'status');
        const saleId = handoff.sale_id;
        if (saleId && api?.handoffOpened) {
            api.handoffOpened(saleId)
                .then(() => debugHandoff('handoffOpened', { sale_id: saleId, ok: true }))
                .catch((error) => debugHandoff('handoffOpened', {
                    sale_id: saleId,
                    ok: false,
                    status: error?.status || null,
                }));
        }
    } catch (error) {
        debugHandoff('handoffSendError', { reason: error?.message || 'open_failed' });
        toast('Não foi possível abrir o WhatsApp. Você pode copiar a mensagem.', 'error');
    }
}

export function showHandoffText(handoff) {
    const pre = document.getElementById('handoff-message-text');
    const sheet = document.getElementById('handoff-message-sheet');
    if (pre) {
        pre.textContent = handoff?.message || '';
    }
    if (sheet) {
        sheet.hidden = false;
    }
}

export function hideHandoffText() {
    const sheet = document.getElementById('handoff-message-sheet');
    if (sheet) {
        sheet.hidden = true;
    }
}

if (typeof window !== 'undefined') {
    window.ExpandorSaleHandoff = {
        copyHandoffMessage,
        openOfficeWhatsApp,
        showHandoffText,
        hideHandoffText,
    };
}
