# Production Data Reset (Expandor)

Reset **seguro** de dados tenant/QA e leads demo da Central Comercial para início de uso real em produção.

> Este fluxo **não** usa `migrate:fresh`, **não** dropa schema e **não** apaga planos, Platform Owner, Mercado Pago, roles/permissions, stages/config do funil, settings do site ou templates.

## Objetivo

Remover empresas de teste/QA e/ou marketplace leads QA/demo **selecionados por ID**, deixando a plataforma pronta para clientes e tráfego reais.

## O que é preservado

- Empresa sistema (`is_system=true`, tipicamente Expandor Platform)
- Platform Owner (`is_platform_admin`)
- Planos comerciais (catálogo)
- Roles / permissions
- Configurações globais / feature flags estruturais
- Marketplace settings / platform brand / templates comerciais
- Stages/config do funil (enum/config — não registros de lead)
- Lógica UTM / site público / formulários
- Payment gateway settings (Mercado Pago)
- Integrações/configurações de plataforma (não tenant)
- Migrations / schema

## O que pode ser removido

### Empresas (`--company=ID`)

Somente IDs explícitos (não protegidos): users, teams, tokens, billing tenant, campaigns, mapa, CRM tenant, etc.

### Marketplace leads (`--lead=ID`)

Somente IDs explícitos. Remove o lead e relações:

- `marketplace_sales_pipeline` (registro do lead, não a config de stages)
- `marketplace_lead_scores`
- `marketplace_lead_activities` (timeline/notes)
- `marketplace_lead_notifications`
- `marketplace_events` com `lead_id` do lead

Heurística (`demo_form`, nomes teste) serve **apenas** para `--audit`. **Nunca** autoriza exclusão no execute.

## Backup

Em `--execute`, o comando tenta `mysqldump` para:

`storage/app/backups/production-reset/expandor-before-reset-YYYYMMDD-HHMMSS.sql.gz`

- Pasta **não** é versionada no Git.
- Se o backup falhar → **abort**.
- Alternativa: `--skip-backup --confirm-backup-exists=YES`

## Dry-run (padrão)

```bash
php artisan expandor:production-reset
php artisan expandor:production-reset --company=5
php artisan expandor:production-reset --lead=1 --lead=2 --lead=3 --lead=4
php artisan expandor:production-reset --audit
```

Não altera o banco. Para leads, mostra nome, provedor, source, status, stage, demo scheduled e contagens relacionadas.

## Execute

Empresas:

```bash
php artisan expandor:production-reset \
  --execute \
  --confirm=RESET-PRODUCTION-DATA \
  --company=5 \
  --company=6
```

Leads QA (exemplo IDs 1–4):

```bash
php artisan expandor:production-reset \
  --execute \
  --confirm=RESET-PRODUCTION-DATA \
  --lead=1 \
  --lead=2 \
  --lead=3 \
  --lead=4
```

Pode combinar `--company=` e `--lead=` no mesmo execute.

Confirmações (inalteradas):

1. `--execute`
2. `--confirm=RESET-PRODUCTION-DATA`
3. Em `APP_ENV=production`: digitar exatamente `RESET EXPANDOR PRODUCTION DATA`
4. Sem TTY em production: aborta, salvo `--force-no-tty --typed="RESET EXPANDOR PRODUCTION DATA"`

## Verify (somente leitura)

```bash
php artisan expandor:production-reset --verify
```

Checa Platform Owner, empresa sistema, planos, RBAC, marketplace settings, órfãos (users/invoices + pipeline/score/timeline/events de lead), config MP.

## Limpeza de checkouts QA

```bash
php artisan expandor:production-reset --checkout=1 --checkout=2
php artisan expandor:production-reset --execute --confirm=RESET-PRODUCTION-DATA \
  --checkout=1 --checkout=2 --checkout=3 --checkout=4 --checkout=5 --checkout=6
```

Bloqueia checkouts pagos/provisionados. Dry-run por padrão.

## Subscription da Expandor Platform

A empresa sistema **não** precisa de subscription comercial.
Entitlements internos usam plano sintético full-access.

```bash
php artisan expandor:platform:purge-system-subscriptions
php artisan expandor:platform:purge-system-subscriptions \
  --execute --confirm=PURGE-SYSTEM-SUBSCRIPTION
```

## Catálogo oficial

Seed: **Start / Pro / Scale** apenas. Legados (`free`, `professional`, `enterprise`, `enterprise-legacy`) não são recriados.
Trial (`ACQUISITION_TRIAL_PLAN`) padrão: `start`.

## Checklist limpeza de leads em produção

1. Backup
2. `--audit` (revisar lista heurística)
3. Dry-run: `--lead=1 --lead=2 --lead=3 --lead=4`
4. Confirmar que nenhum lead real está na lista
5. Execute com confirm + frase production
6. `--verify`
7. Smoke: Central Comercial / site / formulário demo

## Rollback

Restaurar o dump gerado antes do reset.

## Troubleshooting

| Sintoma | Ação |
|---------|------|
| `mysqldump não encontrado` | Backup externo + `--skip-backup --confirm-backup-exists=YES` |
| Lead missing no execute | Remova ID inexistente da lista |
| Verify falhou (órfãos) | Investigar FKs; não reexecutar às cegas |

## Testes

```bash
php artisan test --filter=ProductionResetCommandTest
```
