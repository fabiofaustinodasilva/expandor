/**
 * Copies the seller IIFE vendor into public/capacitor-shell
 * with relative URLs (Capacitor WebView / file).
 */
import { cpSync, existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

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

const apiBase = String(process.env.CAP_API_URL || process.env.APP_URL || '').replace(/\/$/, '');
if (!apiBase) {
    throw new Error(
        'CAP_API_URL (or APP_URL) is required to bake Expandor shell API base. '
        + 'Example: CAP_API_URL=https://seu-dominio.com npm run build',
    );
}

let connectSrc = "'self'";
try {
    const origin = new URL(apiBase).origin;
    connectSrc = `'self' ${origin}`;
} catch {
    throw new Error(`Invalid CAP_API_URL/APP_URL for Capacitor shell: ${apiBase}`);
}

console.log('capacitor-shell API base:', apiBase);

const csp = [
    "default-src 'self'",
    "script-src 'self'",
    "style-src 'self' 'unsafe-inline'",
    "img-src 'self' data: blob: https://*.tile.openstreetmap.org https://tile.openstreetmap.org",
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

const html = `<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0F1117">
    <meta http-equiv="Content-Security-Policy" content="${csp}">
    <title>Expandor</title>
    <link rel="stylesheet" href="./vendor/seller-app.css">
    <style>
        :root {
            --bg: #0F1117;
            --bg-elevated: #171A22;
            --border: #2A3142;
            --text: #F3F5F9;
            --muted: #9AA3B5;
            --accent: #3B82F6;
            --accent-2: #EF4444;
            --button-text: #F8FAFC;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body {
            margin: 0;
            min-height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            display: grid;
            place-items: center;
            padding: calc(1.25rem + env(safe-area-inset-top, 0px))
                calc(1.25rem + env(safe-area-inset-right, 0px))
                calc(1.25rem + env(safe-area-inset-bottom, 0px))
                calc(1.25rem + env(safe-area-inset-left, 0px));
        }
        body.seller-app-mode {
            display: block;
            padding: 0;
            height: 100dvh;
        }
        .card {
            width: min(440px, 100%);
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 1.15rem;
            padding: 2rem 1.75rem 1.75rem;
            text-align: center;
        }
        .brand-hero__wordmark {
            margin: 0;
            font-size: clamp(1.85rem, 5vw, 2.25rem);
            font-weight: 800;
            letter-spacing: 0.04em;
        }
        .brand-hero__welcome {
            margin: 0.85rem 0 0;
            font-size: 1.2rem;
            font-weight: 700;
        }
        .brand-hero__subtitle {
            margin: 0.2rem 0 0;
            color: var(--muted);
            font-size: 0.92rem;
        }
        form { text-align: left; margin-top: 1.35rem; }
        label {
            display: block;
            margin-bottom: 0.35rem;
            color: var(--muted);
            font-size: 0.9rem;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            margin-bottom: 1rem;
            background: #1E2330;
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 0.65rem;
            padding: 0.8rem 0.9rem;
            font-size: 1rem;
        }
        .btn-primary, .btn-ghost {
            display: block;
            width: 100%;
            border: 0;
            border-radius: 0.65rem;
            padding: 0.9rem 1rem;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
        }
        .btn-primary { background: var(--accent); color: var(--button-text); }
        .btn-ghost {
            margin-top: 0.75rem;
            background: transparent;
            color: var(--muted);
            border: 1px solid var(--border);
        }
        .forgot-link {
            display: block;
            margin: 0.85rem 0 0;
            text-align: center;
            color: var(--muted);
            font-size: 0.92rem;
        }
        .banner {
            border-radius: 0.65rem;
            margin: 1rem 0 0;
            padding: 0.75rem 0.85rem;
            font-size: 0.9rem;
            text-align: left;
        }
        .banner[data-kind="error"] {
            color: #FCA5A5;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
        }
        .banner[data-kind="status"] {
            color: #93C5FD;
            background: rgba(59, 130, 246, 0.12);
            border: 1px solid rgba(59, 130, 246, 0.35);
        }
        .signed-meta { color: var(--muted); margin: 0.35rem 0 1.25rem; }
        #screen-app {
            display: flex;
            flex-direction: column;
            width: 100%;
            height: 100dvh;
            background: var(--bg);
        }
        #screen-app[hidden] { display: none !important; }
        .app-header {
            padding: calc(0.75rem + env(safe-area-inset-top, 0px)) 1rem 0.6rem;
            border-bottom: 1px solid var(--border);
        }
        #net-banner, #app-toast {
            margin: 0.5rem 1rem 0;
            padding: 0.55rem 0.75rem;
            border-radius: 0.55rem;
            font-size: 0.88rem;
        }
        #net-banner { background: rgba(239,68,68,.15); color: #FCA5A5; }
        .app-main { flex: 1; min-height: 0; position: relative; }
        .pane { height: 100%; overflow: auto; padding: 0.75rem 1rem 5.5rem; }
        #pane-map { padding: 0; }
        #seller-map { height: 100%; min-height: 280px; }
        .gps-fab, .create-fab {
            position: absolute; right: 1rem; z-index: 500;
            border: 0; border-radius: 999px; padding: 0.7rem 0.95rem;
            background: var(--accent); color: var(--button-text); font-weight: 700;
        }
        .gps-fab { bottom: 6.5rem; }
        .create-fab { bottom: 9.4rem; }
        .seller-nav {
            display: grid; grid-template-columns: repeat(6, 1fr);
            border-top: 1px solid var(--border);
            padding-bottom: env(safe-area-inset-bottom, 0px);
            background: var(--bg-elevated);
        }
        .seller-nav button {
            background: transparent; border: 0; color: var(--muted);
            padding: 0.7rem 0.2rem; font-size: 0.72rem; font-weight: 700;
        }
        .seller-nav button[data-active="1"] { color: var(--accent); }
        .row {
            display: block; width: 100%; text-align: left;
            background: var(--bg-elevated); border: 1px solid var(--border);
            color: var(--text); border-radius: 0.7rem; padding: 0.8rem 0.9rem; margin-bottom: 0.55rem;
        }
        .row span, .muted { color: var(--muted); font-size: 0.86rem; }
        .sheet {
            position: fixed; inset: auto 0 0 0; z-index: 800;
            background: var(--bg-elevated); border-top: 1px solid var(--border);
            padding: 1rem 1rem calc(1rem + env(safe-area-inset-bottom, 0px));
            max-height: 80dvh; overflow: auto;
        }
        .sheet input, .sheet select, .sheet textarea {
            width: 100%; margin-bottom: 0.55rem; background: #1E2330;
            border: 1px solid var(--border); color: var(--text);
            border-radius: 0.55rem; padding: 0.65rem 0.75rem;
        }
        #reward-overlay {
            position: fixed; inset: 0; z-index: 900;
            background: rgba(15,17,23,.82); display: grid; place-items: center;
        }
        #reward-overlay[hidden] { display: none !important; }
    </style>
</head>
<body>
    <div class="card" id="screen-login">
        <header class="brand-hero">
            <h1 class="brand-hero__wordmark">Expandor</h1>
            <p class="brand-hero__welcome">Bem-vindo ao Expandor</p>
            <p class="brand-hero__subtitle">Acesse sua conta</p>
        </header>
        <div id="auth-banner" class="banner" hidden></div>
        <form id="expandor-login-form" autocomplete="on">
            <label for="login-email">E-mail</label>
            <input id="login-email" name="email" type="email" required autofocus autocomplete="username">
            <label for="login-password">Senha</label>
            <input id="login-password" name="password" type="password" required autocomplete="current-password">
            <button class="btn-primary" id="login-submit" type="submit">Entrar</button>
        </form>
        <a class="forgot-link" id="login-forgot" href="/esqueci-minha-senha" data-forgot-password="1">Esqueceu sua senha?</a>
    </div>
    <div id="screen-app" hidden>
        <header class="app-header">
            <p class="brand-hero__welcome" id="signed-hello">Sessão ativa</p>
            <p class="signed-meta" id="signed-meta"></p>
        </header>
        <div id="net-banner" hidden>Offline</div>
        <div id="app-toast" class="banner" hidden></div>
        <main class="app-main">
            <section class="pane" id="pane-map">
                <div id="seller-map"></div>
                <button class="create-fab" id="create-point-open" type="button">Novo ponto</button>
                <button class="gps-fab" id="gps-btn" type="button">GPS</button>
            </section>
            <section class="pane" id="pane-agenda" hidden>
                <h2>Agenda</h2>
                <div id="agenda-list"></div>
            </section>
            <section class="pane" id="pane-clients" hidden>
                <h2>Clientes</h2>
                <input id="clients-q" type="search" placeholder="Buscar nome, telefone ou endereço">
                <div id="clients-list"></div>
            </section>
            <section class="pane" id="pane-results" hidden>
                <h2>Resultado</h2>
                <div id="results-list"></div>
            </section>
            <section class="pane" id="pane-commissions" hidden>
                <h2>Comissão</h2>
                <div id="commissions-list"></div>
            </section>
            <section class="pane" id="pane-more" hidden>
                <h2>Mais</h2>
                <p class="muted">Apresentação de produtos continua no fluxo web nesta sprint.</p>
                <button class="btn-ghost" id="logout-button" type="button">Sair</button>
            </section>
        </main>
        <nav class="seller-nav">
            <button id="nav-map" type="button" data-active="1">Mapa</button>
            <button id="nav-agenda" type="button">Agenda</button>
            <button id="nav-clients" type="button">Clientes</button>
            <button id="nav-results" type="button">Resultado</button>
            <button id="nav-commissions" type="button">Comissão</button>
            <button id="nav-more" type="button">Mais</button>
        </nav>
        <div class="sheet" id="point-sheet" hidden>
            <input type="hidden" id="point-id">
            <h3 id="point-title">Ponto</h3>
            <p class="muted" id="point-meta"></p>
            <a id="point-call" href="#">Ligar</a>
            <a id="point-wa" href="#">WhatsApp</a>
            <label>Campanha</label>
            <select id="visit-campaign"></select>
            <select id="point-campaign" hidden></select>
            <textarea id="visit-notes" placeholder="Observações"></textarea>
            <input id="visit-followup" type="datetime-local">
            <input id="sale-name" placeholder="Nome do cliente">
            <input id="sale-phone" placeholder="Telefone">
            <select id="sale-product"></select>
            <button class="btn-primary" id="visit-interested" type="button">Interessado</button>
            <button class="btn-ghost" id="visit-return" type="button">Retorno</button>
            <button class="btn-ghost" id="visit-no-interest" type="button">Sem interesse</button>
            <button class="btn-primary" id="visit-sale" type="button">Confirmar venda</button>
            <button class="btn-ghost" id="point-sheet-close" type="button">Fechar</button>
        </div>
        <div class="sheet" id="create-sheet" hidden>
            <form id="create-point-form">
                <select id="point-city" required></select>
                <select id="point-sector"></select>
                <input id="point-street" placeholder="Rua" required>
                <input id="point-number" placeholder="Número">
                <input id="point-lat" placeholder="Latitude" required>
                <input id="point-lng" placeholder="Longitude" required>
                <select id="point-status">
                    <option value="new">Novo</option>
                </select>
                <input id="point-contact" placeholder="Nome do contato">
                <input id="point-phone" placeholder="Telefone">
                <textarea id="point-notes" placeholder="Observações"></textarea>
                <button class="btn-primary" type="submit">Salvar ponto</button>
                <button class="btn-ghost" id="create-point-cancel" type="button">Cancelar</button>
            </form>
        </div>
        <div id="reward-overlay" hidden>
            <div class="card">
                <h2>Comissão</h2>
                <p id="reward-amount">R$ 0,00</p>
                <button class="btn-primary" id="reward-close" type="button">Ok</button>
            </div>
        </div>
        <audio id="commission-audio" src="./sounds/commission-coins.wav" preload="auto"></audio>
    </div>
    <script src="./runtime-config.js"></script>
    <script src="./vendor/seller-app.js"></script>
</body>
</html>
`;

writeFileSync(join(outDir, 'index.html'), html);
console.log('capacitor-shell ready:', outDir);
console.log('capacitor-shell runtime-config.js baked with API base');
