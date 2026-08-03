# Sprint 4.3 — Histórico comercial de visitas

## Objetivo

Transformar **Minhas visitas** em histórico comercial de atendimentos, sem alterar First Approach, Agenda, permissões ou banco.

## Decisões

| Tema | Decisão |
|---|---|
| Menu | Mantém **Visitas** |
| Título | Histórico de atendimentos |
| Fallback GPS | Persistido `Local GPS` → exibe “Residência cadastrada pelo GPS” |
| Layout | Cards mobile + desktop |
| Próxima ação | FU da Visit → FU Property+seller → “Sem próximo passo definido” |
| Drawer | Detalhe comercial (sem property_histories) |

## Arquivos

- `VisitStatus::commercialLabel()`
- `VisitHistoryPresenter`
- `VisitRepository::paginateMyVisits` + `pendingFollowUpsByPropertyForUser`
- `MyVisitsController`
- `resources/views/operations/my-visits.blade.php`
- `tests/Feature/Visits/VisitHistoryTest.php`
- `tests/Unit/Visits/VisitHistoryPresenterTest.php`

## Prints

Capturar em `/operacao/minhas-visitas` (`seller@unicanetwork.demo`):

1. `historico-cards.png` — lista de cards
2. `historico-gps-fallback.png` — sem “Local GPS”
3. `historico-drawer.png` — detalhe do atendimento

## Limitações

- Histórico completo do imóvel (`property_histories`) fica para sprint futura
- Menu permanece “Visitas” (título interno mudou)
- Seller vê só as próprias visitas (igual antes)
- “Novo retorno” no drawer usa o formulário clássico (`visits.follow-ups.create`), sem alterar a Agenda

## Testes

```
145 passed (769 assertions)
```

Filtro: `VisitHistory` → 7 passed.
