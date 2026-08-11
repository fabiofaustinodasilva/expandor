# SESSION

## Hoje (8.2.25)

`users.session_version` + `auth.session_version` na sessão web.  
Último login Seller vence. Reset de senha bumpa versão e **apaga todos os PATs**.

`EnforceSellerSingleSession`: se `!$request->hasSession()` → **passa**.  
`/api/mobile/v1` **não** monta esse middleware. Login mobile **não** chama `claimSellerLogin`.

**Gap:** Seller pode ter N tokens Bearer vivos + 1 sessão web.

## Estratégia futura (não implementar agora)

Web + App = **um dispositivo válido**.

1. Login app: `claimSellerLogin` equivalente → incrementa `session_version`, revoga PATs anteriores do user, emite **um** token com `device_id` + `name=seller-app`.
2. Middleware API: Bearer deve carregar `session_version` no token abilities/meta **ou** tabela `user_devices` (1 row seller). Pedido com versão velha → `401 session_replaced`.
3. Login web também revoga PAT seller-app.
4. Impersonação continua isenta.

Não emitir PAT “eterno” sem device bind.

## UX `session_replaced`

Limpar Keychain → tela login com `SellerSingleSessionService::REPLACED_MESSAGE`. Sem loop de retry.
