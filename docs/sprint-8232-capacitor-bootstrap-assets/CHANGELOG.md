# CHANGELOG — 8.2.32

## Added

- Capacitor (`@capacitor/core`, CLI, android, ios package) + `capacitor.config.ts`.
- Vite seller IIFE (`vite.seller.config.js`) e `scripts/prepare-capacitor-shell.mjs`.
- Shell mínimo Expandor em `public/capacitor-shell` (build).
- npm scripts `cap:sync`, `cap:android`, `cap:open:android`, `build:seller`.
- `Sprint8232CapacitorBootstrapAssetsTest`.
- Documentação em `docs/sprint-8232-capacitor-bootstrap-assets/`.

## Changed

- Layout operacional: Tailwind/Lucide locais; `viewport-fit=cover`.
- Mapa: Leaflet/Cluster/Mutant locais; Google Maps JS permanece remoto se entitled.
- `layouts.app`: Lucide local.
- Safe-area tokens em `client-ui.css`.
- `.gitignore` Capacitor/caches/keystores.
- Testes 8.2.31 e 8.2.10 alinhados ao vendor local.

## Not changed

- Auth mobile, device binding, offline, SQLite, sync, Idempotency-Key.
- APIs seller faltantes.
- Migrations (zero).
- Publicação de loja / signing.
- PWA service worker.
