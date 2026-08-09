# OFFLINE-READINESS

Não implementa Capacitor/offline nesta sprint.

| Hoje (hotfix) | Futuro app |
|------|------------|
| Presença = `users.last_seen_at` via middleware web throttled | Mesmo campo; endpoint leve `presence/ping` ou middleware API |
| Independente de SESSION_DRIVER | App reporta last_seen no sync/foreground |
| Offline = null / > 5 min | Continua offline localmente; ao sync atualiza `last_seen_at` |
| Throttle 2 min | Ping a cada 1–2 min em foreground basta |

Coluna já criada pelo hotfix: `users.last_seen_at`.
