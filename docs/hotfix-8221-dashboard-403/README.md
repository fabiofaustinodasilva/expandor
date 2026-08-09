# Hotfix — 403 no /dashboard após 8.2.21

## Causa raiz

1. **Gate do 403:** `DashboardController::__invoke` — `abort_unless(hasPermission('dashboard.view'), 403, 'Access denied.')`  
   Arquivo: `app/Http/Controllers/Web/Dashboard/DashboardController.php` (antes ~L33–37).
2. **Por que surgiu na 8.2.21:** `RolePermissionSeeder` usa `permissions()->sync()`. Se o `whereIn(slug)` retornar conjunto incompleto/vazio, o sync **remove todas** as permissões da role (incluindo `dashboard.view`). A sprint aumentou a chance de re-seed em deploy (nova `integrations.manage`).
3. **Segundo vetor:** Super Admin sem `dashboard.view` ao cair em `/dashboard` (`redirectUsersTo('/dashboard')` / navegação) também recebia o mesmo 403 — mais visível após a Central de Integrações da plataforma.

## Correção

- Guard no `RolePermissionSeeder`: recusa sync destrutivo se faltar permission row.
- Migration aditiva `EnsureIntegrationsPermissionsSeeder` (`syncWithoutDetaching`) reancora `dashboard.view` + `integrations.*`.
- Platform admin em `/dashboard` → redirect para `/platform` (não 403).
- `redirectUsersTo` distingue platform admin.

## Permissões

| Role | Antes (risco) | Depois |
|------|---------------|--------|
| Administrator | podia perder `dashboard.view` via sync vazio | repair aditivo garante `dashboard.view` + integrations |
| Platform admin | 403 em `/dashboard` | redirect `/platform` |

Integrações: `integrations.manage` continua obrigatório para configurar; cross-tenant intacto.
