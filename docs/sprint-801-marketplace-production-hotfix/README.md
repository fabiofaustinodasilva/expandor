# Sprint 8.0.1 — Marketplace Production Hotfix

## Objetivo

Garantir que a landing do Marketplace funcione em instalação limpa **sem depender do CMS**. O CMS passa a ser apenas personalização.

## Fluxo

```
Defaults (config/marketplace_defaults.php)
        ↓
CMS sobrescreve quando existir conteúdo ativo
        ↓
Landing sempre completa
```

## Entregas

| Item | Status |
|------|--------|
| `config/marketplace_defaults.php` | ✅ |
| `MarketplacePublicPageService::assemble()` merge defaults→CMS | ✅ |
| Hero / About / Features / FAQ / Testimonials / CTA / Video fallback | ✅ |
| Navbar e rodapé a partir do config | ✅ |
| `MarketplaceDefaultSeeder` (idempotente + force) | ✅ |
| Botão "Restaurar Conteúdo Padrão" no admin | ✅ |
| Assets SVG padrão em `public/images/marketplace/` | ✅ |
| Testes Sprint 801 | ✅ |

## Regras preservadas

Sem alteração de Auth, Billing, Checkout, Payments, Tenants, ACL, CRM, Onboarding, SaaS Intelligence, Growth, Revenue Intelligence ou analytics existentes.

## Commit

`fix: make marketplace production ready with default content fallback`
