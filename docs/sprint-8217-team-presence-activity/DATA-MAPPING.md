# DATA-MAPPING

| UI | Fonte | Notas |
|----|-------|-------|
| Foto | `users.photo_thumb` → `photoUrl()` | Fallback iniciais |
| Online | `users.last_seen_at` ≤ 5 min | Middleware throttle 2 min; independente de session driver |
| Offline | null ou fora da janela | |
| Label acesso | `last_seen_at` ou `last_login_at` | Distinto de login |
| Último login | `users.last_login_at` | |
| Conexões | `audit_logs` login + `last_seen_at` | Sem "Saiu" |
| Última visita | `visits` agregada | `commissionClientLabel` + status |
| Última venda | `sales` via visit | product_name / amount |
| Timeline | visitas recentes | VISITA / RETORNO / VENDA |
| Hoje | `seller_productivity` | visited_at timezone app |
