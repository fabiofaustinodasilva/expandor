# PLANS-FEATURES

## Três camadas atuais

| Camada | Storage | Exemplo |
|--------|---------|---------|
| PlanCatalog | `plans.features` JSON | `crm`, `ai`, `whatsapp`, `stock`, `finance`, `api`, `white_label` |
| Plan limits | `plan_features` | `users`, `messages`, quotas |
| FeatureFlag | `feature_flags` + `company_feature_flags` | `ai.enabled`, `whatsapp.enabled`, `mobile.enabled` |

## NavVisibility

Módulo exige: permission ∧ flag? ∧ plan_feature?  
Mapa hoje: só `maps.view` — **sem** feature de integração.

Integrações nav: só `integrations.view`.

## Onde cadastrar `integrations.google_maps`

**Recomendado (fase implementação futura):**

1. **FeatureFlag** `integrations.google_maps` (estilo pontilhado já usado)  
2. **PlanCatalog** chave curta `google_maps` (ou `maps_premium`) em `plans.features` para “plano mínimo Pro”  
3. Gate: `plan_feature` + flag override por empresa  

Não criar nestes docs/sprint.

## Liberar por plano
Admin Platform → Plans → checkbox no `PlanCatalog` / seeder.  
Pro/Enterprise: true; Basic: false.

## Override
`CompanyFeatureFlag` pode forçar on/off sem mudar plano (já existe).  
Upgrade/downgrade **não limpa** overrides hoje — documentar risco.

## Impedir empresa sem plano
`NavVisibility` + policy na página Integrações + `IntegrationResolver` recusa provider premium.

## Seller herda
Sem UI. Resolver lê `company_id` do tenant; mapa injeta provider resultante.

## Upgrade libera configuração
Troca `subscription.plan_id` → novo `hasCatalogFeature('google_maps')` → UI mostra “Configurar”.  
Invalidar cache do resolver.
