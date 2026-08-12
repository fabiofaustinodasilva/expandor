# Follow-ups

Criar retorno: visita `return_later` + `follow_up_at` → `VisitService::scheduleFollowUp`.

Concluir: `POST /follow-ups/{id}/complete` → `CompleteFollowUpAction`.

`scheduled_at` é wall-clock (8.2.28). Label via `FollowUpSchedule::label`. Não converter para UTC “de agenda”.
