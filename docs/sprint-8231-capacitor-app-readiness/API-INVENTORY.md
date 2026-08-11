# API-INVENTORY

Base futura: `https://{host}/api/v1` e `https://{host}/api/mobile/v1`.

## READY

| Endpoint | Uso |
|----------|-----|
| `POST /api/v1/auth/login` | token genérico |
| `POST /api/mobile/v1/login` | seller campo (`sales_app.access`) |
| `GET/POST …/me` `…/logout` | sessão API |
| `GET /api/v1/maps/markers` | mapa operacional |
| `GET /api/v1/branding` | tema |
| `GET /api/mobile/v1/campaigns` + properties + markers | recorte campanha |
| `POST /api/mobile/v1/visits` | visita rápida |
| `GET /api/mobile/v1/dashboard` | 3 contadores |
| `GET /api/mobile/v1/pending-sync` | stub |

## PARTIAL

| Ação | Hoje | Gap |
|------|------|-----|
| Visita/venda mapa | `POST /map/campaigns/{id}/visits` CSRF | precisa Bearer + idempotency + reward payload |
| Markers no app | API existe | mapa web usa cookie |
| Dashboard | Blade Resultado vs 3 counts | métricas 8.2.30 não estão na API |

## MISSING (seller diário)

Criar ponto / first-approach / adjust / destroy ponto  
Completar follow-up (pode incluir venda)  
Lista agenda  
Clientes index/show  
Comissões index/summary  
Catálogo / apresentação  
Perfil GET/PATCH  
Presence ping  
Password reset API  
Feature/entitlements públicos (sem secrets)  
Idempotency header  

## Contrato futuro (não refatorar agora)

```
{ "data": {}, "errors": [], "meta": {} }
```

401 `code: session_replaced` já existe no middleware web JSON — reutilizar na API.
