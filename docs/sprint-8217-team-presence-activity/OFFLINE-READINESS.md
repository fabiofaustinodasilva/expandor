# OFFLINE-READINESS

Não implementa Capacitor/offline nesta sprint.

| Hoje | Futuro app |
|------|------------|
| Presença = `sessions.last_activity` (web) | Endpoint leve `presence/ping` → preferir `users.last_seen_at` |
| Sanctum API não atualiza session web | App deve reportar last_seen no sync |
| Offline = sem atividade recente | Continua offline localmente; ao sync atualiza presença |
| Sem heartbeat excessivo | Ping a cada 1–2 min em foreground basta |

Migration futura mínima (se necessário): `users.last_seen_at nullable timestamp`.
