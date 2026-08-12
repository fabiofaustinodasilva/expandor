# Sprint 8.2.32 — Capacitor bootstrap + assets locais

Fundação do app Expandor (estratégia C — híbrido progressivo).

**Não é o app pronto.** Auth mobile, device binding, offline, SQLite, sync, Idempotency-Key, APIs seller faltantes, APK e lojas ficam para sprints seguintes.

## Entrega

- Inventário de assets externos classificado (A/B/C).
- Tailwind / Lucide / Leaflet / MarkerCluster / GoogleMutant locais via Vite IIFE (`public/vendor/expandor`).
- Capacitor core + CLI + plataformas empacotadas.
- Shell mínimo em `public/capacitor-shell` (gerado no build).
- Safe-area + `viewport-fit=cover` no layout operacional e no shell.
- Zero migrations. Web atual preservada (sem CDN crítica no seller).

## Branch / commit

- Branch: `feature/sprint-8232-capacitor-bootstrap-assets`
- Commit alvo: `feat: bootstrap Capacitor shell and local frontend assets`
- Sem push / sem merge.

## Como buildar

```
npm install
npm run build
npx cap sync
```

Android: ver `ANDROID.md`. iOS: ver `IOS.md` (Windows não gera Xcode).
