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

const html = `<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0F1117">
    <title>Expandor</title>
    <link rel="stylesheet" href="./vendor/seller-app.css">
    <style>
        html, body {
            margin: 0;
            min-height: 100%;
            background: #0F1117;
            color: #F8FAFC;
            font-family: ui-sans-serif, system-ui, "Segoe UI", sans-serif;
        }
        main {
            display: grid;
            place-items: center;
            min-height: 100dvh;
            padding: calc(1.5rem + env(safe-area-inset-top, 0px))
                calc(1.5rem + env(safe-area-inset-right, 0px))
                calc(1.5rem + env(safe-area-inset-bottom, 0px))
                calc(1.5rem + env(safe-area-inset-left, 0px));
            text-align: center;
        }
        h1 { margin: 0 0 .35rem; font-size: 1.5rem; }
        p { margin: .35rem 0; color: #94A3B8; }
        .ok { color: #34D399; font-weight: 700; }
        code { font-size: .8rem; color: #CBD5E1; }
    </style>
</head>
<body>
    <main>
        <h1>Expandor</h1>
        <p class="ok" id="status">Bootstrap Capacitor OK</p>
        <p>Shell local · sem CDN crítica · assets empacotados</p>
        <p><code id="vendor">carregando vendor…</code></p>
    </main>
    <script src="./vendor/seller-app.js"></script>
    <script>
        (function () {
            var v = window.ExpandorVendor || {};
            var el = document.getElementById('vendor');
            el.textContent = [
                v.leaflet ? 'Leaflet' : 'Leaflet?',
                v.markerCluster ? 'Cluster' : 'Cluster?',
                v.googleMutant ? 'GoogleMutant' : 'GoogleMutant?',
                v.lucide ? 'Lucide' : 'Lucide?'
            ].join(' · ');
        })();
    </script>
</body>
</html>
`;

writeFileSync(join(outDir, 'index.html'), html);
console.log('capacitor-shell ready:', outDir);
