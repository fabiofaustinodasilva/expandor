# Segurança — Completar auditoria (sem mudar comportamento)

> Sprint 8.2.1 · Checklist e gaps.  
> **Nenhuma** alteração de middleware/policies nesta sprint de docs.  
> Correções mecânicas (registro Gate, nav flags) → 8.2.2 sem mudar regras de negócio.

## Controles que permanecem (ok)

- `tenancy.initialize` + `tenancy.active`  
- `TenantScope` / `BelongsToTenant`  
- Policies + `authorize` na maioria dos controllers  
- `permission:` em rotas API selecionadas  
- Unique email / document (8.1.9+)

## Gaps confirmados (de 8.2.0)

| Gap | Severidade | Ação proposta 8.2.2 | Muda comportamento? |
|-----|------------|---------------------|---------------------|
| `CustomerPolicy` não no Gate | Média | `Gate::policy` | Não (já autoriza manual) |
| `SalesAppPolicy` não no Gate | Média | Registrar + usar `authorize` | Não se mesma permissão |
| Menus sem flag/plano | Média | `NavVisibility` | Só esconde o que já “não deveria” usar |
| Web sem `permission:` middleware uniforme | Baixa | Opcional; policies bastam se auditadas | Evitar double-deny |
| `withoutGlobalScopes` em Web tenant | Média | Grep + review | Só se bug real |
| Viewer vê chrome confuso | Baixa | Menu mínimo | UX |
| Deep links sem menu | Info | Continuar 403 via policy | — |

## Menus e rotas protegidos (modelo alvo)

```
Request menu item
  → NavVisibility (perm ∧ flag ∧ plan)
Request page
  → auth + tenancy
  → authorize/policy (inalterado)
  → opcional: abort se plan feature off (espelha menu)
```

## Tenant isolation — checklist de testes (8.2.2)

1. User company A não GET resource company B (properties, visits, customers, commissions).  
2. Map markers só da company da sessão.  
3. Impersonation: audit log + exit.  
4. Company suspended/cancelled: `tenancy.active` bloqueia.

## Fora de escopo

- Mudar guards Sanctum  
- Alterar webhooks públicos  
- Mudar matriz de roles/permissions seed  
- Integrity purge (já 8.1.9.2)

## Entregável desta fase

Documento aprovado = lista fechada de **gaps** para a 8.2.2 executar sem surpresa de escopo.
