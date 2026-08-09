# ROUTES — Seller

Middleware web área cliente: `auth` + `tenancy.initialize` + `tenancy.active`  
(sem `permission:` nas rotas web; gates em controllers/policies)

## Contagens (auditadas)

| Tipo | Qtd aprox. | Notas |
|------|------------|-------|
| Rotas `map.*` | 8 | index + first-approach + visits + points CRUD |
| Rotas `sales-app.*` | 13 | dashboard, campaigns, visits, follow-ups, products, training |
| Rotas `follow-ups.*` + visits follow-ups | 4+ | agenda |
| `commissions.index` (seller) | 1 | products CRUD 403 |
| `customers.*` | 2 | index/show |
| `dashboard` | 1 | |
| `training.*` view | 2+ | manage 403 |
| `crm.*` (seeded) | várias | acessíveis se plan crm |
| `profile.*` | 2 | |
| `operations.more` | 1 | |

**Total rotas seller-relevantes:** ~45–60 (incluindo território/properties/campaigns condicionais).

## Controllers principais

1. `MapController`  
2. `MapFirstApproachController`  
3. `MapVisitController` / `MapPointController`  
4. `SalesAppProductController`  
5. `SalesAppCampaignController` (+ follow-ups/training Sales App)  
6. `FollowUpController`  
7. `SalesCommissionController`  
8. `CustomerController`  
9. `DashboardController`  
10. `MoreController` (links legado não renderizados)  

## Policies / gates relevantes

- `ProductPolicy` — seller view ativos only  
- `VisitPolicy` / `FollowUpPolicy`  
- `SalesCommissionPolicy` — view_self  
- `CustomerPolicy` — escopo próprio  
- Map: `maps.view` + Property/Visit authorize  
- Sales App: `sales_app.access` (+ flag `mobile.enabled` no nav)

## Home pós-login

`LoginController`: se `maps.view` → `map.index`, senão `dashboard`.
