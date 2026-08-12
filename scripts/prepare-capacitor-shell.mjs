/**
 * Copies the seller IIFE vendor into public/capacitor-shell
 * with relative URLs (Capacitor WebView / file).
 */
import { cpSync, existsSync, mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const vendor = join(root, 'public/vendor/expandor');
const outDir = join(root, 'public/capacitor-shell');

if (!existsSync(join(vendor, 'seller-app.js')) || !existsSync(join(vendor, 'seller-app.css'))) {
    throw new Error('Missing public/vendor/expandor/seller-app.{js,css}. Run the seller Vite build first.');
}

rmSync(outDir, { recursive: true, force: true });
mkdirSync(outDir, { recursive: true });
cpSync(vendor, join(outDir, 'vendor'), { recursive: true });

const apiBase = String(process.env.CAP_API_URL || process.env.APP_URL || '').replace(/\/$/, '');
let connectSrc = "'self'";
try {
    if (apiBase) {
        const origin = new URL(apiBase).origin;
        connectSrc = `'self' ${origin}`;
    }
} catch {
    connectSrc = "'self'";
}

const csp = [
    "default-src 'self'",
    "script-src 'self'",
    "style-src 'self' 'unsafe-inline'",
    "img-src 'self' data:",
    `connect-src ${connectSrc}`,
    "font-src 'self' data:",
    "object-src 'none'",
    "base-uri 'self'",
    "frame-ancestors 'none'",
].join('; ');

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
    <div class="card" id="screen-app" hidden>
        <h1 class="brand-hero__wordmark">Expandor</h1>
        <p class="brand-hero__welcome" id="signed-hello">Sessão ativa</p>
        <p class="signed-meta" id="signed-meta"></p>
        <button class="btn-ghost" id="logout-button" type="button">Sair</button>
    </div>
    <script>
        window.EXPANDOR_API_BASE = ${JSON.stringify(apiBase)};
        window.EXPANDOR_WEB_ORIGIN = ${JSON.stringify(apiBase)};
        window.EXPANDOR_APP_VERSION = "8.2.33";
    </script>
    <script src="./vendor/seller-app.js"></script>
</body>
</html>
`;

writeFileSync(join(outDir, 'index.html'), html);
console.log('capacitor-shell ready:', outDir);
