# README — Sprint 8.2.20

**Somente auditoria e arquitetura.** Sem implementação, migration, SDK, alteração de mapa/MP/planos, push ou merge.

## Branch

`feature/sprint-8220-platform-integrations-audit`

## Objetivo

Definir a Central de Integrações com dois níveis:

1. **Admin Expandor (plataforma)** — catálogo, planos que liberam, capabilities, fallback  
2. **Empresa (tenant)** — conectar credenciais próprias quando a integração for **TENANT-MANAGED**

Regra de negócio Google Maps: Expandor vende o **direito** via plano; **consumo/billing GCP** é da empresa.

## Documentos

| Arquivo | Conteúdo |
|---------|----------|
| CURRENT-ARCHITECTURE.md | Visão geral |
| MAPS-AUDIT.md | Leaflet/OSM/Esri |
| GOOGLE-MAPS-AUDIT.md | Opções técnicas Google |
| MERCADOPAGO-AUDIT.md | Classificação MP |
| PLANS-FEATURES.md | Planos / flags |
| TENANT-INTEGRATIONS.md | Modelo tenant |
| SECURITY.md | Credenciais |
| DATA-MODEL-PROPOSAL.md | Schema proposto (não criar) |
| PROVIDER-ARCHITECTURE.md | Resolver + fallback |
| CAPACITOR-READINESS.md | App futuro |
| IMPLEMENTATION-PLAN.md | Roadmap |

## Decisão preliminar (auditoria)

| Integração | Tipo | Configura quem? | Consumo | Plano controla? |
|------------|------|-----------------|---------|-----------------|
| Mapa padrão (Leaflet+OSM/Esri) | PLATFORM | Expandor (código) | Expandor/tiles públicos | Não (sempre fallback) |
| Google Maps | **TENANT-MANAGED** (alvo) | Empresa | Empresa (GCP) | Sim |
| Mercado Pago | **PLATFORM-MANAGED** | Admin Expandor | Expandor | Indireto (checkout SaaS) |
| WhatsApp | TENANT (+ flag/plano) | Empresa | Empresa | Sim (`whatsapp`) |
| CEP/Território | Futuro TENANT/HYBRID | TBD | TBD | Sim |
