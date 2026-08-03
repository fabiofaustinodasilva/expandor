# Sprint 5.0 — Comissões + Estoque Comercial

## Objetivo

Módulo comercial genérico: produto controla comissão, estoque e venda vinculada a visita/vendedor/cliente.
Preparação para vender planos, equipamentos e produtos físicos — não exclusivo de ISP.

## Decisões

| Tema | Decisão |
|---|---|
| Catálogo | Estende `products` (`commission_amount`, `stock_control`, `stock_quantity`, `minimum_stock`) |
| Lançamentos | Nova `sales_commissions` (não mistura com CRM `commission_entries`) |
| Estoque | `stock_movements` (`entry` / `sale` / `adjustment`) — nunca altera estoque sem movimento |
| Contratou | Exige `product_id`; `visits.plan` = nome do produto (snapshot textual) |
| CRM % | Mantido intacto (`/crm/commissions`) |

## Fluxo Contratou

```
Produto ativo + estoque OK (se controlado)
→ salva Visit (product_id + plan)
→ gera SalesCommission (pending, snapshot)
→ baixa estoque + StockMovement tipo sale
```

## Permissões

| Slug | Quem |
|---|---|
| `commissions.manage` | Admin, Manager, Supervisor |
| `commissions.view_self` | Seller (+ gestores) |

Seller **não** cria produto, altera comissão/estoque, nem vê equipe.

## Telas

- Configurações → **Produtos / Comissões**
- Seller → **Minha comissão** (`/comissoes`)
- Manager → **Comissões** + estoque/alertas
- Resultados → 💰 Comissões (link real)

## Migrations

- `2026_08_03_100001_extend_products_for_commissions_and_stock`
- `2026_08_03_100002_create_stock_movements_table`
- `2026_08_03_100003_create_sales_commissions_table`
- `2026_08_03_100004_add_product_id_to_visits_table`

## Testes

```
php artisan test --filter=SalesCommissionModuleTest
php artisan test
```

Validação: **10** testes Commissions (47 assertions) + suite completa **160 passed** (863 assertions).
