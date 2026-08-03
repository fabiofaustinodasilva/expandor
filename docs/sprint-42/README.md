# Sprint 4.2 — Agenda de Retornos

## Decisões

| Tema | Decisão |
|---|---|
| Visibilidade | Seller → só os próprios. Manager/Admin/Supervisor → equipe |
| Concluir | Modal na Agenda (padrão 4.1) → Visit + FollowUp concluído |
| Novo retorno | Se resultado = Retornar → agenda novo FollowUp na hora |
| Menu | "Minha rota" / "Retornos" → **Agenda** |
| Persistência | Reusa `follow_ups` (sem tabela nova) |

## Fluxo

```
Agenda
  → Concluir
  → Modal de resultado (Contratou / Interessado / Retornar / Sem interesse / Não encontrado)
  → Salvar
  → Cria Visit real
  → Atualiza FollowUp para concluído
  → Se Retornar: novo FollowUp (data + horário + observação)
  → Permanece na Agenda (sem redirect ao mapa)
```

## Ações comerciais por card

- **WhatsApp** — `wa.me` com mensagem pré-preenchida (sem API)
- **Rota** — Google Maps Directions
- **Detalhes** — abre o mapa com `?property=` e foca o drawer
- **Concluir** — modal de resultado

## Drawer do mapa

Exibe **Próximo retorno** (`next_follow_up_at` / relative / notes) no detalhe do ponto.

## Arquivos

- `VisitRepository::paginatePendingFollowUps` (escopo por papel)
- `VisitService::completeFollowUpWithOutcome`
- `CompleteFollowUpAction` / `CompleteFollowUpRequest`
- `FollowUpPolicy` (seller só conclui o próprio)
- `FollowUpController` + `visits/follow-ups/index.blade.php`
- `layouts/operational.blade.php` + `layouts/app.blade.php` (label Agenda)
- `MapPointController::show` + drawer JS/HTML
- `MapVisitController` + `StoreVisitRequest` (`follow_up_at` em revisita)
- `tests/Feature/Visits/AgendaFollowUpsTest.php`

**Não alterado:** contrato atômico do First Approach · Próxima casa · permissions matrix.

## Prints

Capturar manualmente com a demo (`seller@unicanetwork.demo` / `password`):

1. `agenda-lista.png` — `/follow-ups` com cards + ações
2. `agenda-modal-resultado.png` — modal após “Concluir”
3. `agenda-novo-retorno.png` — bloco “Novo agendamento” com Retornar
4. `mapa-drawer-retorno.png` — drawer com “Próximo retorno”

Salvar em `docs/sprint-42/`.

## Testes

```
130 passed (711 assertions)
```

Inclui `AgendaFollowUpsTest` (7 casos): escopo seller/manager, conclusão com Visit, novo retorno, validação, forbidden, drawer.