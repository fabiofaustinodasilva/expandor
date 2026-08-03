# Sprint 4.0 — Central da Equipe

## Arquitetura utilizada

Camada comercial sobre o domínio existente — **sem módulo paralelo de usuários**.

| Necessidade | Reuso |
|---|---|
| Criar / editar / inativar / senha | `UserService` (+ auditoria) |
| Autorização | `UserPolicy` (`users.manage` / `users.view`) |
| Perfis | Roles `seller` / `supervisor` / `manager` via `CommercialProfileCatalog` |
| Permissões na UI | Descrição amigável das permissions da Role (Opção A) |
| Métricas | `DashboardMetricsService` + `user_id` |
| Cidade / região | Derivadas da campanha (`campaign_users` → city/sectors) |
| Última localização | Última `Visit` com lat/lng |
| Tenancy | `BelongsToTenant` / `TenantScope` |

Manager ganhou `users.manage` no seeder. Módulo técnico `/users` ficou restrito a **Administrator**.

## Prints

- `equipe-cards.png` — cards da equipe
- `novo-vendedor.png` — drawer criar
- `permissoes.png` — permissões comerciais (somente leitura)
- `desempenho.png` — desempenho do vendedor

## Testes

```
TeamHubTest: 9 passed
Suite completa: 105 passed (591 assertions)
```

## Observação demo

Após deploy local, rode `php artisan db:seed --class=RolePermissionSeeder` para aplicar `users.manage` ao Manager em bases já existentes.
