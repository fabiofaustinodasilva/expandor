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
const bootstrapSrc = join(root, 'resources/js/mobile/bootstrap-shell.js');

const requiredHtml = [
    'Novo ponto',
    'Local encontrado',
    'Situação / interesse',
    'Como foi a abordagem?',
    'Salvar ponto',
    'CONFIRMAR VENDA',
    'Adicionar produto',
    'create-outcome-list',
    'visit-outcome-list',
    'sheet-body',
    'sheet-footer',
    'point-sector-name',
    'create-point-open',
    'create-point-form',
];

const requiredJs = [
    'Interessado',
    'Não interessado',
    'Retornar depois',
    'Venda realizada',
    'Não encontrado',
    'firstApproach',
    'Confirmar venda',
    'openVisitFlow',
    'pointCreated',
    'visitOutcomeSelected',
];

/** Markers only asserted on freshly built capacitor-shell (android assets update after cap sync). */
const requiredFreshJs = [
    'campaign_context',
    'has_campaign',
    'requires_selection',
    'no_campaign_message',
];

const forbidden = [
    'point-status',
    'SITUAÇÃO',
    'Novo imóvel',
    'id="point-sector"',
];

const bootstrapTopLevelFns = [
    'debugFlow',
    'paintCampaignInto',
    'paintCampaignContext',
    'renderOutcomeButtons',
    'selectCreateOutcome',
    'hydrateCatalog',
    'tryInitialMapGps',
    'enterApp',
    'startup',
];

function assertBootstrapSourceScope() {
    if (!existsSync(bootstrapSrc)) {
        throw new Error(`[verify-shell] Missing bootstrap source: ${bootstrapSrc}`);
    }

    const src = readFileSync(bootstrapSrc, 'utf8');
    const closedDebugFlow = /function debugFlow\([^)]*\)\s*\{\s*try\s*\{[\s\S]*?\}\s*catch\s*\{[\s\S]*?\}\s*\}\s*function renderOutcomeButtons/;
    if (!closedDebugFlow.test(src)) {
        throw new Error('[verify-shell] debugFlow is not closed before renderOutcomeButtons (nests campaign helpers)');
    }

    for (const fn of bootstrapTopLevelFns) {
        const re = new RegExp(`^(async )?function ${fn}\\(`, 'm');
        if (!re.test(src)) {
            throw new Error(`[verify-shell] bootstrap-shell.js missing top-level function: ${fn}`);
        }
    }
}

function assertBundle(label, indexPath, jsPath, { fresh = false } = {}) {
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
    if (fresh) {
        for (const token of requiredFreshJs) {
            if (!js.includes(token)) {
                throw new Error(`[verify-shell] ${label} JS missing campaign marker: ${token}`);
            }
        }
    }
}

assertBootstrapSourceScope();
assertBundle('capacitor-shell', join(shellDir, 'index.html'), join(shellDir, 'vendor/seller-app.js'), { fresh: true });

if (existsSync(androidIndex)) {
    assertBundle('android/assets', androidIndex, androidJs);
}

console.log('[verify-shell] capacitor Android bundle contains new field flow markers');
console.log('[verify-shell] bootstrap campaign helpers are top-level (no nested paintCampaignInto)');
