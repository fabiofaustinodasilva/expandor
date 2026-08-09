# ENTITLEMENTS

## Camadas

1. **PlanCatalog** `google_maps`  
   - Free: false  
   - Professional / Enterprise: true  
2. **FeatureFlag** `integrations.google_maps` (default ON)  
3. **Permission** `integrations.view` / `integrations.manage`

## Gate de configuração (backend)

`IntegrationEntitlementService::allowsGoogleMaps($company)`

- Exige `plan->hasCatalogFeature('google_maps')` **estrito** (sem bypass legado do NavVisibility)
- Exige flag habilitada

Sem entitlement → 403/validation; UI sem formulário de key.

## Downgrade

Resolver deixa de retornar Google imediatamente. Credentials **não** são apagadas automaticamente.
