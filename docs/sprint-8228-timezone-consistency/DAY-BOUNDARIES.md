# DAY-BOUNDARIES

## Problema

Às 22:30 BRT, UTC já é o dia seguinte. `now()->toDateString()` / `whereDate` em colunas instantâneas classificavam visitas/vendas no dia UTC errado.

## Regra

1. Dia operacional = `AppTime::today()` (`America/Sao_Paulo`).
2. Colunas **instant** (`visited_at`, `earned_at`, `created_at` de métricas): `AppTime::dayBoundsUtc()` + `whereBetween` / `>= start` / `<= end`.
3. Colunas **wall** (`scheduled_at`): `whereDate(..., AppTime::today())`.
4. Colunas **DATE** (campanhas, metas `period_start`): comparar com `AppTime::today()` sem conversão de instante.

## 8. Filtro “Hoje” e testes

Queries de produto usam `AppTime::today()` / `dayBoundsUtc()`.

**Testes legados (8216)** assumiam o dia UTC do runner e falhavam na fronteira
`22:30 BRT` / `01:30 UTC`. Foram normalizados para congelar o relógio no fuso
operacional — ver `TEST-REPORT.md`. Não reverter o produto para UTC.
