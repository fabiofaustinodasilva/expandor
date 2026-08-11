# ASSETS-CDN

## Inventário

| Dependência | URL | Risco app |
|-------------|-----|-----------|
| Tailwind Play | `https://cdn.tailwindcss.com` | Alto — bloqueia WebView/produção |
| Lucide | `https://unpkg.com/lucide@0.469.0` | Médio |
| Leaflet | `unpkg.com/leaflet@1.9.4` | Alto (mapa) |
| MarkerCluster | `unpkg.com/leaflet.markercluster@1.5.3` | Alto |
| GoogleMutant | `unpkg.com/leaflet.gridlayer.googlemutant@0.14.1` | Alto se Google |
| Google Maps JS | `maps.googleapis.com/maps/api/js` | Alto; precisa rede + key |
| OSM / Esri tiles | runtime | Sempre online |

`package.json` já tem **Tailwind 4 + Vite**, mas o layout operacional **não usa** `@vite` — usa Play CDN.

## Plano (8.2.32)

1. Vendor Leaflet + MarkerCluster + GoogleMutant em `public/vendor/leaflet/` (cópia versionada, SRI interno).
2. Lucide: build local ou sprite SVG já usado no rail.
3. Tailwind: compilar utilities do mapa via Vite **ou** extrair classes usadas (mapa é o maior consumidor). Não deixar Play CDN.
4. Google JS permanece remoto (licença/key) — documentar “mapa Google exige rede”.

**Não feito nesta sprint:** troca de CDN altera o mapa visual validado (8.2.29). Slice próprio + regressão Maps.
