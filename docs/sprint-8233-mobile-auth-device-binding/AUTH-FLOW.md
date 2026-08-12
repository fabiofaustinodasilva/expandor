# AUTH-FLOW

## Web Seller

1. `POST /login` (cookie + CSRF)
2. Se `Role::SELLER` → `claimSellerLogin` incrementa `session_version`
3. `Auth::login` + `bindCurrentVersion`
4. Requests web: middleware compara sessão vs `users.session_version`

## App Seller

1. Shell gera/persiste `device_id` (UUID v4)
2. `POST /api/mobile/v1/login` `{ email, password, device_id, device_name?, platform?, app_version? }`
3. Resolve tenant (e-mail + senha; `company_id` se ambíguo)
4. Bloqueia não-Seller
5. `claimSellerLogin` (mesma geração da web)
6. Cria PAT `seller-app` ability `seller-app` + metadata device/version
7. Resposta: `{ token, user, company, permissions, session }`
8. Token no SecureAuthStorage (nunca localStorage)

## Startup do app

1. `getToken()`
2. `GET /api/mobile/v1/me` com Bearer + `X-Device-Id` + `X-App-Version`
3. 200 → entra
4. 401 `session_replaced` / `unauthenticated` → limpa token → login
5. Sem rede → mensagem; offline real não existe

## Logout app

`POST /api/mobile/v1/logout` revoga **o token atual**, limpa storage, volta ao login.

## Canais cruzados

| Primeiro | Depois | Resultado |
|---|---|---|
| App A | App B | A recebe `session_replaced` |
| Web | App | Web cai no próximo hit |
| App | Web | App recebe `session_replaced` |
