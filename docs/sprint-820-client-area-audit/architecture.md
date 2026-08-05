# Architecture — Área do Cliente

## Superfícies do produto

| Superfície | Layout | Prefixo | Público |
|------------|--------|---------|---------|
| **Área do Cliente** | `layouts.app`, `layouts.operational`, `layouts.sales-app` | `/dashboard`, `/map`, `/crm`, … | Usuários da empresa |
| Platform Admin | `layouts.platform` | `/platform/*` | Platform Admin |
| Marketplace público | `layouts.guest` | `/`, `/planos`, checkout | Visitantes |

Pós-login: redirect para `/dashboard` (`bootstrap/app.php`).

## Stack da Área do Cliente

- **Backend:** Laravel (domains em `app/Domains/*`)
- **UI:** Blade server-rendered (sem Livewire, Vue, React, Inertia)
- **Assets:** Vite + Tailwind + axios (`resources/js/app.js` mínimo)
- **Auth web:** session (`auth`)
- **Auth API:** Sanctum (`auth:sanctum`) — v1 geral + `mobile/v1` vendedor
- **Tenancy:** `tenancy.initialize` + `tenancy.active` + `BelongsToTenant` / `TenantScope`

## Middleware tenant (web)

```
auth → tenancy.initialize → tenancy.active
```

Permissões na web: sobretudo **Policies** + `authorize()` + Blade `hasPermission()`.  
Na API: middleware `permission:{slug}` em rotas selecionadas.

## Controllers Web tenant (~55)

Base: `app/Http/Controllers/Web/`

| Área | Controllers |
|------|-------------|
| Dashboard | `Dashboard/DashboardController` |
| Maps | `Maps/MapController`, `MapPointController`, `MapVisitController`, `MapFirstApproachController` |
| Campaigns | `Campaigns/CampaignController` |
| Visits | `Visits/VisitController`, `FollowUpController` |
| CRM | `CRM/CrmDashboardController`, `LeadController`, `OpportunityController`, `SalesGoalController`, `CommissionController` |
| Customers | `Customers/CustomerController` |
| Sales | `Sales/Properties/*`, `Residents/*`, `Territory/*` |
| Commissions | `Commissions/SalesCommissionController`, `ProductController` |
| Operations | `Operations/TeamController`, `SettingsController`, `MoreController`, `MyVisitsController`, `IntegrationsController`, `SaleSettingsController`, `FieldOperationsSettingsController` |
| Sales App | `SalesApp/*` (4) |
| Company | `Company/CompanyController`, `UserController` |
| Branding / Billing / Payments | `Branding/*`, `Billing/CompanyPlanController`, `Payments/SubscriptionController` |
| Onboarding | `Onboarding/SaasOnboardingController`, `SetupWizardController`, `TourController`, `TrialConversionController` |
| Training / AI / Communication | `Training/*`, `AI/*`, `Communication/*` |
| Security / Profile | `Security/*`, `Profile/ProfileController` |

## Domínios `app/Domains` (cliente)

Campaigns, Visits, CRM, Customers, Sales (+Properties/Residents/Territory/Products), Commissions, SalesApp, Maps, Analytics, Training, AI, Communication, Branding, Billing, Payments (assinatura tenant), Onboarding, Company, Security, Mobile.

## Layouts e quando usar

| Layout | Uso típico |
|--------|------------|
| `operational` | Mapa, Resultados, Equipe, Clientes, Comissões, Config hub |
| `app` | CRUD clássico (CRM forms, territory, training admin, users, AI, WhatsApp) |
| `sales-app` | Shell mobile de campo (`/app/*`) |

## API

| Prefixo | Uso |
|---------|-----|
| `/api/v1` | Auth, branding, onboarding, users, map markers |
| `/api/mobile/v1` | App vendedor (dashboard, campanhas, visitas, sync) — exige `sales_app.access` |

## Jobs / Events (tenant)

- Jobs: AI chat, WhatsApp send, billing automation (renew/expire/retry)
- Events onboarding → listeners de ativação/audit
- Payment events: **dispatched**, listeners AppServiceProvider **ausentes**
- Observers Eloquent: **nenhum**
- Notifications Laravel: **nenhuma** (mail welcome no provisionamento)

## Fontes de verdade

- Rotas: `routes/web.php`, `routes/api.php`
- Nav: `layouts/partials/app-nav.blade.php`, `layouts/operational.blade.php`, `layouts/sales-app.blade.php`, `Operations/MoreController`
- Permissões: `database/seeders/RolePermissionSeeder.php`
