# PROVIDER-DECISION — Sprint 8.2.22

## Alternativas avaliadas

### A) Google Maps JavaScript API como engine visual pura
- Prós: oficial, suporte nativo a Rua/Satélite, branding correto.
- Contras: reescrever markers, clusters, popups, map click, fitBounds e fluxo seller sobre `google.maps.Map`.
- Risco alto de regressão na superfície 8.2.19.

### B) Leaflet + GoogleMutant (Maps JavaScript API) — **ESCOLHIDA**
- Leaflet continua como host do domínio comercial (markers, MarkerCluster, click, GPS pan, filtros).
- GoogleMutant carrega tiles via **Maps JavaScript API** (não via URLs de tile não documentadas).
- Rua = `roadmap`; Satélite = `satellite`.
- Attribution/branding Google permanece no pane do Mutant (ToS).

### C) Outras abordagens
- Tiles Google “diretos” / endpoints não documentados: **rejeitado** (viola ToS).
- Esri dentro do provider Google: **rejeitado** sem necessidade.

## Decisão
Usar **B**. Conformidade > manter Leaflet como motor de tiles, mas Leaflet permanece como host de camadas comerciais.

## Documentação / ToS considerada
- Google Maps Platform / Maps JavaScript API (uso via script oficial `maps.googleapis.com`).
- Leaflet.GoogleMutant: wrapper que instancia Maps JS API (não scrape de tiles).
- Leaflet FAQ / community: não usar tile URLs Google não oficiais.

## Leaflet mantido?
Sim, como host + fallback absoluto (`leaflet_osm`). Em modo Google, OSM/Esri não são o basemap ativo.
