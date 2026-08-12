# Mapa mobile

Leaflet + MarkerCluster continuam o motor. Não há mapa nativo nesta sprint.

- `MapAdapter` inicializa, centra, renderiza markers, recentraliza GPS e monta bbox.
- House pins / clusters / rua-satélite / fallback Google visual permanecem regras de frontend já existentes no web; o shell MVP usa OSM tiles.
- Config pública vem de `MapFrontendConfigBuilder` / `MapIntegrationResolver`. Entitlement não é reimplementado.
- Browser key Google só entra no JSON se a empresa estiver entitled + configurada.
- Attribution: OSM ou Google conforme provider.
- Viewport: app envia bbox quando zoom ≥ 10. Sem bbox o backend aplica FieldOps (seller: próprios).
