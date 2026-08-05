# Sprint 8.1.9.2 — Data Integrity Repair Engine

## Objetivo

Mecanismo oficial de manutenção da integridade da base Expandor.  
Nenhuma migration deve depender de limpeza manual via SQL.

## Comandos

```bash
php artisan integrity:repair --dry-run
php artisan integrity:repair --execute
php artisan integrity:repair --execute --yes   # CI / não interativo
php artisan company:purge {company_id}
php artisan company:purge 15 --force
```

### `--dry-run`
Gera o relatório completo **sem alterar dados**.

### `--execute`
1. Imprime o relatório  
2. Pergunta `Excluir completamente?` para cada empresa recomendada  
3. Purge via `CompanyPurgeService`  
4. Limpa órfãos  
5. `php artisan migrate --force`  
6. Garante UNIQUE `users.email` e `companies.document`

## Arquitetura

| Peça | Responsabilidade |
|------|------------------|
| `IntegrityScannerService` | Detecta duplicidades, soft-deleted, canceladas, órfãos, índices |
| `CompanyPurgeService` | Remove empresa **só** via Models/Services (ordem de domínio) |
| `OrphanCleanupService` | Remove FKs inconsistentes |
| `UniqueConstraintEnforcer` | Recria UNIQUE após reparo |
| `IntegrityRepairService` | Orquestra dry-run / execute |
| `IntegrityRepairCommand` | CLI `integrity:repair` |
| `CompanyPurgeCommand` | CLI `company:purge` |

## Ordem de purge

Marketplace → Onboarding → CRM/Visits/Sales → Invoices → Subscriptions → Payments → Payment Gateway → Checkout → Usuários → Empresa

## Relatório (exemplo)

```
Integrity Report
Duplicated Emails
  suporte@empresa.com
    Empresa 3
    Empresa 5
Duplicated Documents
  02297318138
    Empresa 5
    Empresa 6
Orphan Records
  users ............
  checkout .........
  payments .........
```

## Migration 8.1.9

Se existirem duplicatas, a migration lança:

> Run: php artisan integrity:repair --execute

## Testes

`tests/Feature/Release/Sprint8192DataIntegrityRepairEngineTest.php` — **8 passed**

- dry-run (e-mail duplicado, sem alterações)
- detecção de documentos duplicados
- execute (purge + migrate)
- company:purge (relacionamentos)
- bloqueio de empresa sistema
- limpeza de órfãos
- migrations após reparo
- soft-deleted / cancelled

## Uso em produção

```bash
php artisan integrity:repair --dry-run
# revisar relatório
php artisan integrity:repair --execute
# confirmar YES/NO por empresa
```
