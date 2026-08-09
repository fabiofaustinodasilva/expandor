# Sprint 8.2.17 — Presença e atividade da equipe

Melhora a Área da Empresa → Equipe com foto, online/offline, última atividade comercial, última venda e resumo do dia — **sem rastreamento GPS**.

## Branch

`feature/sprint-8217-team-presence-activity` (base: 8.2.16)

## Decisão Fase A → B

**Hotfix pós-produção:** presença **não** depende de `sessions`.  
Usa `users.last_seen_at` + middleware throttled (`presence.touch`, 2 min).  
Online = 5 minutos. Funciona com `SESSION_DRIVER=file`.  

Histórico de conexão via `audit_logs` (`auth.login_succeeded`) + `last_seen_at`. Sem inventar "Saiu".

Ver `HOTFIX-PRESENCE.md`.

## Docs

- `AUDIT.md` — respostas da auditoria
- `DATA-MAPPING.md` — origem de cada campo
- `PRIVACY.md` — o que não coletamos
- `PERFORMANCE.md` — queries agregadas
- `OFFLINE-READINESS.md` — Capacitor futuro
- `TEST-REPORT.md` / `UX-CHECKLIST.md` / `CHANGELOG.md`
