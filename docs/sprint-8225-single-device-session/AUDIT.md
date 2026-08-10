# AUDIT — Sprint 8.2.25

## Fluxo encontrado

| Peça | Achado |
|------|--------|
| Login | `LoginController@store` — Auth::login + session regenerate; sem invalidação multi-device |
| Logout | `LoginController@destroy` — invalidate + regenerateToken |
| Middleware auth | `auth` + tenancy + `presence.touch` (web) |
| SESSION_DRIVER | local `file`; prod exemplo `redis`; default config `database` |
| Tabela `sessions` | Existe; `user_id` só populado com driver `database` |
| Sanctum | Mapa web = cookie/sessão (SPA stateful em `/api/v1/maps/markers`); PATs = mobile separado |
| remember_token | Rotacionado no password reset 8.2.24 |
| Seller slug | `Role::SELLER = 'seller'` (confirmado; não presumido) |
| Detecção Seller | `$user->role?->slug === Role::SELLER` / `SellerSingleSessionService::isSeller` |
| Password reset | Hash + remember + Sanctum delete; **não** invalidava sessão web até esta sprint |
| Map AJAX | Sem tratamento 401/419 (corrigido com `mapFetch`) |
| Presence 8.2.17 | `last_seen_at` em `users` — sem conflito |
| Coluna sessão | Não existia `session_version` |

## Conclusão

Apagar rows em `sessions` **não** funciona em file/redis. Estratégia: `users.session_version` + middleware.
