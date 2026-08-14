/**
 * Handoff ao escritório — mensagem sempre vem do backend (SaleHandoffFormatter).
 */

function toast(message, kind = 'status') {
    window.ExpandorToast?.(message, kind);
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
    const app = window.Capacitor?.Plugins?.App;
    if (app?.openUrl) {
        await app.openUrl({ url });

        return;
    }
    const browser = window.Capacitor?.Plugins?.Browser;
    if (browser?.open) {
        await browser.open({ url });

        return;
    }
    const opened = window.open(url, '_blank', 'noopener,noreferrer');
    if (!opened && window.Capacitor?.isNativePlatform?.()) {
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
    if (!handoff?.whatsapp_enabled || !handoff?.whatsapp_url) {
        toast('WhatsApp do escritório não está habilitado.', 'error');

        return;
    }
    try {
        await openExternalUrl(handoff.whatsapp_url);
        const saleId = handoff.sale_id;
        if (saleId && api?.handoffOpened) {
            api.handoffOpened(saleId).catch(() => {});
        }
    } catch {
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
