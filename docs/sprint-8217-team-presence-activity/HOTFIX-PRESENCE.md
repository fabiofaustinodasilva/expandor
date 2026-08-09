# HOTFIX — presença sem SESSION_DRIVER

## Migration

`2026_08_09_000001_add_last_seen_at_to_users_table.php`

- `users.last_seen_at` TIMESTAMP NULL
- índice `users_company_last_seen_at_index` (`company_id`, `last_seen_at`)
- rollback: drop index + coluna

## Presença

| Campo | Significado |
|-------|-------------|
| `last_login_at` | Último LOGIN |
| `last_seen_at` | Última ATIVIDADE |
| `audit_logs` auth.login_succeeded | Histórico de LOGIN |

- **Online:** `last_seen_at >= now() - 5 min`
- **Throttle write:** middleware `TouchUserPresence` (`presence.touch`) só grava se null ou ≥ **2 min**
- **Independente** de `SESSION_DRIVER` (produção pode permanecer `file`)
- Impersonation: **não** atualiza `last_seen_at` do usuário alvo
- Sem GPS / sem heartbeat agressivo
- Futuro Capacitor: mesmo campo; endpoint ping pode reutilizar depois

## Métrica Vendas

Sem alteração: `installation_requested` continua = venda fechada.
