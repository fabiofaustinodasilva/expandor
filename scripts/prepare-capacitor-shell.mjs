/**
 * Copies the seller IIFE vendor into public/capacitor-shell
 * with relative URLs (Capacitor WebView / file).
 */
import { execFileSync } from 'node:child_process';
import { cpSync, existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { MAP_TILE_CSP_HOSTS } from './capacitor-map-csp-hosts.mjs';
import { DEFAULT_APP_VERSION, resolveBakeUrls } from './exp-vendedor-release-config.mjs';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const vendor = join(root, 'public/vendor/expandor');
const outDir = join(root, 'public/capacitor-shell');

function loadEnvFile(filePath) {
    if (!existsSync(filePath)) {
        return;
    }

    const text = readFileSync(filePath, 'utf8');
    for (const raw of text.split(/\r?\n/)) {
        const line = raw.trim();
        if (!line || line.startsWith('#')) {
            continue;
        }

        const eq = line.indexOf('=');
        if (eq < 1) {
            continue;
        }

        const key = line.slice(0, eq).trim();
        if (!/^[A-Z0-9_]+$/.test(key) || process.env[key] !== undefined) {
            continue;
        }

        let value = line.slice(eq + 1).trim();
        if (
            (value.startsWith('"') && value.endsWith('"'))
            || (value.startsWith("'") && value.endsWith("'"))
        ) {
            value = value.slice(1, -1);
        }

        process.env[key] = value;
    }
}

loadEnvFile(join(root, '.env'));
loadEnvFile(join(root, '.env.local'));

if (!existsSync(join(vendor, 'seller-app.js')) || !existsSync(join(vendor, 'seller-app.css'))) {
    throw new Error('Missing public/vendor/expandor/seller-app.{js,css}. Run the seller Vite build first.');
}

rmSync(outDir, { recursive: true, force: true });
mkdirSync(outDir, { recursive: true });
cpSync(vendor, join(outDir, 'vendor'), { recursive: true });

const sound = join(root, 'public/sounds/commission-coins.wav');
if (existsSync(sound)) {
    mkdirSync(join(outDir, 'sounds'), { recursive: true });
    cpSync(sound, join(outDir, 'sounds/commission-coins.wav'));
}

const brandAssets = join(root, 'public/images/exp-vendedor');
const logoPng = join(brandAssets, 'exp-vendedor-logo.png');
const iconPng = join(brandAssets, 'exp-vendedor-icon.png');

if (!existsSync(logoPng)) {
    throw new Error('Missing public/images/exp-vendedor/exp-vendedor-logo.png — official EXP Vendedor logo required.');
}
if (!existsSync(iconPng)) {
    throw new Error('Missing public/images/exp-vendedor/exp-vendedor-icon.png — official EXP Vendedor icon required.');
}

mkdirSync(join(outDir, 'assets/exp-vendedor'), { recursive: true });
cpSync(logoPng, join(outDir, 'assets/exp-vendedor/exp-vendedor-logo.png'));
cpSync(iconPng, join(outDir, 'assets/exp-vendedor/exp-vendedor-icon.png'));
cpSync(logoPng, join(outDir, 'vendor/exp-vendedor-logo.png'));
cpSync(iconPng, join(outDir, 'vendor/exp-vendedor-icon.png'));

let bake;
try {
    bake = resolveBakeUrls(process.env);
} catch (error) {
    throw new Error(error.message || String(error));
}

const apiBase = bake.api;
const webBase = bake.web;
if (!apiBase) {
    throw new Error(
        'CAP_API_URL (or APP_URL in development) is required to bake Expandor shell API base. '
        + 'QA/release must set CAP_API_URL and CAP_WEB_ORIGIN and will not fall back to APP_URL.',
    );
}

let connectSrc = "'self'";
let imgSrc = ["'self'", 'data:', 'blob:', ...MAP_TILE_CSP_HOSTS].join(' ');
let apiOrigin = '';
let webOrigin = '';
try {
    apiOrigin = new URL(apiBase).origin;
    webOrigin = new URL(webBase).origin;
    connectSrc = `'self' ${apiOrigin} ${MAP_TILE_CSP_HOSTS.join(' ')}`;
    imgSrc += ` ${apiOrigin}`;
    if (webOrigin !== apiOrigin) {
        imgSrc += ` ${webOrigin}`;
        connectSrc += ` ${webOrigin}`;
    }
} catch {
    throw new Error(`Invalid CAP_API_URL/APP_URL/CAP_WEB_ORIGIN for Capacitor shell: ${apiBase} / ${webBase}`);
}

console.log('capacitor-shell API base:', apiBase);
console.log('capacitor-shell web origin:', webBase);

// Google Maps JS API (Maps JavaScript API via GoogleMutant) — script + fonts/styles used by the SDK.
const googleMapsScriptHosts = 'https://maps.googleapis.com https://maps.gstatic.com';
const googleMapsFontHosts = 'https://fonts.gstatic.com https://fonts.googleapis.com';

const csp = [
    "default-src 'self'",
    `script-src 'self' ${googleMapsScriptHosts}`,
    `style-src 'self' 'unsafe-inline' ${googleMapsFontHosts}`,
    `img-src ${imgSrc}`,
    `connect-src ${connectSrc}`,
    `font-src 'self' data: ${googleMapsFontHosts}`,
    `media-src 'self' blob: ${apiOrigin}${webOrigin && webOrigin !== apiOrigin ? ` ${webOrigin}` : ''}`,
    "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com",
    "worker-src 'self' blob:",
    "object-src 'none'",
    "base-uri 'self'",
    // frame-ancestors is ignored on <meta> CSP (browser warning). Keep framing controls
    // on HTTP responses when the shell is hosted; Capacitor WebView uses local assets.
].join('; ');

const shellVersion = String(process.env.EXPANDOR_SHELL_VERSION || DEFAULT_APP_VERSION || '8.2.34').trim();
const buildStamp = new Date().toISOString();
const buildMode = bake.mode;
let gitHash = 'unknown';
try {
    gitHash = execFileSync('git', ['rev-parse', '--short', 'HEAD'], {
        cwd: root,
        encoding: 'utf8',
    }).trim() || 'unknown';
} catch {
    gitHash = 'unknown';
}

const runtimeConfig = [
    `window.EXPANDOR_API_BASE = ${JSON.stringify(apiBase)};`,
    `window.EXPANDOR_WEB_ORIGIN = ${JSON.stringify(webBase)};`,
    `window.EXPANDOR_APP_VERSION = ${JSON.stringify(shellVersion)};`,
    `window.EXPANDOR_BUILD_MODE = ${JSON.stringify(buildMode)};`,
    `window.EXPANDOR_GIT_HASH = ${JSON.stringify(gitHash)};`,
    '',
].join('\n');

writeFileSync(join(outDir, 'runtime-config.js'), runtimeConfig);

function saleFormMarkup(prefix) {
    const p = prefix;
    return `
            <div class="visit-block" id="${p}sale-block" hidden>
                <p class="sheet__section-title">CONFIRMAR VENDA</p>
                <ol class="sale-steps">
                    <li>Cliente</li>
                    <li>Endereço</li>
                    <li>Contratação</li>
                    <li>Revisão</li>
                </ol>
                <section class="sale-section sale-section--client">
                    <p class="sale-section__title">Dados do cliente</p>
                    <label class="field-label" for="${p}sale-name">Nome Completo <span id="${p}sale-name-required" class="req-star">*</span></label>
                    <input class="field-input" id="${p}sale-name" placeholder="João da Silva" autocomplete="name">
                    <label class="field-label" for="${p}sale-document">CPF <span id="${p}sale-document-required" class="req-star">*</span></label>
                    <input class="field-input" id="${p}sale-document" placeholder="000.000.000-00" inputmode="numeric" autocomplete="off">
                    <label class="field-label" for="${p}sale-birth">Data de nascimento <span class="req-star">*</span></label>
                    <input class="field-input" id="${p}sale-birth" placeholder="dd/mm/aaaa" inputmode="numeric" autocomplete="bday">
                    <label class="field-label" for="${p}sale-phone">Telefone / WhatsApp <span id="${p}sale-phone-required" class="req-star">*</span></label>
                    <input class="field-input" id="${p}sale-phone" placeholder="(64) 99999-9999" inputmode="tel" autocomplete="tel">
                    <div id="${p}sale-whatsapp-wrap" hidden>
                        <label class="field-label" for="${p}sale-whatsapp">WhatsApp <span id="${p}sale-whatsapp-required" class="req-star" hidden>*</span></label>
                        <input class="field-input" id="${p}sale-whatsapp" placeholder="WhatsApp" inputmode="tel">
                    </div>
                    <div id="${p}sale-rg-wrap" hidden>
                        <label class="field-label" for="${p}sale-rg">RG <span id="${p}sale-rg-required" class="req-star" hidden>*</span></label>
                        <input class="field-input" id="${p}sale-rg" placeholder="RG">
                    </div>
                    <div id="${p}sale-email-wrap" hidden>
                        <label class="field-label" for="${p}sale-email">E-mail <span id="${p}sale-email-required" class="req-star" hidden>*</span></label>
                        <input class="field-input" id="${p}sale-email" placeholder="E-mail" type="email" autocomplete="email">
                    </div>
                </section>
                <section class="sale-section sale-section--address">
                    <p class="sale-section__title">Endereço da instalação</p>
                    <label class="field-label" for="${p}sale-install-street">Rua / Avenida <span class="req-star">*</span></label>
                    <input class="field-input" id="${p}sale-install-street" placeholder="Rua Goiás" autocomplete="street-address">
                    <label class="field-label" for="${p}sale-install-number">Número</label>
                    <input class="field-input" id="${p}sale-install-number" placeholder="123" inputmode="numeric">
                    <label class="field-label" for="${p}sale-install-neighborhood">Bairro</label>
                    <input class="field-input" id="${p}sale-install-neighborhood" placeholder="Centro">
                    <label class="field-label" for="${p}sale-install-reference">Ponto de referência</label>
                    <input class="field-input" id="${p}sale-install-reference" placeholder="Próximo à praça">
                    <label class="field-label" for="${p}sale-install-city">Cidade</label>
                    <input class="field-input" id="${p}sale-install-city" placeholder="Digite a cidade" autocomplete="address-level2">
                    <p class="card__meta" id="${p}sale-install-city-hint" hidden>Definida pela campanha</p>
                    <input type="hidden" id="${p}sale-install-city-id">
                </section>
                <section class="sale-section sale-section--contract">
                    <p class="sale-section__title">Contratação</p>
                    <p class="field-label">Dia de vencimento <span class="req-star">*</span></p>
                    <input type="hidden" id="${p}sale-due-day">
                    <div class="due-day-chips" id="${p}sale-due-chips"></div>
                    <div class="sale-cart-head">
                        <label class="field-label">Produtos <span id="${p}sale-product-required" class="req-star" hidden>*</span></label>
                        <button type="button" class="btn btn-ghost" id="${p}sale-cart-add">+ Adicionar produto</button>
                    </div>
                    <div id="${p}sale-cart-lines" class="sale-cart-lines"></div>
                    <p class="card__meta" id="${p}sale-cart-empty">Nenhum produto ainda. Toque em “+ Adicionar produto”.</p>
                    <div class="sale-cart-total">
                        <span class="muted">Total</span>
                        <strong id="${p}sale-cart-total">R$ 0,00</strong>
                    </div>
                    <div id="${p}sale-notes-wrap" hidden>
                        <label class="field-label" for="${p}sale-notes">Observações da venda <span id="${p}sale-notes-required" class="req-star" hidden>*</span></label>
                        <textarea class="field-textarea" id="${p}sale-notes" placeholder="Observações comerciais desta venda"></textarea>
                    </div>
                </section>
                <section class="sale-section sale-section--review">
                    <p class="sale-section__title">Revisão</p>
                    <div class="sale-review" id="${p}sale-review"></div>
                </section>
            </div>`;
}

const html = `<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0B1F3A">
    <meta name="exp-shell-version" content="${shellVersion}">
    <meta name="exp-shell-built-at" content="${buildStamp}">
    <meta name="exp-shell-mode" content="${buildMode}">
    <meta name="exp-shell-hash" content="${gitHash}">
    <meta http-equiv="Content-Security-Policy" content="${csp}">
    <title>EXP Vendedor</title>
    <link rel="stylesheet" href="./vendor/seller-app.css?v=${shellVersion}">
</head>
<body data-shell-version="${shellVersion}" data-shell-built-at="${buildStamp}" data-shell-mode="${buildMode}" data-shell-hash="${gitHash}">
    <div class="restore-gate" id="screen-restore">
        <img class="login-brand__logo" src="./vendor/exp-vendedor-logo.png" alt="EXP Vendedor" width="120" height="120" decoding="async">
        <p class="login-brand__welcome">EXP Vendedor</p>
        <p class="restore-gate__status" id="restore-status">Entrando...</p>
        <div id="restore-error" class="banner" hidden></div>
        <button class="btn btn-primary btn-block" id="restore-retry" type="button" hidden>Tentar novamente</button>
    </div>

    <div class="login-card" id="screen-login" hidden>
        <header class="login-brand">
            <img class="login-brand__logo" src="./vendor/exp-vendedor-logo.png" alt="EXP Vendedor" width="120" height="120" decoding="async">
            <p class="login-brand__welcome">Bem-vindo ao Expandor</p>
            <p class="login-brand__hint">Acesse sua conta</p>
        </header>
        <div id="auth-banner" class="banner" hidden></div>
        <form id="expandor-login-form" autocomplete="on">
            <label class="field-label" for="login-email">E-mail</label>
            <input class="field-input" id="login-email" name="email" type="email" required autofocus autocomplete="username">
            <label class="field-label" for="login-password">Senha</label>
            <input class="field-input" id="login-password" name="password" type="password" required autocomplete="current-password">
            <button class="btn btn-primary btn-block" id="login-submit" type="submit" style="margin-top:0.85rem">Entrar</button>
        </form>
        <a class="link-muted" id="login-forgot" href="/esqueci-minha-senha" data-forgot-password="1">Esqueceu sua senha?</a>
    </div>

    <div id="screen-app" hidden>
        <header class="app-header">
            <div class="app-header__left">
                <img class="app-header__logo" src="./vendor/exp-vendedor-icon.png" alt="" width="32" height="32" decoding="async">
                <div class="app-header__text">
                    <p class="app-header__hello" id="signed-hello">Olá</p>
                    <p class="app-header__company" id="signed-company"></p>
                </div>
            </div>
            <button class="btn-icon" id="profile-btn" type="button" aria-label="Minha conta">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </button>
        </header>
        <div id="net-banner" hidden>
            <span>Sem conexão. Sua sessão permanece salva.</span>
            <button type="button" class="btn btn-ghost" id="net-retry">Tentar novamente</button>
        </div>
        <div id="app-toast" class="banner" hidden></div>

        <main class="app-main">
            <section class="pane" id="pane-map">
                <div id="seller-map"></div>
                <p class="map-loading-hint" id="map-loading-hint" hidden>Carregando pontos...</p>
                <div class="map-toolbar map-toolbar--left">
                    <button class="map-fab map-fab--present" id="map-present-products" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                        Apresentar produtos
                    </button>
                </div>
                <div class="map-toolbar">
                    <div class="map-layers-menu" id="map-layers-menu" hidden>
                        <button type="button" id="layer-street" data-active="1">Mapa</button>
                        <button type="button" id="layer-satellite">Satélite</button>
                    </div>
                    <button class="map-fab map-fab--add map-fab--labeled" id="create-point-open" type="button" aria-label="Novo ponto">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                        <span class="map-fab__label">Novo ponto</span>
                    </button>
                    <button class="map-fab map-fab--layers" id="map-layers-btn" type="button" aria-label="Camadas">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg>
                    </button>
                    <button class="map-fab map-fab--gps" id="gps-btn" type="button" aria-label="Meu Local">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        Meu Local
                    </button>
                </div>
            </section>

            <section class="pane" id="pane-agenda" hidden>
                <h2 class="pane-title">Agenda</h2>
                <div id="agenda-list"></div>
            </section>

            <section class="pane" id="pane-clients" hidden>
                <h2 class="pane-title">Clientes</h2>
                <input class="search-field" id="clients-q" type="search" placeholder="Buscar por nome, telefone ou endereço">
                <div id="clients-list"></div>
            </section>

            <section class="pane" id="pane-results" hidden>
                <h2 class="pane-title">Resultado</h2>
                <div id="results-list"></div>
            </section>

            <section class="pane" id="pane-commissions" hidden>
                <h2 class="pane-title">Comissão</h2>
                <div id="commissions-list"></div>
            </section>

            <section class="pane" id="pane-more" hidden>
                <h2 class="pane-title">Mais</h2>
                <ul class="menu-list">
                    <li class="menu-group">Trabalho</li>
                    <li><button class="menu-list__item" id="menu-products" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                        Apresentar produtos
                    </button></li>
                    <li class="menu-group">Conta</li>
                    <li><button class="menu-list__item" id="menu-account" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Minha conta
                    </button></li>
                    <li><button class="menu-list__item" id="menu-about" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        Sobre o Expandor
                    </button></li>
                    <li class="menu-group">Sessão</li>
                    <li><button class="menu-list__item menu-list__item--danger" id="logout-button" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                        Sair
                    </button></li>
                </ul>
                <p class="app-version" id="app-version">EXP Vendedor</p>
            </section>
        </main>

        <nav class="bottom-nav" aria-label="Navegação principal">
            <button class="bottom-nav__item" id="nav-map" type="button" data-active="1">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                Mapa
            </button>
            <button class="bottom-nav__item" id="nav-agenda" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                Agenda
            </button>
            <button class="bottom-nav__item" id="nav-clients" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Clientes
            </button>
            <button class="bottom-nav__item" id="nav-results" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/></svg>
                Resultado
            </button>
            <button class="bottom-nav__item" id="nav-commissions" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                Comissão
            </button>
            <button class="bottom-nav__item" id="nav-more" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                Mais
            </button>
        </nav>

        <div class="sheet" id="point-sheet" hidden>
            <div class="sheet__handle"></div>
            <h3 class="sheet__title" id="point-title">Imóvel</h3>
            <p class="card__meta" id="point-meta"></p>
            <input type="hidden" id="point-id">
            <div class="sheet-actions">
                <a class="btn btn-ghost" id="point-call" href="#">Ligar</a>
                <a class="btn btn-primary" id="point-wa" href="#">WhatsApp</a>
            </div>
            <div class="sheet-actions" style="margin-top:0.75rem">
                <button class="btn btn-ghost btn-block" id="point-adjust-map" type="button">Ajustar posição no mapa</button>
                <button class="btn btn-accent btn-block" id="point-register-visit" type="button">Registrar visita</button>
                <button class="btn btn-ghost btn-block" id="point-sheet-close" type="button">Fechar</button>
            </div>
            <div class="sale-section sale-section--review sale-success-actions" id="point-handoff-block" hidden>
                <p class="sale-section__title">Encaminhamento ao escritório</p>
                <p class="handoff-hint" id="point-handoff-hint" hidden></p>
                <button type="button" class="btn btn-success btn-block" id="point-handoff-whatsapp">Enviar no WhatsApp</button>
                <button type="button" class="btn btn-info btn-block" id="point-handoff-copy">Copiar mensagem</button>
                <button type="button" class="btn btn-ghost btn-block" id="point-handoff-view">Ver mensagem</button>
            </div>
        </div>

        <div class="sheet" id="adjust-sheet" hidden>
            <div class="sheet__handle"></div>
            <h3 class="sheet__title">Ajustar posição</h3>
            <p class="card__meta" id="adjust-sheet-meta">Toque no mapa ou arraste o marcador para a nova posição.</p>
            <div class="sheet-actions" style="margin-top:0.75rem">
                <button class="btn btn-accent btn-block" id="adjust-confirm" type="button">Confirmar nova posição</button>
                <button class="btn btn-ghost btn-block" id="adjust-cancel" type="button">Cancelar</button>
            </div>
        </div>


        <div class="sheet" id="visit-sheet" hidden>
            <div class="sheet-header">
                <div class="sheet__handle"></div>
                <p class="sheet__section-title">RESULTADO DA ABORDAGEM</p>
                <h3 class="sheet__title" id="visit-sheet-title">Registrar visita</h3>
                <p class="card__meta" id="visit-sheet-subtitle">Como foi a abordagem?</p>
            </div>
            <div class="sheet-body">
                <input type="hidden" id="visit-property-id">
                <input type="hidden" id="visit-status" value="">
                <input type="hidden" id="visit-follow-up-id" value="">
                <select class="field-select" id="point-campaign" hidden></select>

                <div id="visit-campaign-block">
                    <label class="field-label">Campanha</label>
                    <p class="campaign-context__value" id="visit-campaign-label" hidden></p>
                    <select class="field-select" id="visit-campaign"></select>
                    <p class="banner" id="visit-campaign-warning" hidden data-kind="error"></p>
                </div>

                <div id="visit-outcome-list" class="outcome-grid" role="listbox" aria-label="Como foi a abordagem?"></div>

                <div class="visit-block" id="visit-notes-block">
                    <label class="field-label" for="visit-notes">Observações <span class="muted" id="visit-notes-hint">(opcional)</span></label>
                    <textarea class="field-textarea" id="visit-notes" placeholder="Ex.: voltar amanhã à tarde"></textarea>
                </div>

                
            <div class="visit-block" id="visit-return-block" hidden>
                <label class="field-label">Quando voltar? <span style="color:#FCA5A5">*</span></label>
                <div class="return-shortcuts" id="visit-return-shortcuts">
                    <button type="button" class="return-shortcut" data-days="1">Amanhã</button>
                    <button type="button" class="return-shortcut" data-days="2">+2 dias</button>
                    <button type="button" class="return-shortcut" data-days="pick">Escolher data</button>
                </div>
                <div class="sheet-actions" style="grid-template-columns:1fr 1fr;margin-top:0.55rem">
                    <div>
                        <label class="field-label" for="visit-follow-up-date">Data</label>
                        <input class="field-input" id="visit-follow-up-date" type="date">
                    </div>
                    <div>
                        <label class="field-label" for="visit-follow-up-time">Horário</label>
                        <input class="field-input" id="visit-follow-up-time" type="time">
                    </div>
                </div>
            </div>
                
            ${saleFormMarkup('')}

                <div id="visit-error" class="banner" hidden data-kind="error"></div>
            </div>
            <div class="sheet-footer">
                <div class="sheet-actions">
                    <button class="btn btn-ghost" id="visit-sheet-close" type="button">Cancelar</button>
                    <button class="btn btn-primary" id="visit-submit" type="button">Salvar visita</button>
                </div>
            </div>
        </div>


        <div class="sheet" id="create-sheet" hidden>
            <div class="sheet-header">
                <div class="sheet__handle"></div>
                <p class="sheet__section-title">ADICIONAR LOCAL</p>
                <h3 class="sheet__title">Novo ponto</h3>
                <p class="card__meta">Cadastre este local para iniciar uma abordagem.</p>
            </div>
            <div class="sheet-body">
                <form id="create-point-form">
                    <input type="hidden" id="point-lat">
                    <input type="hidden" id="point-lng">
                    <input type="hidden" id="create-visit-status" value="">
                    <div class="gps-block">
                        <p class="gps-block__title">Local encontrado</p>
                        <p class="gps-block__label" id="create-location-status">Posição pronta para registro</p>
                        <button type="button" class="btn btn-ghost btn-block" id="create-adjust-map" style="margin-top:0.65rem">Ajustar posição no mapa</button>
                    </div>

                    <p class="sheet__section-title">CLIENTE</p>
                    <label class="field-label" for="point-contact">Nome / responsável</label>
                    <input class="field-input" id="point-contact" placeholder="Quem atendeu" autocomplete="name">
                    <label class="field-label" for="point-phone">Telefone / WhatsApp</label>
                    <input class="field-input" id="point-phone" placeholder="WhatsApp" inputmode="tel" autocomplete="tel">
                    <label class="field-label" for="point-notes">Observação curta <span class="muted" id="create-notes-hint">(opcional)</span></label>
                    <textarea class="field-textarea" id="point-notes" placeholder="Referência rápida…"></textarea>

                    <div id="create-campaign-block">
                        <label class="field-label">Campanha</label>
                        <p class="campaign-context__value" id="create-campaign-label" hidden></p>
                        <select class="field-select" id="create-campaign"></select>
                        <p class="banner" id="create-campaign-warning" hidden data-kind="error"></p>
                    </div>

                    <p class="sheet__section-title" style="margin-top:0.85rem">RESULTADO DO ATENDIMENTO</p>
                    <p class="field-label">Situação / interesse</p>
                    <div id="create-outcome-list" class="outcome-grid" role="listbox" aria-label="Situação / interesse"></div>

                    
            <div class="visit-block" id="create-return-block" hidden>
                <label class="field-label">Quando voltar? <span style="color:#FCA5A5">*</span></label>
                <div class="return-shortcuts" id="create-return-shortcuts">
                    <button type="button" class="return-shortcut" data-days="1">Amanhã</button>
                    <button type="button" class="return-shortcut" data-days="2">+2 dias</button>
                    <button type="button" class="return-shortcut" data-days="pick">Escolher data</button>
                </div>
                <div class="sheet-actions" style="grid-template-columns:1fr 1fr;margin-top:0.55rem">
                    <div>
                        <label class="field-label" for="create-follow-up-date">Data</label>
                        <input class="field-input" id="create-follow-up-date" type="date">
                    </div>
                    <div>
                        <label class="field-label" for="create-follow-up-time">Horário</label>
                        <input class="field-input" id="create-follow-up-time" type="time">
                    </div>
                </div>
            </div>
                    
            ${saleFormMarkup('create-')}

                    <details class="address-advanced" id="create-address-advanced" style="margin-top:1rem">
                        <summary class="field-label" style="cursor:pointer">Endereço (opcional no campo)</summary>
                        <div id="create-city-block">
                            <label class="field-label" for="point-city">Cidade</label>
                            <select class="field-select" id="point-city"></select>
                        </div>
                        <label class="field-label" for="point-sector-name">Setor/Bairro</label>
                        <input class="field-input" id="point-sector-name" placeholder="Digite o setor ou bairro" autocomplete="address-level2">
                        <label class="field-label" for="point-street">Rua</label>
                        <input class="field-input" id="point-street" placeholder="Nome da rua">
                        <label class="field-label" for="point-number">Número</label>
                        <input class="field-input" id="point-number" placeholder="S/N">
                    </details>

                    <div id="create-error" class="banner" hidden data-kind="error"></div>
                </form>
            </div>
            <div class="sheet-footer">
                <div class="sheet-actions">
                    <button class="btn btn-ghost" id="create-point-cancel" type="button">Cancelar</button>
                    <button class="btn btn-primary" id="create-point-submit" type="submit" form="create-point-form">Salvar ponto</button>
                </div>
            </div>
        </div>

        <div class="sheet" id="account-sheet" hidden>
            <div class="sheet__handle"></div>
            <h3 class="sheet__title">Minha conta</h3>
            <p class="card__meta">Nome</p>
            <p class="card__title" id="account-name">—</p>
            <p class="card__meta">E-mail</p>
            <p class="card__title" id="account-email">—</p>
            <p class="card__meta">Empresa</p>
            <p class="card__title" id="account-company">—</p>
            <div class="sheet-actions">
                <button class="btn btn-ghost" id="account-close" type="button">Fechar</button>
                <button class="btn btn-ghost menu-list__item--danger" id="account-logout" type="button">Sair</button>
            </div>
        </div>

        <div class="sheet" id="about-sheet" hidden>
            <div class="sheet__handle"></div>
            <h3 class="sheet__title">Sobre o Expandor</h3>
            <p class="card__meta">EXP Vendedor é o aplicativo de campo da plataforma Expandor para vendas porta a porta.</p>
            <p class="app-version" id="about-version"></p>
            <p class="app-version" id="about-shell-build"></p>
            <button class="btn btn-ghost btn-block" id="about-close" type="button">Fechar</button>
        </div>

        <div class="presentation-pane" id="pane-presentation" hidden>
            <div class="deck-shell" id="presentation-deck">
                <div class="deck-chrome">
                    <div class="deck-chrome-left">
                        <button class="deck-back" id="presentation-close" type="button">Voltar ao mapa</button>
                        <button type="button" class="deck-details" id="deck-details" aria-haspopup="dialog" aria-controls="deck-details-sheet">Detalhes</button>
                    </div>
                    <button type="button" class="deck-contract" id="deck-contract">Contratar</button>
                </div>
                <div class="deck-counter" id="deck-counter" aria-live="polite">0 / 0</div>
                <div class="deck-viewport" id="deck-viewport">
                    <div class="deck-track" id="deck-track"></div>
                </div>
                <div class="deck-nav" aria-label="Navegação da apresentação">
                    <button type="button" id="deck-prev" aria-label="Produto anterior">←</button>
                    <button type="button" id="deck-next" aria-label="Próximo produto">→</button>
                </div>
                <div class="deck-sheet" id="deck-details-sheet" aria-hidden="true">
                    <button type="button" class="deck-sheet-backdrop" id="deck-details-backdrop" aria-label="Fechar detalhes"></button>
                    <div class="deck-sheet-panel" role="dialog" aria-modal="true" aria-labelledby="deck-details-title">
                        <div class="deck-sheet-handle" aria-hidden="true"></div>
                        <div class="deck-sheet-top">
                            <h2 id="deck-details-title">Detalhes</h2>
                            <button type="button" class="deck-sheet-close" id="deck-details-close">Fechar</button>
                        </div>
                        <p class="deck-sheet-meta" id="deck-details-category"></p>
                        <div id="deck-details-body"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sheet" id="sale-success-sheet" hidden>
            <div class="sheet-header">
                <div class="sheet__handle"></div>
                <p class="sheet__section-title sale-success__kicker">VENDA CONFIRMADA</p>
                <h3 class="sheet__title" id="sale-success-title">Venda realizada!</h3>
                <p class="card__title" id="sale-success-label">Venda Expandor</p>
                <p class="card__meta" id="sale-success-commission"></p>
                <p class="card__meta" id="sale-success-status"></p>
            </div>
            <div class="sheet-body sale-success-actions">
                <p class="handoff-hint" id="sale-success-handoff-hint" hidden></p>
                <button type="button" class="btn btn-success btn-block" id="sale-success-whatsapp">Enviar para o escritório</button>
                <button type="button" class="btn btn-info btn-block" id="sale-success-copy">Copiar mensagem</button>
                <button type="button" class="btn btn-ghost btn-block" id="sale-success-view">Ver mensagem</button>
            </div>
            <div class="sheet-footer">
                <button type="button" class="btn btn-ghost btn-block" id="sale-success-close">Voltar ao mapa</button>
            </div>
        </div>

        <div class="sheet" id="handoff-message-sheet" hidden>
            <div class="sheet-header">
                <div class="sheet__handle"></div>
                <h3 class="sheet__title">Mensagem ao escritório</h3>
            </div>
            <div class="sheet-body">
                <pre class="handoff-message" id="handoff-message-text"></pre>
            </div>
            <div class="sheet-footer">
                <div class="sheet-actions" style="grid-template-columns:1fr 1fr 1fr">
                    <button type="button" class="btn btn-info" id="handoff-copy">Copiar</button>
                    <button type="button" class="btn btn-success" id="handoff-whatsapp">Enviar no WhatsApp</button>
                    <button type="button" class="btn btn-ghost" id="handoff-close">Fechar</button>
                </div>
            </div>
        </div>

        <div id="reward-overlay" hidden>
            <div class="login-card" style="text-align:center">
                <h2 class="pane-title">Comissão recebida!</h2>
                <p class="stat-card__value" id="reward-amount">R$ 0,00</p>
                <button class="btn btn-primary btn-block" id="reward-close" type="button">Ok</button>
            </div>
        </div>
        <audio id="commission-audio" src="./sounds/commission-coins.wav" preload="auto"></audio>
    </div>
    <script src="./runtime-config.js?v=${shellVersion}"></script>
    <script src="./vendor/seller-app.js?v=${shellVersion}"></script>
</body>
</html>
`;

writeFileSync(join(outDir, 'index.html'), html);
writeFileSync(join(outDir, 'shell-build.json'), JSON.stringify({
    version: shellVersion,
    built_at: buildStamp,
    flow: 'field-visit-parity-v2',
}, null, 2));
console.log('capacitor-shell ready:', outDir);
console.log('capacitor-shell build stamp:', shellVersion, buildStamp);
console.log('capacitor-shell runtime-config.js baked with API base');
