# CHANGELOG — 8.2.17

- `TeamPresenceActivityService` — presença, visitas/vendas agregadas, timeline, conexões
- `TeamController` — cards com foto/online/atividade/venda; filtros Todos/Online/Offline; drawer sem GPS
- `operations/team.blade.php` — UI compacta mobile-friendly
- Tests `Sprint8217TeamPresenceActivityTest`
- Docs em `docs/sprint-8217-team-presence-activity/`

## Hotfix presença (pré-publicação)

- Migration `users.last_seen_at` + índice `(company_id, last_seen_at)`
- Middleware `TouchUserPresence` (`presence.touch`) com throttle 2 min
- Presença desacoplada de `sessions` / `SESSION_DRIVER`
- Impersonation não atualiza last_seen do alvo
- Docs `HOTFIX-PRESENCE.md`