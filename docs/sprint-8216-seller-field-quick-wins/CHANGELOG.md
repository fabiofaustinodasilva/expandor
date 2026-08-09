# CHANGELOG — Sprint 8.2.16

## Backend

- `StoreFirstApproachRequest` / `StoreVisitRequest`: `follow_up_at` required_if status `return_later`; normalização via `FollowUpSchedule`
- `RegisterFirstApproachAction`: sempre agenda FollowUp em retorno (com validação defensiva)
- `MapVisitController`: idem
- `VisitRepository`: filtro `day=today` + `countPendingFollowUpsForToday`
- `FollowUpController@index`: aceita `?day=today`
- `MapController`: passa `todayFollowUpsCount` e URL da Agenda hoje
- `ClientNav::sections`: filtra itens administrativos/duplicados para seller no Mais

## Frontend

- Blocos de retorno no mapa: data obrigatória, atalhos Amanhã / +2 dias / Escolher data, horário padrão 18:00
- Chip **Hoje · N** no mapa seller → `follow-ups.index?day=today`
- Agenda: filtro Todos | Hoje; empty state neutro; badge Pendente; plan se existir
- Sales App bottom nav **Apresentar** → `sales-app.products.present`
- Cache-bust `operational-map.js?v=50`

## Docs / Tests

- `docs/sprint-8216-seller-field-quick-wins/*`
- `Sprint8216SellerFieldQuickWinsTest`
