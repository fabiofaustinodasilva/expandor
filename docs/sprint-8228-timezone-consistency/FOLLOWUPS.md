# FOLLOWUPS

## Round-trip

Entrada: `10/08/2026 15:00`  
Persistência: `2026-08-10 15:00:00` (dígitos)  
UI: `15:00` via `FollowUpSchedule::label` / `AppTime::formatWall`

Sem +3h / −3h.

## Overdue

`isOverdue` usa `AppTime::wall()` (shiftTimezone) para comparar o instante civil pretendido com `now()`, e `AppTime::today()` para retornos só-data.

## Agenda “Hoje”

`VisitRepository` filtra `whereDate('scheduled_at', AppTime::today())`.

## Inputs

`min` nos date pickers: `AppTime::today()` (não `now()->toDateString()` UTC).
