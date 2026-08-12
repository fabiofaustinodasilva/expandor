# Inventário `/api/mobile/v1`

## Reutilizadas (8.2.33 e anteriores)

| METHOD | PATH | AUTH | NOTA |
|---|---|---|---|
| POST | `/login` | público + throttle:login | device_id obrigatório |
| GET | `/me` | sanctum + device + seller | |
| POST | `/logout` | sanctum + device + seller | |
| GET | `/dashboard` | idem | stats existentes |
| GET | `/campaigns` | idem | |
| GET | `/campaign/{campaign}/properties` | idem | |
| GET | `/campaign/{campaign}/markers` | idem | |
| POST | `/visits` | idem | legado |
| GET | `/pending-sync` | idem | placeholder |

## Criadas nesta sprint (adapters)

| METHOD | PATH | PERMISSION |
|---|---|---|
| GET | `/bootstrap` | `sales_app.access` |
| GET | `/map/config` | `sales_app.access` |
| GET | `/markers` | `sales_app.access` + `maps.view` |
| GET | `/points` | `sales_app.access` |
| POST | `/points` | create Property + throttle 60/min |
| GET | `/points/{point}` | view Property |
| POST | `/points/{point}/visits` | create Visit + throttle 60/min |
| POST | `/points/{point}/sales` | mesmo fluxo de visita venda |
| GET | `/agenda` | seller self |
| POST | `/follow-ups/{followUp}/complete` | complete FollowUp |
| GET | `/products` | catálogo ativo |
| GET | `/commissions` | `viewAny` SalesCommission (self no service) |
| GET | `/results` | dashboard + summary existente |
| GET | `/territory` | cidades/setores ativos |

Todas autenticadas passam por `auth:sanctum`, `seller.single-session`, tenancy e `permission:sales_app.access`.
