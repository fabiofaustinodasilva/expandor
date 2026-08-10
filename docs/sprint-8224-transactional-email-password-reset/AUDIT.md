# AUDIT — Sprint 8.2.24

## Respostas (30)

| # | Pergunta | Resposta |
|---|----------|----------|
| 1 | Já existe “Esqueci minha senha”? | **Não** (antes). Criado nesta sprint. |
| 2 | Rota `password.request`? | **Não** → criada `/esqueci-minha-senha` |
| 3 | Rota `password.email`? | **Não** → criada POST |
| 4 | Rota `password.reset`? | **Não** → criada `/redefinir-senha/{token}` |
| 5 | PasswordBroker? | **Sim** (`config/auth.php` passwords.users) |
| 6 | Tabela `password_reset_tokens`? | **Sim** (migration base) |
| 7 | Token expira? | **60 minutos** (`auth.passwords.users.expire`) |
| 8 | Token single-use? | **Sim** (Laravel remove após reset) |
| 9 | Reset invalida token anterior? | Novo pedido substitui; reset consome |
| 10 | Reset invalida sessões antigas? | **Parcial**: revoga Sanctum + rotaciona `remember_token`. Sessão web multi-device completa → 8.2.25 |
| 11 | Troca de senha (profile) invalida sessões? | Não auditado como completo — sem duplicar 8.2.25 |
| 12 | Usuário precisa ter e-mail? | **Sim** (obrigatório + unique global) |
| 13 | Seller sempre possui e-mail? | **Sim** (schema) |
| 14 | Usuários sem e-mail? | **Não** (coluna required) |
| 15 | Super Admin recupera? | **Mesmo fluxo** (User + PasswordBroker) |
| 16 | Queue para mail? | Default sync/`QUEUE_CONNECTION=sync`; reset **síncrono** via notify |
| 17 | `MAIL_MAILER` atual? | `log` local; produção `smtp` (exemplo deploy) |
| 18 | SMTP configurado? | Via `.env` platform-managed |
| 19 | Domínio remetente? | `MAIL_FROM_ADDRESS` |
| 20 | Nome remetente? | `MAIL_FROM_NAME` / APP_NAME |
| 21 | Templates? | MailMessage Laravel + `ResetPasswordNotification` |
| 22 | Audit password reset? | **Sim** (requested / succeeded) |
| 23 | Rate limit? | `throttle:password-reset` 5/min email\|IP + broker throttle 60s |
| 24 | Anti-enumeração? | Mensagem neutra sempre |
| 25 | Fluxo revela e-mail? | **Não** |
| 26 | Policies/tenancy? | Guest; `TenantScope` sem company não filtra; reset por e-mail global |
| 27 | Migrations? | **Zero** |
| 28 | Riscos? | SMTP prod misconfig; atraso se queue futura; sessões web remanescentes até 8.2.25 |
| 29 | Capacitor? | Documentado; não implementado |
| 30 | Plano mínimo? | Rotas + controllers + notification + views + rate limit + testes + docs |

## Reutilizado

PasswordBroker, `password_reset_tokens`, `Password::defaults()`, branding login, `SecurityService::recordAudit`, hash bcrypt.
