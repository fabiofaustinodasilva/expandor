# CHANGELOG — 8.2.30.1

- Clientes: tabela compacta + empty-state 8.2.30
- `DELETE /clientes/{property}` — soft-delete só sem visitas
- Produtos: filtro Ativos/Desativados/Todos (default Ativos)
- `hasLinkedSales()` cobre sale/item/visit
- Confirmações e copy de bloqueio
- Audit `customer.deleted` (product.deleted já existia)

Não alterado: comissões, estoque (além do cascade já existente em delete de produto **não usado**), mapa JS, Capacitor.
