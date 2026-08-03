# Sprint 5.2.1 — Revisão do fluxo de venda (PDV)

## Causa raiz: “Confirmar venda” não finalizava

| Camada | Problema |
|--------|----------|
| Validação | Default exigia `negotiated_amount` digitado; FormData omitia campo vazio → **422** |
| JS | Erros da API pouco claros; toast genérico no First Approach (“Atendimento registrado”) |
| Total | Valor digitável conflitava com fluxo real de venda |

**Não era falha silenciosa do service/transaction** — a request era rejeitada na validação ou a UX mascarava o erro.

### Correções
- Total = soma automática dos produtos (não digitado)
- `formatApiErrors` concatena todos os erros 422
- Toast de venda no First Approach quando `installation_requested`
- Carrinho com validação de estoque no cliente + servidor

## PDV externo

1. Cliente (campos configuráveis)
2. **+ Adicionar produto** (N linhas: nome, preço, qtd, excluir)
3. **Total** automático
4. Confirmar → estoque, comissões, histórico automáticos

### Backend
- Tabela `sale_items` (snapshots por linha)
- `sales.negotiated_amount` = soma das linhas
- `sales_commissions`: removido `unique(visit_id)`; FK `sale_item_id` (1 comissão por item)
- Idempotência legado: `visit_id` + `product_id` quando sem `sale_item`
- Estoque por linha; produtos sem `stock_control` livres

### UI
- `partials/sale-finalize-fields.blade.php`
- `operational-map.js` v40
- Agenda: mesmo carrinho

## Testes
`php artisan test` — FirstApproach, PilotSellerUx, SalesCommissionModule
