# AUDIT — Área do Vendedor (estado real 8.2.30.1)

## Superfície

Seller = Blade em `layouts.operational` + AJAX de mapa (sessão + CSRF).  
Rail: `ClientNav::sellerRail()` — Mapa, Agenda, Clientes, Resultado, Comissão, Apresentar, Perfil, Mais, Sair.

Há um segundo shell (`layouts.sales-app`, start `/app`) com PWA stub. **Não** é o rail do vendedor de campo.

Login / forgot / reset = HTML standalone (não `layouts.guest`).

## Auth / sessão / CSRF / Sanctum

| Item | Estado |
|------|--------|
| Session | cookie `expandor-session`, `SameSite=lax`, `SESSION_DOMAIN=null`, Secure unset |
| CSRF | meta + cookie `XSRF-TOKEN`; API webhooks except; mapa usa ambos |
| Sanctum | tokens `api` e `mobile`; `expiration=null`; stateful = localhost |
| Single session | `session_version` só com cookie; **Bearer ignora** (`!$request->hasSession()`) |
| Password reset | e-mail → `APP_URL/redefinir-senha/{token}` → login (sem deep link) |
| CORS | **`config/cors.php` ausente** → HandleCors não aplica paths |
| Presence | `presence.touch` só no grupo **web**; API não atualiza `last_seen_at` |

## CDN (seller-crítico)

| Asset | Onde |
|-------|------|
| `cdn.tailwindcss.com` | `layouts/operational.blade.php` |
| `unpkg.com/lucide@0.469.0` | operational + app |
| Leaflet 1.9.4 + MarkerCluster 1.5.3 | `maps/index.blade.php` |
| GoogleMutant 0.14.1 + Maps JS | mapa se Google visual |
| OSM / Esri tiles | `map-provider.js` (runtime) |

Locais: `client-ui.css`, `operational-map.js`, `map-provider.js`, `field-offline-queue.js`, `client-mobile.js`.  
**Não há cópia local de Leaflet/Lucide/Tailwind.**

## Mapa / GPS

- `navigator.geolocation.getCurrentPosition` (sem watch)
- Offline tiles: **não**
- Fila: `localStorage['expandor.field.offline.queue.v1']` → POST web CSRF (`point.create`, `point.update`, `visit.create`, `first_approach`)
- Markers: `GET /api/v1/maps/markers` (Sanctum cookie no browser)

## Storage JS

- localStorage: fila offline + tips
- sessionStorage: reward de comissão, rota, produto do contrato
- Sem IndexedDB / SQLite

## PWA / Capacitor

`public/manifest.webmanifest` — `start_url: /app`, `icons: []`.  
Sem service worker (não há dois caches concorrentes hoje).  
Sem Capacitor em `package.json`. Vite existe mas o mapa não usa `@vite`.

## Navegação app (sem redesign)

Bottom nav futuro = rail seller: Mapa, Agenda, Clientes, Resultado, Comissão, Mais.  
Apresentar pode ficar em Mais. Academia: preferir `sales-app.training`, não `layouts.app`.

## Links externos

`wa.me`, `tel:`, Google Maps `dir` → no Capacitor abrir app do sistema (não `_blank` WebView).

## Keyboard / safe-area / back

- Sheets: `100dvh` + `safe-area-inset` em `client-ui.css` / mapa
- Operational **sem** `viewport-fit=cover` (risco notch + teclado)
- Login: `100vh`, sem inset
- Android back (8.2.34): sheet → fecha; drawer → fecha; mapa root → não sair

## Feature config pública

Reusar `GET /api/v1/branding` + entitlements (mapa Google sim/não) **sem** API keys secretas. Browser key Google só no WebView se provider Google.

## Logging

App: ring buffer local limitado. Backend: audit já existente. Crash: Sentry futuro, sem PII.

## Lifecycle campo

Rede fraca, Wi-Fi↔4G, lock screen, background: fila não flush em background no MVP; sync no foreground `online`. Presence só foreground.

## APIs

Ver [API-INVENTORY.md](./API-INVENTORY.md).

## Camera / files

Seller de campo não depende de câmera no mapa. Defer Capacitor Camera.
