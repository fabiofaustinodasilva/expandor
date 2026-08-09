# FALLBACK

## MapIntegrationResolver

```
entitled? → integration exists? → enabled? → connected? → has browser key?
qualquer NÃO → leaflet_osm
SIM → provider lógico google_maps
```

`visualProvider()` **sempre** `leaflet_osm` nesta sprint (mapa produção intacto).

## Cache

Chave: `integration.map.{company_id}` (5 min)  
Invalidação: save/test/disconnect + change plan (platform + self-serve upgrade/downgrade)

## Fallback absoluto

Sem integração / Google / key / plano premium → Leaflet continua. Não bloqueia mapa, GPS, visita, venda.
