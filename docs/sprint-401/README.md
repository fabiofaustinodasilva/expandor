# Sprint 4.0.1 (refino) — Regras de operação × permissões administrativas

## Objetivo

O gerente configura a empresa. O vendedor trabalha. Funcionalidades essenciais de campo **não** são checkboxes — são comportamento padrão do Expandor.

## Separação

| Camada | Onde vive | Exemplos |
|---|---|---|
| **Regras de operação** | Role Seller + `FieldOpsPolicy` (empresa) | Ver mapa, visitas, ajustar próprio ponto, fluxo de campo |
| **Política da empresa** | `company_settings` via `FieldOpsPolicyResolver` | Visibilidade / exibição / editar outros / excluir |
| **Permissões administrativas** | Role + `permission_user` (overrides) | Equipe, campanhas, resultados, sistema |

Overrides em slugs de **núcleo operacional** são ignorados (`CommercialProfileCatalog::operationalCoreSlugs`) — deny não tira o fluxo do vendedor.

## Matriz administrativa (UI)

| Grupo | Item | Slug |
|---|---|---|
| EQUIPE | Criar / Editar / Inativar / Resetar senha / Gerenciar permissões | `users.create` · `users.update` · `users.deactivate` · `users.reset_password` · `users.manage_permissions` |
| CAMPANHAS | Criar / Editar / Encerrar | `campaigns.create` · `update` · `close` |
| RESULTADOS | Ver equipe / Exportar | `reports.view` · `reports.export` |
| SISTEMA | Configurações / Integrações / Dashboard Gerencial | `company.manage` · `integrations.view` · `dashboard.team` |

`users.manage` permanece como pai grosseiro (compatibilidade).

## Operação de Campo

**Configurações → Operação de Campo**

| Chave | Valores | Padrão |
|---|---|---|
| `field.points_visibility` | `company` · `team` · `own` | `company` |
| `field.points_display` | `all` · `active_only` · `pending_only` · `interested_only` · `customers_only` · `all_with_filters` | `all_with_filters` |
| `field.points_edit_others` | `nobody` · `supervisor` · `manager` · `administrator` | `nobody` |
| `field.points_delete` | `nobody` · `creator` · `supervisor` · `manager` · `administrator` | `creator` |

Aplicado no mapa via `MapQueryService::constrainForUser` (tenancy). Gerente/Admin veem todos os pontos (bypass de visibilidade).

### Futuro por campanha

`FieldOpsPolicyResolver::resolve($companyId, ?$campaignId)` já aceita `campaignId`. Hoje ignora e usa só empresa (`scope=company`). Overrides por campanha entram sem mudar callers.

## Central da Equipe

Badge no card: **Perfil padrão** ou **Permissões personalizadas · N ajustes**.

## Arquivos principais

- `app/Domains/Company/Support/CommercialProfileCatalog.php`
- `app/Domains/Company/Support/FieldOps/*`
- `app/Domains/Company/Policies/UserPolicy.php`
- `app/Domains/Sales/Properties/Policies/PropertyPolicy.php`
- `app/Domains/Maps/{DTOs,Repositories,Services}/*`
- `app/Http/Controllers/Web/Operations/{FieldOperationsSettingsController,SettingsController,TeamController}.php`
- `resources/views/operations/{field-operations,settings,team}.blade.php`
- `database/seeders/RolePermissionSeeder.php`
- `tests/Feature/Operations/{FieldOperationsSettingsTest,TeamPermissionOverridesTest}.php`

## Prints

- `equipe-badges-perfil.png` — badge Perfil padrão
- `permissoes-admin-apenas.png` — drawer só administrativo
- `configuracoes-hub.png` — card Operação de Campo
- `operacao-de-campo.png` — visibilidade / exibição / edição
- `operacao-de-campo-exclusao.png` — exclusão de pontos

## Testes

```
116 passed (645 assertions)
```

Inclui `FieldOperationsSettingsTest` (5) e `TeamPermissionOverridesTest` atualizado (6).
