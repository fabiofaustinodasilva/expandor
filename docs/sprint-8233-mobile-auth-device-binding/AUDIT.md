# AUDIT — Sprint 8.2.33

Auditoria do fluxo de auth web/API antes da implementação.

## Web

| Peça | Local | Comportamento |
|---|---|---|
| Login | `app/Http/Controllers/Web/Auth/LoginController.php` | Cookie/sessão Laravel. Seller chama `claimSellerLogin` (incrementa `users.session_version` + rotaciona remember). |
| Rotas | `routes/web.php` | `POST /login` throttle:login. `GET/POST /esqueci-minha-senha`. `GET/POST /redefinir-senha`. |
| Middleware 8.2.25 | `EnforceSellerSingleSession` | Compara `session('auth.session_version')` com `users.session_version`. JSON: 401 `session_replaced`. |
| Serviço | `SellerSingleSessionService` | `claimSellerLogin`, `bindCurrentVersion`, `invalidateAllSessions`, `sessionMatches`. |
| Logout web | `LoginController@destroy` | Logout + invalidate session. **Não** incrementa `session_version`. |
| Remember | `Auth::login($user, remember)` | Rotacionado no claim Seller. Não bypassa sessão única (8.2.25). |
| Password reset 8.2.24 | `ResetPasswordController` | `invalidateAllSessions` + `$user->tokens()->delete()`. |

## API existente

| Peça | Local | Comportamento pré-8.2.33 |
|---|---|---|
| `POST /api/v1/auth/login` | `Api\V1\Auth\AuthController` | Token Sanctum nome `api`. Sem device. Sem claim. **Preservado** (não é o app Seller). |
| `GET /api/v1/auth/me` | idem | User/company/permissions. Já tem `seller.single-session`. |
| `POST /api/mobile/v1/login` | `Api\Mobile\V1\Auth\AuthController` | Token nome `mobile`. Sem device. Sem claim. Sem seller-only rígido (só `sales_app.access`). |
| `GET /api/mobile/v1/me` | idem | Transformer user. |
| `POST /api/mobile/v1/logout` | idem | Delete current token. |

## Sanctum

| Peça | Estado |
|---|---|
| Config | `config/sanctum.php` — `expiration` = `null`. |
| Tabela | `personal_access_tokens` (migration 2026_08_02) — sem device/version. |
| Model | Package default até 8.2.33. |
| User | `HasApiTokens`. |
| Stateful | `EnsureFrontendRequestsAreStateful` no grupo `api`. Host `localhost` é stateful. App Capacitor envia Bearer; `api/mobile/*` excluído de CSRF. |

## Sessão única vs Bearer (gap 8.2.31)

Middleware **pulava** requests sem sessão (`!$request->hasSession()`). Token Sanctum **não** participava da regra 1 Seller = 1 sessão.

## Tenancy

- E-mail **é** globalmente único (`2026_08_05_120001`).
- Web login usa `first()` no e-mail (comportamento legado, não alterado).
- App: localiza o usuário pelo e-mail; se a senha bater em mais de um registro legado, exige `company_id`.

## CORS / CSRF

- `config/cors.php` **ausente** até esta sprint.
- Web CSRF intacto. App Bearer não envia CSRF.

## Permissions / papéis

- `Role::SELLER = 'seller'`. Admin/Manager bloqueados no app.
- APIs mobile autenticadas: `permission:sales_app.access` + policies existentes.

## mapFetch

`public/js/operational-map.js` — sessão web + `session_replaced`. **Não misturar** com `apiFetch` do app.

## Decisão 8.2.33

1. Endpoint mobile explícito permanece `POST /api/mobile/v1/login` (já existia).
2. Migration aditiva em `personal_access_tokens` (device + `session_version`).
3. Validação em **toda** request device-bound: header `X-Device-Id` + versão.
4. Não revogar PAT no login — a geração decide. Password reset continua apagando tokens.
