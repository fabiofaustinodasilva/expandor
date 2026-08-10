# Sprint 8.2.24 — E-mail transacional + recuperação de senha

Branch: `feature/sprint-8224-transactional-email-password-reset`  
Sem push / sem merge. Não inicia 8.2.25.

## Objetivo

Self-service de recuperação de senha com SMTP **platform-managed** (Expandor), independente de integrações do tenant.

## Entregue

- Link “Esqueceu sua senha?” no login
- Fluxo forgot → e-mail → reset (anti-enumeração)
- Notification pt-BR Expandor
- Rate limit `password-reset`
- Audit: `auth.password_reset_requested` / `auth.password_reset_succeeded`
- Zero migrations (reuso de `password_reset_tokens` + PasswordBroker)

## Fora de escopo

- SMTP tenant-managed / marketing / campanhas
- Painel Super Admin para secrets SMTP
- Capacitor / deep links (só documentação)
- Sessão única do vendedor (8.2.25)
