# ENTITLEMENTS

## Camadas

1. **PlanCatalog** `google_maps` (persistido em `plans.features` JSON)  
   - Free: false  
   - Professional / Enterprise: true  
   - Fonte de verdade do Super Admin ao editar o plano
2. **FeatureFlag** `integrations.google_maps` (opcional; default ON quando a row existe)  
   - Kill-switch de plataforma  
   - Se a row **não existir**, o entitlement **não bloqueia** (plano manda)
3. **Permission** `integrations.view` / `integrations.manage`

## Gate de configuração (backend)

`IntegrationEntitlementService::allowsGoogleMaps($company)`

- Exige `plan->hasCatalogFeature('google_maps')` **estrito** (sem bypass legado do NavVisibility)
- Exige flag **somente se** a FeatureFlag ativa existir e estiver desabilitada (global/override)

Sem entitlement → UI “Disponível em plano superior”; formulário de key oculto.

## Hotfix 8.2.22 entitlement

Bug: Super Admin ligava `google_maps` no plano, mas `FeatureFlagService::isEnabled` retornava `false`
quando `integrations.google_maps` ainda não tinha sido seeded → falso “plano superior”.

## Downgrade

Resolver deixa de retornar Google imediatamente. Credentials **não** são apagadas automaticamente.
