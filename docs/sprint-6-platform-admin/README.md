# Sprint 6 — Platform Admin Management v1

Painel administrativo do Platform Owner expandido sem alterar tenancy, trial, CRM, vendas ou permissões de tenants.

## Escopo

- Perfil do Owner (nome, e-mail, senha, foto)
- Gestão de empresas (editar, suspender, ativar, exclusão lógica, restaurar)
- Visualização de assinatura e usuários (show)
- Reset de senha do administrador da empresa
- Auditoria das novas ações
- Middleware `platform.admin` exige `is_platform_admin=true`

## Migration

`2026_08_03_270001_add_platform_company_archive_metadata.php`
- `companies.deleted_by`
- `companies.deletion_reason`

(`deleted_at` / SoftDeletes e `photo`/`photo_thumb` já existiam.)
