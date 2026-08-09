# FALLBACK — Sprint 8.2.22

## Preload
Se o resolver / `MapFrontendConfig` retornar `leaflet_osm`:
- HTML **não** inclui script Google nem GoogleMutant
- Nenhum request Google
- Nenhuma browser key no HTML

## Runtime
Sempre monta **Leaflet+OSM primeiro** (canvas nunca fica sem base layer).

Se o provider desejado for `google_maps`:
1. OSM provisório já está no mapa
2. Aguarda Maps JS + GoogleMutant
3. Em sucesso: destroy OSM → monta GoogleMutant
4. Em timeout/script/init error: **mantém OSM** + toast + report
5. Em `gm_authFailure` (key/referrer/billing): destroy Google → remonta OSM + toast

Toast: “Mapa padrão ativado temporariamente.”
POST throttled em `/map/provider-fallback` (sem key)

## Causa de blank canvas (pré-fix)
Com Google desejado, o init antigo **esperava** o script Google **sem** base layer.
Se Mutant era criado sem throw mas tiles falhavam (auth), o mapa ficava azul-escuro
permanentemente sem fallback.

## Anti double-init
- Token `basemapInitToken` invalida callbacks atrasados
- `destroy` antes de recriar
- Force failure (`?force_google_failure=1`) **somente** `local` / `testing`

## Preservação
Markers/clusters/filtros/GPS continuam no Leaflet host; só o basemap troca.
