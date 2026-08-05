# Permissions — Roles, policies e inconsistências

## Roles tenant

| Role | Slug | Perfil |
|------|------|--------|
| Administrador | `administrator` | Tudo do tenant |
| Gerente | `manager` | Quase admin; sem company/branding/onboarding.manage |
| Supervisor | `supervisor` | Equipe + campo + CRM; sem company.manage |
| Vendedor | `seller` | Campo + CRM view + comissão própria |
| Visualizador | `viewer` | Só `dashboard.view` |

Fonte: `RolePermissionSeeder.php` + `User::hasPermission()` (overrides + herança coarse).

## Matriz resumida (capacidades)

| Capacidade | Admin | Manager | Supervisor | Seller | Viewer |
|------------|:-----:|:-------:|:----------:|:------:|:------:|
| dashboard.view | ✓ | ✓ | ✓ | ✓ | ✓ |
| maps / visits / campaigns.view | ✓ | ✓ | ✓ | ✓ | |
| customers.view | ✓ | ✓ | ✓ | ✓ | |
| customers.manage | ✓ | ✓ | ✓ | | |
| crm.* | ✓ | ✓ | ✓ | ✓ | |
| commissions.manage | ✓ | ✓ | ✓ | | |
| commissions.view_self | ✓ | ✓ | ✓ | ✓ | |
| users.manage | ✓ | ✓ | | | |
| company.manage | ✓ | | | | |
| billing.view | ✓ | ✓ | | | |
| ai.access | ✓ | ✓ | ✓ | ✓ | |
| sales_app.access | ✓ | ✓ | ✓ | ✓ | |
| reports.view | ✓ | ✓ | ✓ | | | ← **sem UI** |

## Policies

~28 policies em `app/Domains/*/Policies`.  
Registro: `AppServiceProvider`.

### Inconsistências

| Item | Problema | Risco |
|------|----------|-------|
| `CustomerPolicy` | Usada manualmente; **não** em `Gate::policy` | Autorização fácil de esquecer |
| `SalesAppPolicy` | Existe; **não** registrada; controllers checam slug | Duplicação de padrão |
| Web vs API | Web quase sem `permission:` middleware; API usa em poucas rotas | Superfície desigual |
| Menus vs Policies | Menu pode mostrar item; action pode 403 (ou o inverso em deep links) | UX / segurança |
| Feature flags | `ai.enabled`, `whatsapp.enabled` **não** ligam em menus | Seller vê AI/WhatsApp mesmo se flag off no platform |
| PlanCatalog features | `crm`, `stock`, `ai`… packing de plano **não** esconde nav | Tenant em plano básico vê módulos “premium” |
| Viewer | Quase cego; rail/sidebar ainda podem confundir | Menu vazio vs dashboard only |

## Rotas expostas (atenção)

- Deep links de edição CRM/territory acessíveis por URL se autenticado + policy.
- Checkout/webhooks públicos (fora tenant) — ok, mas não misturar com sessão tenant.
- Impersonation: exit no tenant; start só platform.

## Menus incorretos / ações no lugar errado

- “Usuários (técnico)” só no Mais/sidebar — deveria estar sob Equipe.
- “Clientes / Pontos” sob Comercial na sidebar vs “Clientes” no rail — labels diferentes.
- Produtos sob Configurações **e** menu Comissões.

## Ações duplicadas

- Ativar/inativar usuário: Equipe **e** Users toggle.
- Follow-up complete: web **e** sales-app.
- Training complete: admin content **e** sales-app.
