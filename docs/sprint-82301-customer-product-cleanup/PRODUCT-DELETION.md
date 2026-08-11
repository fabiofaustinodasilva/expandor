# PRODUCT-DELETION

Já existia `DELETE commissions.products.destroy` + `hasLinkedSales()`.

## 8.2.30.1

- `hasLinkedSales()` também olha `sale_items`, `sales`, `visits.product_id` (não só comissões).
- Confirmação específica para produto nunca usado.
- Usado: “Excluir indisponível” + tooltip. Desativar continua.
- Filtro **Ativos** (default) / Desativados / Todos.
- Desativado não entra em `sellableOptions()` (já era assim).

SoftDeletes em Product: **não**. Hard-delete só se nunca usado; justificativa de migration não se aplica.
