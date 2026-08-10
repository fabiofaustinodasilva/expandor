# SECURITY

## Controles

- CSRF (middleware web)
- Rate limit 5/min por e-mail+IP
- Broker throttle 60s
- Token temporário (60 min) + single-use
- Anti-enumeração (mensagem única; sem audit em e-mail inexistente)
- Senha: `Password::defaults()` + `confirmed` (mín. 8)
- Hash bcrypt (`BCRYPT_ROUNDS`)
- Sem senha/token em audit `new_values`
- Sem secrets SMTP na UI de erro
- HTTPS obrigatório em produção (`APP_URL`)

## Sessões após reset

- Sanctum tokens revogados
- `remember_token` regenerado
- Sessões web multi-dispositivo: **não** varridas nesta sprint (ver 8.2.25)

## Logs

Erros: `password_reset.request_failed` / `password_reset.link_not_sent` sem password/token SMTP.
