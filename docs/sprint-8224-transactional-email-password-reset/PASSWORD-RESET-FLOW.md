# PASSWORD-RESET-FLOW

```
Login → Esqueceu sua senha?
  → GET /esqueci-minha-senha (password.request)
  → POST /esqueci-minha-senha (password.email) [throttle:password-reset]
      → se user ativo: PasswordBroker::sendResetLink
      → e-mail Expandor com link
      → UI: mensagem neutra (sempre)
  → GET /redefinir-senha/{token}?email=... (password.reset)
  → POST /redefinir-senha (password.update)
      → Password::defaults() + confirmed
      → hash + remember_token novo
      → revoga tokens Sanctum
      → audit succeeded
      → redirect login (sem auto-login)
```

## Rotas

| Nome | Método | Path |
|------|--------|------|
| `password.request` | GET | `/esqueci-minha-senha` |
| `password.email` | POST | `/esqueci-minha-senha` |
| `password.reset` | GET | `/redefinir-senha/{token}` |
| `password.update` | POST | `/redefinir-senha` |

## Controllers

- `ForgotPasswordController`
- `ResetPasswordController`

## Token

- Tabela `password_reset_tokens`
- Hash armazenado; expira em 60 min
- Single-use após reset bem-sucedido
- Novo pedido invalida token anterior (broker)

## Papéis

Seller, manager, admin e Super Admin usam o **mesmo** fluxo.
