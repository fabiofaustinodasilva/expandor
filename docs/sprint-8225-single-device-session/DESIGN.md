# DESIGN — Sprint 8.2.25

## Estratégia

Coluna aditiva `users.session_version` (unsigned int, default 0).

1. **Seller login:** `INCREMENT` atômico (`lockForUpdate`) + rotaciona `remember_token` + grava versão na sessão (`auth.session_version`).
2. **Admin/Manager/Platform login:** **não** incrementa; apenas faz bind da versão atual (multi-device OK).
3. **Password reset (qualquer papel):** incrementa versão → sessões antigas falham no middleware.
4. **Middleware `seller.single-session`:** se sessão ≠ user → logout, mensagem, redirect ou JSON 401.
5. **Impersonação:** skip.
6. **API sem sessão (bearer):** skip (mapa usa cookie).

## Último login vence

Não bloqueia o segundo login. Login B sobe a geração; A falha na próxima request.

## Concorrência

`lockForUpdate` + incremento serializa dois logins quase simultâneos; a maior geração prevalece.

## Seller identificado por

`Role::SELLER` (`'seller'`), via `SellerSingleSessionService::isSeller()` — não por permission.
