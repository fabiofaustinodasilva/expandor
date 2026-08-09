# DATA-MAPPING

| UI | Fonte | Notas |
|----|-------|-------|
| Foto | `users.photo_thumb` → `photoUrl()` | Fallback iniciais |
| Online | `MAX(sessions.last_activity)` ≤ 5 min | Sem heartbeat extra |
| Offline | sem sessão recente | |
| Label acesso | sessão recente ou `last_login_at` | Distinto de login |
| Último login | `users.last_login_at` | |
| Conexões | `audit_logs` login + sessão atual | Sem "Saiu" |
| Última visita | `visits` agregada | `commissionClientLabel` + status |
| Última venda | `sales` via visit | product_name / amount |
| Timeline | visitas recentes | VISITA / RETORNO / VENDA |
| Hoje | `seller_productivity` | visited_at timezone app |
