# Pendências — Sprint 5.5.5

Itens **fora do escopo** desta sprint (auditoria/polimento apenas) ou que exigem decisão de produto:

| Item | Motivo |
|------|--------|
| Unificar 100% das telas no layout operacional | Refatoração de UI maior; risco de regressão visual |
| Captcha / Turnstile no cadastro | Feature de segurança nova |
| Tela de conversão trial para empresa **suspensa** (allowlist middleware) | Mudança em tenancy/`EnsureTenantIsActive` |
| Renomear enums internos (`installation_requested`) | Regra de domínio — valores internos devem permanecer |
| Remover alias `/teste-gratis` | Mantido de propósito (compat Sprint 5.5.3) |
| Dual sistema de comissões (`commissions.*` vs `crm.commissions.*`) | Arquitetura existente; não consolidar sem sprint dedicada |
| Smoke E2E browser completo (Landing→Venda→Comissão) | Requer ambiente com dados e QA manual |

## Segurança (revisão)

Policies/Gates/CSRF/tenant isolation: sem alteração de regras.  
Uploads e validações: mantidos como nas sprints 5.4.x / 5.5.x.

## Testes

Rodar suíte completa após o polimento. Qualquer falha deve ser tratada como regressão.
