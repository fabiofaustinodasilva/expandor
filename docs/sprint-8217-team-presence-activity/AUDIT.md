# AUDIT — Sprint 8.2.17

## Respostas obrigatórias

| # | Pergunta | Resposta |
|---|----------|----------|
| 1 | `last_login_at`? | **SIM** — `users.last_login_at` (LoginController / API Auth) |
| 2 | `last_seen_at`? | **SIM (hotfix)** — `users.last_seen_at`; antes usava `sessions` (incompatível com SESSION_DRIVER=file) |
| 3 | Histórico login/logout? | Login: `audit_logs.action=auth.login_succeeded`. **Logout não é auditado** |
| 4 | Tabela sessões? | **SIM** — `sessions` (id, user_id, last_activity, …); driver default `database` |
| 5 | Audit log utilizável? | **Parcial** — logins confiáveis; sem duração de sessão histórica completa |
| 6 | Foto? | **SIM** — `users.photo` / `photo_thumb`; `User::photoUrl()`; Profile → `UserService::updateOwnProfile` |
| 7 | Última visita? | `Visit` por `user_id`, `MAX(id)` / `visited_at`; label via `VisitHistoryPresenter` |
| 8 | Última venda? | `Sale` via `visit.user_id` (Sale sem user_id) |
| 9 | Nome cliente/ponto? | `commissionClientLabel()` → residente / sale.resident / **Ponto sem cliente** |
| 10 | Produto da venda? | `sale_items.product_name` (snapshot) ou `sale.product` |
| 11 | Visitas hoje? | `DashboardMetricsService` / `seller_productivity.visits` (`visited_at` hoje) |
| 12 | Interessados hoje? | idem `interested` |
| 13 | Vendas hoje? | idem `installations` (= `installation_requested`) |

## Migration

Hotfix: `users.last_seen_at` + índice `(company_id, last_seen_at)`. Ver `HOTFIX-PRESENCE.md`.

Original sprint planejou zero migrations; produção com `SESSION_DRIVER=file` exige a coluna.

## Risco tenancy

Todas as queries passam por TenantScope em Visit/Sale/AuditLog/User.

## GPS

Painel antigo exibia lat/lng da última visita — **removido** nesta sprint (privacidade).
