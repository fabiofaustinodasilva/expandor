# Sprint 8.2.22 — Google Maps visual + fallback real

## Objetivo
Ativar Google Maps como basemap visual quando o plano permite, a empresa configurou e a integração está connected/enabled; caso contrário manter Leaflet + OSM/Esri.

## Estratégia (Fase 0)
**B — Leaflet host + GoogleMutant (Maps JavaScript API oficial)**

Ver `PROVIDER-DECISION.md`.

## Escopo
- Provider adapter (`map-provider.js`)
- Config pública segura (`MapFrontendConfig`)
- Fallback runtime → Leaflet + aviso discreto
- Sem redesenho do mapa 8.2.19
- Sem Comissões 2.0
- Zero migrations

## Branch
`feature/sprint-8222-google-maps-provider`

Base: `94a3de4` (8221) + `9558e6c` (hotfix dashboard 403)

## Não fazer
- Push / merge
- Places/Autocomplete
- Cobrança adicional Google
- App Capacitor (apenas readiness)
