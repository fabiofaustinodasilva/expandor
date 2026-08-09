# Data model — Comissões 2.0

## Product (configuração viva — só vendas futuras)

| Coluna | Tipo | Notas |
|--------|------|-------|
| `commission_type` | string enum `fixed` \| `percentage` | default `fixed` |
| `commission_amount` | decimal(12,2) | valor fixo por unidade (legado + fixed) |
| `commission_percentage` | decimal(8,4) nullable | 0–100 quando type=percentage |
| `price` | decimal | preço de lista (base de unit_price na venda) |

## SaleItem (snapshot por linha)

| Coluna | Tipo | Notas |
|--------|------|-------|
| `unit_price` | decimal | preço no momento da venda |
| `quantity` | int | |
| `line_total` | decimal | **base %** = unit_price × qty |
| `commission_amount` | decimal | valor calculado da linha |
| `commission_type` | string nullable | snapshot |
| `commission_rate` | decimal nullable | valor fixo unitário OU percentual |
| `commission_base` | decimal nullable | base usada (line_total para %; null/0 para fixed) |
| `product_name` | string | nome congelado |

## SalesCommission (snapshot / pagamento)

| Coluna | Tipo | Notas |
|--------|------|-------|
| `commission_amount` | decimal | **fonte da verdade** para relatórios |
| `commission_type` | string nullable | |
| `commission_rate` | decimal nullable | |
| `commission_base` | decimal nullable | |
| `quantity` | int | |
| `sale_item_id` | FK unique | idempotência |
| `status` | pending/approved/paid | |

## CRM (paralelo — não misturar)

- `commission_rules.percent` → `commission_entries` em Opportunity.
- Sem FK para Product field-sales nestes cálculos.
