# CHANGELOG

## Added

- Migration `company_integrations`
- Domain `App\Domains\Integrations\*`
- `MapIntegrationResolver`, `IntegrationEntitlementService`, `GoogleMapsIntegrationService`, `GoogleMapsConnectionTester`
- Company UI: `/operacao/integracoes` + Google Maps config
- Platform UI: `/platform/integrations`
- Permission `integrations.manage`
- Plan feature `google_maps`
- Feature flag `integrations.google_maps`
- Tests Sprint8221

## Changed

- `PlanCatalog` / `PlanSeeder` / `FeatureFlagSeeder` / `RolePermissionSeeder`
- Plan change invalidates map integration cache
- Platform nav: link Integrações

## Unchanged

- Leaflet map surface / operational-map.js visual provider  
- Mercado Pago architecture  
