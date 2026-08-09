# Sprint 8.2.21 — Fundação da Central de Integrações

Branch: `feature/sprint-8221-integrations-foundation`  
Base: auditoria 8.2.20 aprovada.

## O que foi entregue

Fundação real (sem Google visual):

| Camada | Entrega |
|--------|---------|
| Plataforma | Catálogo Super Admin (`/platform/integrations`) |
| Plano | Feature `google_maps` no PlanCatalog + flag `integrations.google_maps` |
| Empresa | Central + conexão Google Maps (key web, test, mask, disconnect) |
| Seller | Sem acesso de configuração; herda via resolver |
| Resolver | `MapIntegrationResolver` → lógico `google_maps` ou `leaflet_osm` |
| Visual | **Continua Leaflet** (`visualProvider()` sempre `leaflet_osm`) |

## Fora de escopo (cumprido)

- Sem adapter Google no mapa  
- Sem tiles/Places/Geocoding/Routes/Street View de produto  
- Sem migração Mercado Pago para tenant  
- Sem Capacitor  

## Docs

| Arquivo | Conteúdo |
|---------|----------|
| DATA-MODEL.md | `company_integrations` |
| SECURITY.md | Browser vs server, encryption, masking |
| ENTITLEMENTS.md | Plano + flag |
| GOOGLE-MAPS-CONNECTION.md | Fluxo conectar/testar |
| FALLBACK.md | Resolver |
| MERCADOPAGO-COMPATIBILITY.md | Platform-managed intacto |
| TEST-REPORT.md | Cobertura |
| UX-CHECKLIST.md | Estados de UI |
| CHANGELOG.md | Diff técnico |
