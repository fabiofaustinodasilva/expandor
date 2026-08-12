# Auditoria de APIs existentes (8.2.34)

| Necessidade | Origem atual | Classificação | Decisão |
|---|---|---|---|
| Auth login/me/logout | `/api/mobile/v1/login\|me\|logout` | READY | Reutilizar 8.2.33 |
| Dashboard Seller | `/api/mobile/v1/dashboard` | READY | Mantido; Resultado usa stats existentes |
| Campaigns / campaign markers | `/api/mobile/v1/campaigns*` | READY | Reutilizar para campanha ativa |
| Visits store legado | `POST /api/mobile/v1/visits` | READY | Mantido; app novo usa `/points/{id}/visits` |
| Pending sync | `/api/mobile/v1/pending-sync` | READY | Placeholder; fila ainda não existe |
| Markers | `MapQueryService` + `MapMarkersRequest` | REUSE WITH ADAPTER | Novo GET `/markers` |
| Map config | `MapFrontendConfigBuilder` | REUSE WITH ADAPTER | Bootstrap + `/map/config` |
| Create/detail point | `PropertyService` / `ResidentService` / `CustomerQueryService` | REUSE WITH ADAPTER | `/points` |
| Visits / statuses | `VisitService` + `StoreVisitRequest` | REUSE WITH ADAPTER | `/points/{id}/visits\|sales` |
| Follow-up / agenda | `VisitService::scheduleFollowUp` + `CompleteFollowUpAction` | REUSE WITH ADAPTER | `/agenda` + complete |
| Products | `ProductCatalogService::activeCatalogForSeller` | REUSE WITH ADAPTER | `/products` |
| Commissions | `SalesCommissionRepository` | REUSE WITH ADAPTER | `/commissions` (self) |
| Sale / items / commission calc | `VisitService` + `GenerateVisitCommissionAction` | REUSE WITH ADAPTER | Não recalcular no app |
| Territory cities/sectors | `TerritoryRepository` | REUSE WITH ADAPTER | `/territory` |
| Customers | domínio = Property | REUSE WITH ADAPTER | Sem API paralela `customers` |
| Presentation “Apresentar” | Blade Sales App | WEB-ONLY | Não implementado no app |
| Campaign CRUD | web/manager | WEB-ONLY | Fora |
| Commission approve/pay | manager | WEB-ONLY | Seller vê as próprias |
| Google server key / SMTP / APP_KEY | config | WEB-ONLY | Nunca no bootstrap |
| `@capacitor/geolocation` | package.json | MISSING → feito | Plugin oficial 7.x, foreground only |
| Offline / SQLite / sync | — | MISSING | Explicitamente não nesta sprint |

Não foram duplicadas regras de `MapMarkerColor`, comissão, tenancy ou sessão única.
