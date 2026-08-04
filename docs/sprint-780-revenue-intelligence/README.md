# Sprint 7.8 — Marketplace Revenue Intelligence

## Objetivo

Transformar leads e eventos do Marketplace em inteligência comercial: scoring, pipeline SaaS, alertas de lead quente, dashboard e CAC/ROI de campanhas — sem alterar auth, tenants, billing, subscriptions, onboarding core ou CRM interno.

## Arquitetura

```
App\Domains\Marketplace\Revenue\
  Models\       MarketplaceLeadScore, MarketplaceSalesPipeline
  Enums\        LeadTemperature, PipelineStage
  Services\     LeadScoringService, MarketplacePipelineService, RevenueIntelligenceService
  Actions\      ScoreMarketplaceLeadAction, UpdateMarketplacePipelineAction
  Events\       LeadScored, LeadHotDetected, PipelineChanged, DemoScheduled
  Listeners\    BootstrapLeadRevenueOnCreated, RescoreLeadOnGrowthEvent,
                RecordHotLeadAnalytics, RecordLeadScoredAnalytics,
                RecordPipelineChangedAnalytics
  DTOs\         RevenueIntelligenceMetrics
```

## Tabelas

| Tabela | Papel |
|--------|--------|
| `marketplace_lead_scores` | Score + sinais + temperature |
| `marketplace_sales_pipeline` | Estágio comercial por lead |
| `marketplace_campaigns.investment` | Investimento para CAC/ROI |

## Lead scoring

| Sinal | Pontos |
|-------|--------|
| Visitou planos (`plan_view` / `plan_clicked`) | +10 |
| Assistiu vídeo | +15 |
| Calculadora ROI | +20 |
| Clique WhatsApp | +25 |
| Solicitou demo (lead) | +40 |

Cap 100. Temperature: cold ≤30 · warm 31–70 · hot ≥71.

## Pipeline stages

`new` → `contacted` → `demo_scheduled` → `trial_started` → `customer` / `lost`

## Eventos

- `marketplace.lead_scored`
- `marketplace.lead_hot_detected`
- `marketplace.pipeline_changed`
- `marketplace.demo_scheduled`

## Dashboard Intelligence

Rota: `GET /platform/marketplace/intelligence`  
Métricas: visitantes, leads, conversão %, quentes, demos, trials, clientes, funil, campanhas (investimento, leads, clientes, CAC, ROI).

## Fluxo

1. Lead criado → pipeline `new` + score inicial (demo = 40 / warm)  
2. Eventos de sessão/lead → rescore automático  
3. Score ≥ 71 → `lead_hot_detected` + alerta no painel  
4. Operador move estágio no Pipeline  

## Testes

`tests/Feature/Marketplace/Sprint780MarketplaceRevenueIntelligenceTest.php`
