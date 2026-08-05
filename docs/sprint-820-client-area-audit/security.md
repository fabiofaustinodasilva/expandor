# Security — Tenant, policies, exposição

## Controles existentes (bons)

| Controle | Onde | Função |
|----------|------|--------|
| `InitializeTenancy` | middleware | Define empresa do user |
| `EnsureTenantIsActive` | middleware | Bloqueia company não `active` |
| `TenantScope` / `BelongsToTenant` | models | Filtra `company_id` |
| Policies + `authorize` | controllers | Autorização por habilidade |
| `EnsurePermission` | API | Slug explícito |
| Soft-delete company | platform | Isola tenant cancelado |
| Unique email global (8.1.9) | DB | Identidade única |

## Riscos / gaps

| Risco | Severidade | Detalhe |
|-------|------------|---------|
| Policies não registradas | Média | `CustomerPolicy`, `SalesAppPolicy` fora do Gate padrão |
| Autorização web inconsistente | Média | Nem toda rota usa `authorize` igualmente; depende de review |
| Feature flags não enforcement | Baixa-Média | AI/WhatsApp visíveis mesmo se flag platform off |
| Plan features não enforcement UI | Média | Módulo no menu ≠ incluso no plano (limites via `BillingService` em alguns pontos) |
| `withoutGlobalScopes()` | Média | Necessário em platform/payments; **perigoso** se vazar em controller tenant |
| Impersonation | Baixa (controlado) | Banner + exit; auditar sempre |
| Rotas públicas checkout/webhook | Baixa | Fora do tenant; CSRF exempt webhooks — esperado |
| Viewer role | Baixa | Quase sem permissões; validar que deep links 403 |

## TenantScope — vazamento entre empresas

- Models tenant com `BelongsToTenant` estão protegidos **se** queries usam Eloquent com scope ativo.
- Padrão de risco: `Model::withoutGlobalScopes()->where(...)` sem filtrar `company_id` em código tenant.
- CheckoutSession / PaymentGatewayTransaction **não** são BelongsToTenant (por design do funil público) — ok; não misturar com UI tenant sem checagem.

## Guards

- Web: `web` session guard.
- API: `sanctum`.
- Platform: mesmo user model + flag/permission `platform.access`.

## Checklist para 8.2.1 (segurança)

1. Registrar todas as Policies no Gate.  
2. Audit estático: controllers Web tenant sem `authorize` / `hasPermission`.  
3. Ligar flags/plan features a menu **e** abort no controller.  
4. Grep `withoutGlobalScopes` em `Http/Controllers/Web` (exceto casos justificados).  
5. Testes de isolamento: user A não lê resource company B.
