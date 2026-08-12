# OFFLINE-SHELL

## Critério desta sprint

Sem internet, o **shell base abre**: branding Expandor, JS local executa, sem CDN crítica.

Mapa/tiles/API **podem falhar**. Esperado. Offline real / SQLite / sync **não** implementados.

## Como

- Tailwind, Lucide, Leaflet, Cluster, GoogleMutant no IIFE local.
- Shell gerado com `./vendor/seller-app.js` e `.css` relativos.
- Google Maps JS **não** é necessário para o shell técnico.

## Fallback

`createIcons()` em try/catch. Fontes = system stack. Se Maps JS remoto faltar, o app web cai para Leaflet OSM (já existente); o shell nem inicializa mapa.

## O que não fazer

Não introduzir Service Worker. Não fingir cache de tiles.
