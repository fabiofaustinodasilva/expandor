# FALLBACK — Sprint 8.2.22

## Preload
Se o resolver / `MapFrontendConfig` retornar `leaflet_osm`:
- HTML **não** inclui script Google nem GoogleMutant
- Nenhum request Google
- Nenhuma browser key no HTML

## Runtime
Se o provider desejado for `google_maps` e falhar (timeout, script, init, QA force):
1. `destroyCurrentProvider()` limpa layers GoogleMutant
2. Anexa `leaflet_osm`
3. Toast: “Mapa padrão ativado temporariamente.”
4. POST throttled em `/map/provider-fallback` (sem key)

## Anti double-init
- Token `basemapInitToken` invalida callbacks atrasados
- `destroy` antes de recriar
- Force failure (`?force_google_failure=1`) **somente** `local` / `testing`

## Preservação
Markers/clusters/filtros/GPS continuam no Leaflet host; só o basemap troca.
