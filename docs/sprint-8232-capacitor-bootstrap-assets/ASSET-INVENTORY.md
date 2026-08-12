# ASSET-INVENTORY

Classificação pós-auditoria (grep em blades, JS, CSS, layouts).

| Asset | Origem anterior | Classe | Destino 8.2.32 |
|-------|-----------------|--------|----------------|
| Tailwind Play | `cdn.tailwindcss.com` | **A** precisa ser local | Vite + Tailwind 4 → `seller-app.css` |
| Lucide 0.469.0 | `unpkg.com/lucide@0.469.0` | **A** | npm `lucide@0.469.0` → `window.lucide` |
| Leaflet 1.9.4 | unpkg CSS+JS | **A** | npm `leaflet@1.9.4` → `window.L` |
| MarkerCluster 1.5.3 | unpkg CSS+JS | **A** | npm `leaflet.markercluster@1.5.3` |
| GoogleMutant 0.14.1 | unpkg | **A** (adapter) | npm `leaflet.gridlayer.googlemutant@0.14.1` |
| Leaflet marker PNG | unpkg/dist/images | **A** | empacotado pelo Vite (`assets/`) |
| Google Maps JS API | `maps.googleapis.com` | **B** remoto por natureza | permanece quando `usesGoogleVisual()` |
| OSM tiles | `tile.openstreetmap.org` | **B** | runtime, exige rede |
| Esri World Imagery | Esri | **B** | runtime, exige rede |
| `maps.gstatic.com` | Google | **B** | só com Maps JS |
| fonts.bunny.net | welcome marketing | **C** / web marketing | não no seller/app; app usa system stack |
| Google Fonts | não usado no operacional | — | N/A |
| Service worker | inexistente | **C** | não introduzir |

## Layouts

| Superfície | Antes | Depois |
|------------|-------|--------|
| `layouts.operational` | Tailwind Play + Lucide CDN | `vendor/expandor/seller-app.css` + `.js` |
| `layouts.app` | Lucide CDN | `vendor/expandor/seller-app.js` (sem Tailwind preflight) |
| `layouts.sales-app` | CSS próprio + viewport-fit | inalterado (já local) |
| `welcome` | Vite `app.css` + bunny fonts | inalterado (marketing) |
| Mapa | Leaflet/Cluster/Mutant unpkg | vendors no IIFE; Maps JS remoto se Google |

## Ainda remoto (esperado)

- Google Maps JavaScript API (oficial, key de browser em runtime).
- Tiles OSM / Esri.
- Chamadas API Laravel (ainda não no shell).
