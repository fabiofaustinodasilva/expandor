# Calculation rules

## Arredondamento

- Escala: 2 casas decimais para valores monetários.
- Modo: `PHP_ROUND_HALF_UP` (`round($x, 2)` em PHP).
- Percentual armazenado com até 4 casas (`decimal(8,4)`); o resultado final da comissão sempre em 2 casas.

## Fixed

```
unit_rate = product.commission_amount
commission = round(unit_rate * quantity, 2)
base = null (ou 0 — não usado no cálculo)
```

## Percentage

```
line_total = round(unit_price * quantity, 2)   // unit_price = preço congelado na venda
commission = round(line_total * (rate / 100), 2)
base = line_total
```

**Base auditada:** `sale_items.line_total` (valor efetivamente registrado na venda), **não** `products.price` atual.

## Exemplos

| Caso | Input | Output |
|------|-------|--------|
| Fixed | rate=20, qty=2 | 40.00 |
| % | price=150, qty=1, rate=10 | base=150, commission=15.00 |
| % negociado futuro* | line_total=130, rate=10 | 13.00 |
| Rounding | 99.90 × 7.5% | round(7.4925, 2) = **7.49** |
| Zero | rate=0 | 0.00 — sem som de moeda |
| Sem config | amount/rate ausente → 0 | venda OK |

\* Hoje o Contratar grava `unit_price` = preço de catálogo no momento; se no futuro houver preço negociado persistido em `line_total`, a % já usa essa base.

## Sem regra

Comissão 0 não bloqueia a venda.
