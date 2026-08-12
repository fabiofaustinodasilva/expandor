/**
 * Copies the seller IIFE vendor into public/capacitor-shell
 * with relative URLs (Capacitor WebView / file).
 */
import { cpSync, existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { MAP_TILE_CSP_HOSTS } from './capacitor-map-csp-hosts.mjs';

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

const apiBase = String(process.env.CAP_API_URL || process.env.APP_URL || '').replace(/\/$/, '');
if (!apiBase) {
    throw new Error(
        'CAP_API_URL (or APP_URL) is required to bake Expandor shell API base. '
        + 'Example: CAP_API_URL=https://seu-dominio.com npm run build',
    );
}

let connectSrc = "'self'";
let imgSrc = ["'self'", 'data:', 'blob:', ...MAP_TILE_CSP_HOSTS].join(' ');
try {
    const origin = new URL(apiBase).origin;
    connectSrc = `'self' ${origin} ${MAP_TILE_CSP_HOSTS.join(' ')}`;
    imgSrc += ` ${origin}`;
} catch {
    throw new Error(`Invalid CAP_API_URL/APP_URL for Capacitor shell: ${apiBase}`);
}

console.log('capacitor-shell API base:', apiBase);

const csp = [
    "default-src 'self'",
    "script-src 'self'",
    "style-src 'self' 'unsafe-inline'",
    `img-src ${imgSrc}`,
    `connect-src ${connectSrc}`,
    "font-src 'self' data:",
    "media-src 'self'",
    "object-src 'none'",
    "base-uri 'self'",
    // frame-ancestors is ignored on <meta> CSP (browser warning). Keep framing controls
    // on HTTP responses when the shell is hosted; Capacitor WebView uses local assets.
].join('; ');

const runtimeConfig = [
    `window.EXPANDOR_API_BASE = ${JSON.stringify(apiBase)};`,
    `window.EXPANDOR_WEB_ORIGIN = ${JSON.stringify(apiBase)};`,
    'window.EXPANDOR_APP_VERSION = "8.2.34";',
    '',
].join('\n');

writeFileSync(join(outDir, 'runtime-config.js'), runtimeConfig);

const shellVersion = String(process.env.EXPANDOR_SHELL_VERSION || '8.2.34').trim();
const buildStamp = new Date().toISOString();

const html = `<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0B1F3A">
    <meta name="exp-shell-version" content="${shellVersion}">
    <meta name="exp-shell-built-at" content="${buildStamp}">
    <meta http-equiv="Content-Security-Policy" content="${csp}">
    <title>EXP Vendedor</title>
    <link rel="stylesheet" href="./vendor/seller-app.css?v=${shellVersion}">
</head>
<body data-shell-version="${shellVersion}" data-shell-built-at="${buildStamp}">
    <div class="login-card" id="screen-login">
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
        <div id="net-banner" hidden>Sem conexão</div>
        <div id="app-toast" class="banner" hidden></div>

        <main class="app-main">
            <section class="pane" id="pane-map">
                <div id="seller-map"></div>
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
                    <button class="map-fab map-fab--gps" id="gps-btn" type="button">
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
                    <li><button class="menu-list__item" id="menu-products" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                        Apresentar produtos
                    </button></li>
                    <li><button class="menu-list__item" id="menu-account" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Minha conta
                    </button></li>
                    <li><button class="menu-list__item" id="menu-about" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        Sobre o Expandor
                    </button></li>
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
                <button class="btn btn-accent btn-block" id="point-register-visit" type="button">Registrar visita</button>
                <button class="btn btn-ghost btn-block" id="point-sheet-close" type="button">Fechar</button>
            </div>
        </div>

        <div class="sheet sheet--scroll" id="visit-sheet" hidden>
            <div class="sheet__handle"></div>
            <p class="sheet__section-title">RESULTADO DA ABORDAGEM</p>
            <h3 class="sheet__title" id="visit-sheet-title">Registrar visita</h3>
            <p class="card__meta" id="visit-sheet-subtitle">Como foi a abordagem?</p>
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
                <div class="sheet-actions" style="grid-template-columns:1fr 1fr">
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

            <div class="visit-block" id="visit-sale-block" hidden>
                <p class="sheet__section-title">CONFIRMAR VENDA</p>
                <p class="card__meta">Revise os produtos antes de finalizar.</p>
                <label class="field-label" for="sale-name">Nome completo <span id="sale-name-required" class="req-star" hidden>*</span></label>
                <input class="field-input" id="sale-name" placeholder="Nome completo" autocomplete="name">
                <label class="field-label" for="sale-phone">Telefone <span id="sale-phone-required" class="req-star" hidden>*</span></label>
                <input class="field-input" id="sale-phone" placeholder="Telefone" inputmode="tel" autocomplete="tel">
                <label class="field-label" for="sale-whatsapp">WhatsApp <span id="sale-whatsapp-required" class="req-star" hidden>*</span></label>
                <input class="field-input" id="sale-whatsapp" placeholder="WhatsApp" inputmode="tel">
                <div class="sheet-actions" style="grid-template-columns:1fr 1fr">
                    <div>
                        <label class="field-label" for="sale-document">CPF <span id="sale-document-required" class="req-star" hidden>*</span></label>
                        <input class="field-input" id="sale-document" placeholder="CPF">
                    </div>
                    <div>
                        <label class="field-label" for="sale-rg">RG <span id="sale-rg-required" class="req-star" hidden>*</span></label>
                        <input class="field-input" id="sale-rg" placeholder="RG">
                    </div>
                </div>
                <label class="field-label" for="sale-email">E-mail <span id="sale-email-required" class="req-star" hidden>*</span></label>
                <input class="field-input" id="sale-email" placeholder="E-mail" type="email" autocomplete="email">
                <div class="sale-cart-head">
                    <label class="field-label">Produtos <span id="sale-product-required" class="req-star" hidden>*</span></label>
                    <button type="button" class="btn btn-ghost" id="sale-cart-add">+ Adicionar produto</button>
                </div>
                <div id="sale-cart-lines" class="sale-cart-lines"></div>
                <p class="card__meta" id="sale-cart-empty">Nenhum produto ainda. Toque em “+ Adicionar produto”.</p>
                <div class="sale-cart-total">
                    <span class="muted">Total</span>
                    <strong id="sale-cart-total">R$ 0,00</strong>
                </div>
                <label class="field-label" for="sale-notes">Observações da venda <span id="sale-notes-required" class="req-star" hidden>*</span></label>
                <textarea class="field-textarea" id="sale-notes" placeholder="Observações comerciais desta venda"></textarea>
            </div>

            <div id="visit-error" class="banner" hidden data-kind="error"></div>
            <div class="sheet-actions sheet-actions--sticky">
                <button class="btn btn-primary btn-block" id="visit-submit" type="button">Salvar visita</button>
                <button class="btn btn-ghost btn-block" id="visit-sheet-close" type="button">Cancelar</button>
            </div>
        </div>

        <div class="sheet sheet--scroll" id="create-sheet" hidden>
            <div class="sheet__handle"></div>
            <h3 class="sheet__title">Novo imóvel</h3>
            <p class="card__meta" id="create-location-status">Localização definida ✓</p>
            <span class="location-chip" id="create-location-chip" hidden></span>
            <form id="create-point-form">
                <input type="hidden" id="point-lat">
                <input type="hidden" id="point-lng">
                <div class="sheet__section">
                    <p class="sheet__section-title">ENDEREÇO</p>
                    <label class="field-label">Cidade</label>
                    <select class="field-select" id="point-city" required></select>
                    <label class="field-label">Setor/Bairro</label>
                    <select class="field-select" id="point-sector"></select>
                    <label class="field-label">Rua</label>
                    <input class="field-input" id="point-street" placeholder="Rua" required>
                    <label class="field-label">Número</label>
                    <input class="field-input" id="point-number" placeholder="Número">
                </div>
                <div class="sheet__section">
                    <p class="sheet__section-title">CONTATO</p>
                    <label class="field-label">Nome do contato</label>
                    <input class="field-input" id="point-contact" placeholder="Nome do contato">
                    <label class="field-label">Telefone</label>
                    <input class="field-input" id="point-phone" placeholder="Telefone" inputmode="tel">
                </div>
                <div class="sheet-actions sheet-actions--sticky">
                    <button class="btn btn-primary" type="submit">Salvar imóvel</button>
                    <button class="btn btn-ghost" id="create-point-cancel" type="button">Cancelar</button>
                </div>
            </form>
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
            <header class="presentation-pane__header">
                <h2 class="pane-title" style="margin:0">Apresentar produtos</h2>
                <button class="btn btn-ghost" id="presentation-close" type="button">Fechar</button>
            </header>
            <div class="presentation-pane__body" id="presentation-list"></div>
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
