# MAIL-ARCHITECTURE

## Princípio

SMTP é **platform-managed** (Expandor). Recuperação de senha **não** depende de SMTP do tenant.

## Configuração

| Variável | Uso |
|----------|-----|
| `MAIL_MAILER` | `log`/`array` local; `smtp` produção |
| `MAIL_HOST` / `MAIL_PORT` | Servidor SMTP |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | Credenciais (secrets de servidor) |
| `MAIL_SCHEME` | `null` (587 STARTTLS) ou `smtps` (465) |
| `MAIL_FROM_ADDRESS` | Remetente (domínio autenticado) |
| `MAIL_FROM_NAME` | Nome (Expandor) |

Laravel 11+ usa `MAIL_SCHEME` em vez de `MAIL_ENCRYPTION` legado.

## Envio

- `ResetPasswordNotification` (extends Laravel `ResetPassword`)
- Envio síncrono na request (sem worker obrigatório)
- Falha SMTP: log seguro + UI neutra; login permanece intacto

## Fora de escopo

Campanhas, marketing, SMTP por empresa, painel de secrets.
