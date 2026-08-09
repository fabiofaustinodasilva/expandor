# Migration notes

## Forward

Migration aditiva `2026_08_09_200001_add_commission_type_percentage_and_snapshots`:

1. `products.commission_type` default `'fixed'`
2. `products.commission_percentage` nullable
3. Backfill: `UPDATE products SET commission_type = 'fixed'` (valores de `commission_amount` intactos)
4. Snapshot cols em `sale_items` e `sales_commissions`

**Não** drop de colunas. **Não** recalc de `sales_commissions`.

## Rollback

`down()`: drop das colunas novas. Dados legados em `commission_amount` permanecem se rollback parcial for só type/%.

## Impacto

- Zero downtime esperado (nullable + default)
- Código antigo lendo só `commission_amount` continua válido para fixed
