# Sprint 8.2.16 — Seller field quick wins

Quick wins do fluxo de campo / retornos + navegação seller, com base na auditoria `docs/sprint-8214-seller-area-audit/`.

## Objetivo

Resolver P0/P1 sem reescrever o Sales App e sem migrations:

1. Retorno exige data/hora utilizável
2. Retorno cria FollowUp e aparece na Agenda
3. Chip **Hoje · N** no mapa → Agenda filtrada
4. Limpar Mais seller (admin/duplicatas)
5. Apresentar → deck photo-first

## Decisão de regra (Retorno sem FollowUp)

**Causa técnica:** `RegisterFirstApproachAction` / `MapVisitController` só chamavam `scheduleFollowUp` quando `follow_up_at` vinha preenchido; a validação permitia `nullable`. Status `return_later` no ponto/visita ≠ FollowUp na Agenda.

**Fluxos legítimos preservados (não alterados silenciosamente):**

- Registros históricos já salvos como retorno sem FollowUp
- `MapPointController::store` (ponto administrativo sem visita) — não é o fluxo FirstApproach do seller
- Mobile API com flag `schedule_follow_up` (contrato separado)

**Correção mínima:** exigir `follow_up_at` em FirstApproach + visita do mapa quando status = `return_later`, e sempre criar FollowUp via `VisitService::scheduleFollowUp` (coluna existente `scheduled_at`).

## Zero migrations

Modelo `FollowUp.scheduled_at` já cobre data e horário (convenção `FollowUpSchedule`: meia-noite = sem horário).

## Mapa = home

Nenhuma Home seller nova. Chip Hoje é atalho discreto.

## Branch

`feature/sprint-8216-seller-field-quick-wins`
