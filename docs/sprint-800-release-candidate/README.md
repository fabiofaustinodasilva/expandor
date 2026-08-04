# Sprint 8.0 — Release Candidate RC1

## Objetivo

Acabamento para produção: UX consistente, responsividade, performance leve e preparação de ambiente — **sem novos módulos** e **sem alteração de regras de negócio** (Billing, Marketplace CMS/Revenue, CRM, SaaS, Platform, Tenants, Auth, Permissions).

## Checklist revisado

| Área | Status | Notas |
|------|--------|-------|
| Layouts compartilhados | ✅ | Partial `rc-ux-polish` em platform/app/guest/operational |
| UX (skeleton/empty/toast/loading) | ✅ | Componentes + toast JS `ExpandorUX` |
| Responsividade | ✅ | Guest mobile; marketplace menu; grid-2 collapse |
| Marketplace | ✅ | Menu mobile + lazy logo; plans() sem assemble completo |
| Dashboard / CRM / Billing / Onboarding | ✅ | Beneficiam do polish compartilhado (sem mudar fluxos) |
| Performance | ✅ | `MarketplaceController::plans` só carrega settings |
| Segurança | ✅ | Revisado — sem mudança de policies/middleware |
| Logs | ✅ | Sem `dd`/`dump`/`Log::debug` em Domains |
| `.env.example` | ✅ | Comentários de produção + `ACQUISITION_TRIAL_DAYS` |
| Testes | ✅ | 354 passed (2105 assertions) |
| Código PSR | ✅ | Sem refactors amplos de domínio |

## Mudanças desta sprint

1. `resources/views/partials/rc-ux-polish.blade.php` — skeleton, spinner, overlay, empty, toast, alert/form polish  
2. `components/ux/skeleton.blade.php`, `components/ux/empty-state.blade.php`  
3. Inclusão do polish nos layouts `platform`, `app`, `guest`, `operational`  
4. Marketplace landing: toggle Menu mobile (antes nav sumia sem alternativa)  
5. `MarketplaceController::plans()` — evita `assemble()` completo  
6. `.env.example` — orientação de produção (queue/cache/session/debug)  

## Pendências (pós-RC / produção)

- Extrair CSS inline dos layouts para asset único (Vite)  
- Adotar `<x-ux.*>` em todas as views (hoje ainda há markup direto)  
- Cache Redis + worker de fila no servidor  
- `php artisan config:cache` / `route:cache` / `view:cache` no deploy  
- Validação visual completa no staging com dados reais  
- Load test do dashboard Platform sob N tenants  

## Como validar localmente

```bash
php artisan test
php artisan migrate
php artisan storage:link
```

Produção sugerida: copiar `deploy/.env.example.production`, configurar queue worker e cron `schedule:run`.

## Commit

`chore: release candidate polish for production readiness`
