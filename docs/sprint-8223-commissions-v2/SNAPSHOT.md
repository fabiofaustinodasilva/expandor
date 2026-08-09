# Snapshot

## Por que

Alterar produto amanhã (10% → 15% ou R$20 → R$30) **não** pode mudar comissões já criadas.

## O que é congelado na venda

Por `SaleItem` / `SalesCommission`:

- `commission_amount` (resultado)
- `commission_type` (`fixed` | `percentage`)
- `commission_rate` (valor unitário fixo ou percentual)
- `commission_base` (line_total quando %)
- `unit_price`, `quantity`, `line_total`, `product_name`

## Relatórios

Sempre `SUM(commission_amount)` / leitura das linhas.  
**Nunca** `product.commission_*` em queries históricas.

## Legado pré-8223

Linhas sem `commission_type`: valor em `commission_amount` já é o resultado final. Tratar como fixed materializado; **não** backfill recalculando.
