# BUILD-PIPELINE

Pipeline único: **Laravel Vite** (welcome/marketing) + **Vite seller IIFE** (operacional/app) + script do shell Capacitor.

Não há segundo bundler (Webpack/esbuild avulso).

## Entrypoints

| Arquivo | Papel |
|---------|--------|
| `resources/css/app.css` + `resources/js/app.js` | Vite Laravel (`vite.config.js`) — welcome |
| `resources/css/seller-app.css` | Tailwind 4 + Leaflet/Cluster CSS + tokens safe-area |
| `resources/js/seller-app.js` | IIFE: Leaflet, Cluster, GoogleMutant, Lucide, globals |
| `resources/js/vendor-icons.js` | Lucide-only (reserva; app usa o IIFE completo sem CSS) |

## Scripts npm

| Script | Função |
|--------|--------|
| `npm run build` | web + seller IIFE + `prepare-capacitor-shell.mjs` |
| `npm run build:web` | só `vite build` |
| `npm run build:seller` | só IIFE em `public/vendor/expandor` |
| `npm run dev` | Vite HMR + watch do seller |
| `npm run cap:sync` | build + `npx cap sync` |
| `npm run cap:android` | `npx cap sync android` |
| `npm run cap:open:android` | abre Android Studio se instalado |

## Saídas (gitignoradas)

- `public/build` — Laravel Vite
- `public/vendor/expandor` — IIFE seller
- `public/capacitor-shell` — shell Capacitor (`webDir`)

## Por que IIFE e não `@vite` no operacional

Scripts do mapa (`operational-map.js`, `map-provider.js`) são clássicos e esperam `window.L` no parse. Módulos ES do Vite são deferred e rodariam **depois** desses scripts. O IIFE no `<head>` restaura o contrato.

## Source maps

Seller build: `sourcemap: false`. Web Vite: padrão atual (não publicar maps sensíveis em produção sem análise).
