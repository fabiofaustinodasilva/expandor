# Hotfix — Mapa sem tiles + logo + Apresentar produtos

## Causa raiz (tiles cinza)

**CSP meta do shell Capacitor** usava wildcards (`https://*.tile.openstreetmap.org`) que o **WebView Android (Xiaomi/Chrome)** não aplica de forma confiável para `<img>` de tiles Leaflet.

Leaflet estava correto (`L.tileLayer` OSM + Esri); requisições de tile eram **bloqueadas por img-src**, resultando em mapa cinza com controles visíveis.

## Correção

Allowlist **explícita** em `scripts/capacitor-map-csp-hosts.mjs`:

| Provider | Hosts |
|----------|--------|
| OpenStreetMap | `a/b/c.tile.openstreetmap.org`, `tile.openstreetmap.org` |
| Esri satélite | `server.arcgisonline.com`, `services.arcgisonline.com`, `tiles.arcgisonline.com`, `basemaps.arcgis.com` |
| Google (entitled) | `maps.googleapis.com`, `maps.gstatic.com`, `khms0-3.googleapis.com`, `mt0-3.google.com` |

- `img-src`: self + data + blob + hosts acima + origem API
- `connect-src`: self + origem API + mesmos hosts de tile (diagnóstico/XHR)
- **Sem** `img-src *` / `connect-src *`

## Logo quebrada

- Logo copiada para **`vendor/exp-vendedor-logo.svg`** (sempre presente no bundle Capacitor)
- Header/login usam `./vendor/exp-vendedor-logo.svg`
- `prepare-capacitor-shell.mjs` falha se `logo-exp.svg` ausente

## Apresentar produtos no mapa

- Botão **Apresentar produtos** no canto inferior esquerdo (`#map-present-products`)
- Abre `PresentationScreen` (API `/api/mobile/v1/products`)
- Opção permanece também em **Mais**

## QA Xiaomi (checklist)

1. Login
2. Mapa exibe ruas (tiles OSM)
3. Meu Local
4. Camadas: Rua / Satélite
5. Pins
6. Apresentar produtos no mapa
7. Logo no header
8. Sem imagens quebradas

## Debug remoto

Console: `[EXP Vendedor] tile error` se tile falhar.  
Network: filtrar `tile` / `.png` — status 200 após hotfix.
