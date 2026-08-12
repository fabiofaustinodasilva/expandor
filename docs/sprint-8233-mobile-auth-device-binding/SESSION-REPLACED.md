# SESSION-REPLACED

HTTP 401

```json
{
  "success": false,
  "message": "Sua conta foi acessada em outro dispositivo. Por segurança, esta sessão foi encerrada.",
  "code": "session_replaced"
}
```

App: limpa token + estado, mostra a mensagem **uma vez**, volta ao login. Sem loop (`handlingUnauthorized`).

Audit: `auth.mobile_session_replaced` com `device_id` hasheado — nunca o token.
