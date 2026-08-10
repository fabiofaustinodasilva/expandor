# SMTP-DEPLOYMENT

## Recomendação operacional

1. **Não** hospedar MTA completo no mesmo VPS sem necessidade.
2. Preferir SMTP transacional (Resend, Postmark, Amazon SES, SendGrid, etc.).
3. Configurar no DNS do domínio remetente: **SPF**, **DKIM**, **DMARC**.
4. Não automatizar DNS nesta sprint.

## Produção (`.env`)

```
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_SCHEME=null
MAIL_FROM_ADDRESS=noreply@seudominio.com
MAIL_FROM_NAME=Expandor
APP_URL=https://app.seudominio.com
```

## Local / CI

`MAIL_MAILER=log` ou `array`.

## Indisponibilidade

Login continua. Forgot password responde mensagem neutra; falha registrada sem vazar credenciais.
