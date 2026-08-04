# Sprint 7.6 — Marketplace Premium CMS + Public Sales Experience

## Objetivo

Transformar a página pública do Expandor em uma landing comercial SaaS premium, gerenciada por CMS na área administrativa da plataforma — sem alterar auth, tenants, billing, subscriptions, onboarding ou CRM interno.

## Arquitetura

```
App\Domains\Marketplace\
  Models\          MarketplaceSetting, Section, Testimonial, Faq, Media, Event
  Enums\           MarketplaceSectionType
  Services\        Settings, Section, Media, Analytics, PublicPage
  Repositories\    SettingsRepository, ContentRepository
  Actions\         UpdateMarketplaceSettings, UpsertSection, RecordEvent
  Requests\        UpdateMarketplaceSettings, UpsertSection
  Policies\        MarketplacePolicy (gate marketplace.manage)
  Events\          MarketplaceAnalyticsRecorded
```

Controllers:
- Público: `MarketplaceController`, `MarketplaceAnalyticsController`
- Admin: `Platform\Marketplace\{Settings,Section,Media}Controller`

## Tabelas

| Tabela | Papel |
|--------|--------|
| `marketplace_settings` | Singleton de identidade, tema, contato, SEO |
| `marketplace_sections` | Blocos da landing (type/order/active) |
| `marketplace_testimonials` | Prova social |
| `marketplace_faqs` | FAQ |
| `marketplace_media` | Galeria / vídeos |
| `marketplace_events` | Analytics (ip_hash, user_agent, origin) |

## Fluxo CMS

1. Platform admin → **Marketplace → Configuração** (logo, cores, WhatsApp, Instagram, SEO)
2. **Seções** — CRUD, ativar/desativar, reordenar
3. **Mídias** — imagens/vídeos, depoimentos, FAQ
4. **Visualizar Marketplace** — preview autenticado (`/platform/marketplace/preview`) sem fluxo de publish separado (persistência = live)
5. Público `/` e `/marketplace` montam a landing via `MarketplacePublicPageService`

## Componentes Blade

- `x-marketplace-whats-app-button` — flutuante se `whatsapp_enabled`
- `x-marketplace-social-links` — Instagram (+ Facebook/YouTube/LinkedIn futuros)
- View: `marketplace/landing.blade.php`

## Eventos analytics

| Evento | Quando |
|--------|--------|
| `marketplace.page_view` | GET `/` |
| `marketplace.whatsapp_clicked` | clique no botão / beacon |
| `marketplace.instagram_clicked` | clique social |
| `marketplace.video_started` | play / beacon |
| `marketplace.plan_clicked` | CTA de plano / redirect subscribe |
| `marketplace.signup_started` | GET `/cadastro` |
| `marketplace.signup_completed` | pós-provisionamento trial |

Endpoint: `POST /marketplace/events` (`marketplace.events.store`).

## SEO & Performance

- Meta title/description/keywords + Open Graph + Twitter Card
- `GET /sitemap.xml`
- Upload via `MediaUploadService::storeMarketplace()` (compressão/otimização de imagens)
- `loading="lazy"` na landing
- Cache de settings (`MarketplaceSettingsRepository`)

## Decisões técnicas

1. Gate `marketplace.manage` espelha `platform.manageBranding` (platform admin + `platform.access`) — sem nova permissão DB.
2. Sem draft/publish: salvar no CMS já reflete no público; preview é visualização autenticada.
3. Features da seção podem vir como JSON em `description` (cards CRM/Vendas/Equipe/Dashboard).
4. Planos públicos reutilizam `plans` existentes (`is_featured`, `display_order`) — billing/subscriptions intocados.
5. IP armazenado como hash SHA-256 (`app.key` + IP) — sem PII crua.

## Rotas admin

| Método | Rota | Nome |
|--------|------|------|
| GET/PUT | `/platform/marketplace/settings` | `platform.marketplace.settings.*` |
| GET | `/platform/marketplace/preview` | `platform.marketplace.preview` |
| CRUD | `/platform/marketplace/sections` | `platform.marketplace.sections.*` |
| CRUD | `/platform/marketplace/media` | `platform.marketplace.media.*` |

## Testes

`tests/Feature/Marketplace/Sprint760MarketplaceCmsTest.php`
