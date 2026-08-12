/**
 * Falha o build se o shell Android não contiver o fluxo novo de visita/imóvel.
 */
import { existsSync, readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const shellDir = join(root, 'public/capacitor-shell');
const androidIndex = join(root, 'android/app/src/main/assets/public/index.html');
const androidJs = join(root, 'android/app/src/main/assets/public/vendor/seller-app.js');

const requiredHtml = [
    'Como foi a abordagem?',
    'Salvar imóvel',
    'visit-outcome-list',
    'visit-campaign-label',
    'sale-cart-lines',
    'create-point-open',
    'create-point-form',
    'Novo ponto',
    'Localização definida ✓',
];

const requiredJs = [
    'Interessado',
    'Retornar depois',
    'Venda realizada',
    'Não encontrado',
    'Sem interesse',
    '[EXP Vendedor]',
    'openVisitFlow',
    'pointCreated',
    'visitOutcomeSelected',
    'visitSubmitted',
    'after-create-point',
];

const forbidden = [
    'point-status',
    'SITUAÇÃO',
    'id="point-notes"',
];

function assertBundle(label, indexPath, jsPath) {
    if (!existsSync(indexPath)) {
        throw new Error(`[verify-shell] Missing ${label} index: ${indexPath}`);
    }

    const html = readFileSync(indexPath, 'utf8');
    for (const token of requiredHtml) {
        if (!html.includes(token)) {
            throw new Error(`[verify-shell] ${label} index missing required token: ${token}`);
        }
    }
    for (const token of forbidden) {
        if (html.includes(token)) {
            throw new Error(`[verify-shell] ${label} index still contains legacy token: ${token}`);
        }
    }

    if (!jsPath || !existsSync(jsPath)) {
        throw new Error(`[verify-shell] Missing ${label} JS bundle: ${jsPath}`);
    }

    const js = readFileSync(jsPath, 'utf8');
    for (const token of requiredJs) {
        if (!js.includes(token)) {
            throw new Error(`[verify-shell] ${label} JS missing: ${token}`);
        }
    }
}

assertBundle('capacitor-shell', join(shellDir, 'index.html'), join(shellDir, 'vendor/seller-app.js'));

if (existsSync(androidIndex)) {
    assertBundle('android/assets', androidIndex, androidJs);
}

console.log('[verify-shell] capacitor Android bundle contains new field flow markers');
