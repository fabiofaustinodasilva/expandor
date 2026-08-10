# SESSION-LIFECYCLE

```
Seller login A → version N, session[key]=N
Seller login B → version N+1, session[key]=N+1
Request em A  → N ≠ N+1 → logout + mensagem + redirect/401
Request em B  → OK

Admin login A+B → mesma version, ambos OK

Password reset → version++
Sessões com version antiga → invalidadas

Logout manual → invalidate local (inalterado)
Expiração natural de sessão → inalterada
```

Mensagem HTML:
`Sua conta foi acessada em outro dispositivo. Por segurança, esta sessão foi encerrada.`

AJAX: `{ "message": "...", "code": "session_replaced" }` status 401.
Mapa: `mapFetch` → toast + redirect `/login`.
