# Sprint 7.7 — Marketplace Growth Engine + Conversion Intelligence

## Objetivo

Camada de aquisição sobre o Marketplace Premium CMS (7.6): captura de leads, UTMs, analytics de conversão, páginas por segmento, ROI, cases e inteligência de trial — sem alterar auth, tenants, billing, subscriptions, onboarding core ou o CMS existente.

## Arquitetura

```
App\Domains\Marketplace\Growth\
  Models\          Lead, SegmentPage, Case, Campaign, TrialMilestone
  Services\        Attribution, ConversionTracking, LeadCapture,
                   GrowthAnalytics, RoiCalculator, Segment/Case/Campaign,
                   TrialGrowthIntelligence
  Actions\         CaptureMarketplaceLead, CalculateMarketplaceRoi
  Jobs\            PersistMarketplaceGrowthEventJob (async)
  Events\          MarketplaceLeadCreated, MarketplaceGrowthEventRecorded
  Listeners\       RecordLeadCreatedAnalytics, SyncTrialActivationFromOnboarding
  Middleware\      CaptureMarketplaceAttribution
  Repositories\    Lead, GrowthContent
  Requests\        StoreMarketplaceLeadRequest
  DTOs\            UtmAttribution, RoiCalculation, GrowthDashboardMetrics
  Enums\           MarketplaceLeadStatus
```

Analytics do CMS (`MarketplaceAnalyticsService`) delega ao `ConversionTrackingService`.

## Tabelas

| Tabela | Papel |
|--------|--------|
| `marketplace_leads` | Leads do formulário de demo |
| `marketplace_events` | Estendido: session_id, UTMs, device, lead_id, company_id, url… |
| `marketplace_segment_pages` | Landing por nicho `/marketplace/{slug}` |
| `marketplace_cases` | Cases de sucesso |
| `marketplace_campaigns` | Cadastro de campanhas UTM |
| `marketplace_trial_milestones` | trial.started / day1 / day3 / day7 / activation |

## Eventos marketplace.*

`page_view`, `hero_view`, `feature_view`, `video_started`, `plan_view`, `plan_clicked`, `whatsapp_clicked`, `instagram_clicked`, `signup_started`, `signup_completed`, `lead_created`, `roi_calculated`

## Trial growth

| Evento | Gatilho |
|--------|---------|
| `trial.started` | Trial signup concluído |
| `trial.day1/3/7` | Avaliação por idade do trial |
| `trial.activation_completed` | `OnboardingCompleted` |

## Métricas (dashboard)

`total_visits`, `unique_visitors`, `total_leads`, `conversion_rate`, `signup_conversion`, funnel Visitantes→Leads→Testes→Clientes, `top_sources`, `top_campaigns`, `top_pages` — cache 5 min.

## Fluxo comercial

1. Visitante chega com UTM → middleware grava session + attribution  
2. Navega landing / segmento → eventos (+ beacon)  
3. Solicita demo → lead + `marketplace.lead_created`  
4. Ou calcula ROI → `marketplace.roi_calculated`  
5. Ou inicia trial → signup events + `trial.started`  
6. Platform → Leads / Analytics / Segmentos / Cases / Campanhas  

## Decisões

1. Extensão da tabela `marketplace_events` (não recriação) para preservar 7.6.  
2. Persistência de eventos via Job (sync em testing / queue sync).  
3. IP apenas como hash; session UUID anônimo.  
4. WhatsApp aceita `context` (Marketplace / Segmento / Plano).  
5. Gate `marketplace.manage` reutilizado — sem novas permissões DB.

## Rotas novas

| Rota | Nome |
|------|------|
| `GET /marketplace/{segment}` | `marketplace.segment` |
| `POST /marketplace/leads` | `marketplace.leads.store` |
| `POST /marketplace/roi` | `marketplace.roi.calculate` |
| `GET /platform/marketplace/leads` | `platform.marketplace.leads.index` |
| `GET /platform/marketplace/analytics` | `platform.marketplace.analytics` |
| … segments / cases / campaigns | CRUD admin |

## Testes

`tests/Feature/Marketplace/Sprint770MarketplaceGrowthTest.php`
