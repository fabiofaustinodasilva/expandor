# CHANGELOG — 8.2.28

## Added

- `App\Support\AppTime`
- `config('app.display_timezone')` via `APP_TIMEZONE`
- Docs `docs/sprint-8228-timezone-consistency/`
- `Sprint8228TimezoneConsistencyTest`
- `APP_TIMEZONE` em `.env.example`, `deploy/.env.example.production`, `phpunit.xml`

## Changed

- Equipe/presença: display + hoje/ontem em BRT
- Follow-ups: parse/label/overdue wall
- Filtros “Hoje” (Agenda, Mapa, Dashboard, Equipe, SalesApp, Analytics, Comissões)
- Queries de instante: `dayBoundsUtc`
- Blades/controllers de visita, comissão, mapa, equipe
- Testes 8216/8217: freeze operacional BRT
- SalesCommissionModuleTest: asserts de título alinhados à UI (`Comissão`/`Financeiro`) — não temporal

## Unchanged

- `config('app.timezone')` = UTC (storage)
- Zero migration schema/dados
- Auth/session TTL internos
- DATE de campanhas
- Sem push/merge
