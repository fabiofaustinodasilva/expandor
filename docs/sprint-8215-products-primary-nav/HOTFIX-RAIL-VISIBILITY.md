# HOTFIX — Produtos não aparecia no rail real

## Causa raiz

O Dashboard usa `layouts.operational` → `layouts.partials.client-rail` → `ClientNav::railItems()`.

A 8.2.15 **acertou o componente certo**, mas o item usava módulo `stock` com:

```php
'stock' => ['permissions' => ['commissions.manage'], 'plan_feature' => 'stock']
```

Em planos com features ativas e `stock = false` (ex.: Free), `NavVisibility` filtrava Produtos do rail, enquanto Financeiro (`commissions`, sem plan_feature stock) e o CRUD (`ProductPolicy` / `commissions.manage`) continuavam acessíveis.

## Correção

Remover `plan_feature => 'stock'` de `NavVisibility` para o módulo `stock`, alinhando o rail ao mesmo critério do CRUD administrativo.
