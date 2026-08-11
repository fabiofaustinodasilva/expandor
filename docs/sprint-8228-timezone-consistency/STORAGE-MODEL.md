# STORAGE-MODEL (final)

## Veredito: **C — mistura** (comprovado)

### Instants (`now()` sob `app.timezone=UTC`)

Persistidos como wall-clock **UTC** em colunas `timestamp` (Laravel não converte MySQL↔UTC automaticamente aqui).

Exemplos: `users.last_login_at`, `users.last_seen_at`, `visits.visited_at`, `created_at`/`updated_at`, `sales_commissions.earned_at`, `audit_logs.created_at`, `password_reset_tokens.created_at`.

**Apresentação:** `AppTime::formatInstant()` / `AppTime::local()` → `America/Sao_Paulo`.  
**Filtro de dia:** `AppTime::dayBoundsUtc()` + `whereBetween` / limites UTC (não `whereDate` cego no calendário UTC).

### Wall-clock operacional (follow-ups)

`follow_ups.scheduled_at`: dígitos que o vendedor digitou (ex. `15:00`), historicamente rotulados como app TZ UTC.

**Apresentação:** `AppTime::formatWall()` / `FollowUpSchedule::label` (sem shift de relógio).  
**Parse:** `AppTime::parseWall` / `FollowUpSchedule::fromDateAndTime` (mantém dígitos).  
**Filtro de dia:** `whereDate(..., AppTime::today())`.

### DATE puro

`campaigns.start_date` / `end_date` (cast `date`): calendário civil — **sem** conversão de instante.

### Epoch

`sessions.last_activity` (integer unix): independente de `APP_TIMEZONE`.

## Dados históricos

**ZERO** rewrite. Instants UTC corretos; follow-ups wall já alinhados ao display wall. Migration massiva proibida nesta sprint.
