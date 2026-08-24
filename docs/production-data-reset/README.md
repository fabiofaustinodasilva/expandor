# Production Data Reset (Expandor)

Reset **seguro** de dados tenant/QA para início de uso real em produção.

> Este fluxo **não** usa `migrate:fresh`, **não** dropa schema e **não** apaga planos, Platform Owner, Mercado Pago, roles/permissions ou configurações globais.

## Objetivo

Remover empresas de teste/QA e todo o grafo tenant associado, deixando a plataforma pronta para cadastrar clientes reais do zero.

## O que é preservado

- Empresa sistema (`is_system=true`, tipicamente Expandor Platform)
- Platform Owner (`is_platform_admin`)
- Planos comerciais (catálogo)
- Roles / permissions
- Configurações globais / feature flags estruturais
- Marketplace settings / platform brand
- Payment gateway settings (Mercado Pago)
- Integrações/configurações de plataforma (não tenant)
- Migrations / schema

## O que pode ser removido

Somente empresas **explicitamente** passadas em `--company=ID` (e que não estejam protegidas):

- users, teams, tokens/sessions
- subscriptions, invoices, payments, checkouts
- campaigns, visits, follow-ups
- properties, residents, addresses, cities, sectors
- products, commissions, training progress
- company settings / company integrations
- audit logs **do tenant**
- leads/pipeline tenant ligados à company

Heurística de nomes (`teste`, `demo`, `qa`) serve **apenas** para relatório (`--audit`). **Nunca** autoriza exclusão.

## Empresas que exigem decisão manual

Antes de qualquer `--execute`, revisar manualmente:

| Empresa | Ação sugerida |
|---------|---------------|
| Expandor Platform (`is_system`) | Sempre preservar |
| Única Network / iFF (e-mails reais) | Decisão humana — não auto-apagar |
| Empresas `*.demo` / QA financeiro | Candidatas a `--company=` após confirmação |

## Backup

Em `--execute`, o comando tenta `mysqldump` para:

`storage/app/backups/production-reset/expandor-before-reset-YYYYMMDD-HHMMSS.sql.gz`

- Pasta **não** é versionada no Git.
- Senha do banco vai via `MYSQL_PWD` no processo (não é impressa no log).
- Se o backup falhar → **abort**.
- Alternativa controlada:

```bash
php artisan expandor:production-reset --execute --confirm=RESET-PRODUCTION-DATA \
  --company=ID --skip-backup --confirm-backup-exists=YES
```

Backup externo sugerido:

```bash
mysqldump --single-transaction --routines --triggers -h HOST -u USER DB | gzip > expandor-before-reset.sql.gz
```

## Dry-run (padrão)

```bash
php artisan expandor:production-reset
php artisan expandor:production-reset --company=5 --company=6
php artisan expandor:production-reset --audit
```

Não altera o banco. Mostra contagens e pagamentos (flag DEMO/TEST vs REVIEW).

## Execute

```bash
php artisan expandor:production-reset \
  --execute \
  --confirm=RESET-PRODUCTION-DATA \
  --company=5 \
  --company=6
```

Confirmações:

1. `--execute`
2. `--confirm=RESET-PRODUCTION-DATA`
3. Em `APP_ENV=production`: digitar exatamente `RESET EXPANDOR PRODUCTION DATA`
4. Sem TTY em production: aborta, salvo `--force-no-tty --typed="RESET EXPANDOR PRODUCTION DATA"`

## Verify (somente leitura)

```bash
php artisan expandor:production-reset --verify
```

Checa Platform Owner, empresa sistema, planos, RBAC, órfãos óbvios (users/invoices), config MP.

## Rollback

Não há “undo” automático do purge. O rollback é **restaurar o dump** gerado antes do reset.

## Segurança

- Default = dry-run
- Sem allowlist explícita de IDs → nada é apagado no execute
- Empresas protegidas / sistema / host do Platform Owner → bloqueio
- Sem refund/cancel no Mercado Pago (reset só local)
- Audit log: `platform.production_reset` (IDs, counts, backup path; sem secrets)

## Troubleshooting

| Sintoma | Ação |
|---------|------|
| `mysqldump não encontrado` | Instale no PATH ou use `--skip-backup --confirm-backup-exists=YES` após backup externo |
| Empresa bloqueada | Remova do `--company=` ou ajuste `PRODUCTION_RESET_PROTECTED_COMPANY_IDS` com cuidado extremo |
| Verify falhou (órfãos) | Investigar FKs; não rodar execute novamente às cegas |
| Production sem TTY | Não force sem procedimento; use sessão interativa |

## Checklist produção (dia do reset)

1. Backup externo + confirmação de restore testado
2. `php artisan expandor:production-reset --audit` (revisar MANUAL)
3. Definir lista **explícita** de `--company=`
4. Dry-run com esses IDs
5. Confirmar Platform Owner / planos / MP intactos no dry-run mental
6. Janela de manutenção
7. Execute **somente** com IDs revisados + confirmações
8. `--verify`
9. Smoke: `/platform`, `/platform/companies`, `/platform/billing`, site público, planos
10. **Não** push/merge deste checklist como execução automática

## Testes

```bash
php artisan test --filter=ProductionResetCommandTest
```
