# PROVIDER-ARCHITECTURE

## IntegrationResolver (futuro)

```
resolveMapProvider(Company $company): MapProviderDecision
  1. entitlement: plan/flag permite google_maps?
  2. company_integrations: enabled + status connected?
  3. credentials present + last test ok? (soft)
  4. YES → provider=google (+ public browser config)
  5. NO  → provider=leaflet_osm (default)
```

Cache: `integrations.map.{company_id}` TTL curto; invalidate on save/disconnect/plan change.

## Fallback runtime

Se Google falhar no browser (script error / for development purposes only):

- JS catch → `ExpandorMapProvider.create(..., leaflet_osm)`  
- Toast discreto opcional  
- Mapa **nunca** fica em branco

## ExpandorMapProvider

Já preparado com ponto de troca (`provider: 'google'|'mapbox'|'leaflet_osm'`).  
Implementar adapter sem tocar MapQueryService.

## Admin vs Empresa

| Admin | Empresa |
|-------|---------|
| Catálogo + planos + “empresas conectadas” count | Conectar/testar/desconectar |
| Sem API key completa | Form key + mask |
| Toggle plataforma off → força fallback global | — |

## Categorias futuras da Central

MAPAS · PAGAMENTOS · COMUNICAÇÃO · TERRITÓRIO · ARMAZENAMENTO · IA · APP (push)
