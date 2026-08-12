# Agenda

`GET /agenda?scope=today|upcoming|overdue|all`

Day boundary: `AppTime::today()` / `whereDate(scheduled_at)` em `America/Sao_Paulo`.

Paginação `per_page` 1–50. Somente follow-ups `pending` do seller autenticado.
