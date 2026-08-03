# Sprint 4.2.1 — Horário opcional na Agenda

## Problema

Retornos criados só com data gravavam `00:00:00` e a Agenda mostrava esse horário.

## Solução (sem migration)

Convenção em `follow_ups.scheduled_at`:

| Persistido | Significado | Exibição |
|---|---|---|
| `2026-08-04 00:00:00` | Só data | `04/08/2026` + *Horário não definido* |
| `2026-08-04 14:30:00` | Data + hora | `04/08/2026 às 14:30` |

Helper: `App\Domains\Visits\Support\FollowUpSchedule`

## Cadastro

- Data obrigatória
- Horário opcional
- Formulários: Agenda (novo retorno), `visits/follow-ups/create`, mapa (First Approach / visita)

## Compatibilidade

- Retornos antigos com meia-noite → “Horário não definido”
- Retornos antigos com horário real → inalterados
- First Approach continua aceitando `follow_up_at` (date ou datetime)

## Arquivos

- `app/Domains/Visits/Support/FollowUpSchedule.php`
- `app/Domains/Visits/Models/FollowUp.php`
- `app/Domains/Visits/Services/VisitService.php`
- `app/Domains/Visits/Requests/StoreFollowUpRequest.php`
- `app/Domains/Visits/Requests/CompleteFollowUpRequest.php`
- `app/Http/Controllers/Web/Visits/FollowUpController.php`
- `app/Http/Controllers/Web/Maps/MapPointController.php`
- `resources/views/visits/follow-ups/index.blade.php`
- `resources/views/visits/follow-ups/create.blade.php`
- `resources/views/visits/show.blade.php`
- `resources/views/sales-app/follow-ups/index.blade.php`
- `resources/views/maps/index.blade.php`
- `public/js/operational-map.js`
- `tests/Unit/Visits/FollowUpScheduleTest.php`
- `tests/Feature/Visits/AgendaFollowUpsTest.php`

## Prints

1. `agenda-sem-horario.png` — card com data + “Horário não definido”
2. `agenda-com-horario.png` — “dd/mm/aaaa às HH:mm”
3. `agenda-form-horario-opcional.png` — campos Data * / Horário (opc.)

## Testes

```
138 passed (744 assertions)
```

Filtro focado: `FollowUpScheduleTest|AgendaFollowUpsTest|VisitsModuleTest|FirstApproachTest` → 26 passed.
