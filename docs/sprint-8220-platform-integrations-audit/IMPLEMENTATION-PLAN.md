# IMPLEMENTATION-PLAN

## Fora de escopo desta sprint
Tudo que seja código/migration/SDK.

## Roadmap sugerido (após aprovação)

### Slice 0 — Fundações (docs já feitos)
Audit ✅

### Slice 1 — Entitlement
- FeatureFlag `integrations.google_maps` + PlanCatalog `google_maps`
- Gate na página Integrações
- Sem Google ainda

### Slice 2 — Persistência tenant
- Migration `company_integrations` (ou tabela Maps)
- Encryption + mask + audit log
- CRUD UI empresa + Test connection stub

### Slice 3 — Resolver + mapa
- `MapProviderResolver`
- Injetar config no `/map`
- Adapter Google (spike A/B) **ou** adiar visual e só Geocoding server-side

### Slice 4 — Admin plataforma
- Catálogo Integrações + contagem empresas + planos mínimos

### Slice 5 — Observabilidade
- Fallback metrics, erros tipados, cache invalidation

### Paralelo
Mercado Pago permanece PLATFORM; WhatsApp evolui no padrão tenant existente.

## Ordem de risco
1. Spike ToS/compatibilidade Leaflet↔Google  
2. Modelo keys browser vs server  
3. Só então UI/billing GCP da empresa
