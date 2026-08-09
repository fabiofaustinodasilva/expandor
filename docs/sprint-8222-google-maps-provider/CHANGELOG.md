# CHANGELOG — Sprint 8.2.22

## Added
- `MapFrontendConfig` + `MapFrontendConfigBuilder` (browser key live, tenant-scoped)
- `MapProviderRuntimeReporter` + `POST /map/provider-fallback`
- GoogleMutant provider em `public/js/map-provider.js`
- Runtime fallback Leaflet em `operational-map.js`
- QA `?force_google_failure=1` (local/testing only)
- Docs em `docs/sprint-8222-google-maps-provider/`
- `Sprint8222GoogleMapsProviderTest`

## Changed
- `MapProviderDecision::visualProvider()` retorna o provider lógico (Google quando connected)
- Blade do mapa carrega Maps JS + GoogleMutant somente se `usesGoogleVisual()`
- UI Google Maps: orientação de referrers / APIs / billing / limite do Testar conexão

## Migrations
Nenhuma.
