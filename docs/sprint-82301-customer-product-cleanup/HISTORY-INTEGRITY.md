# HISTORY-INTEGRITY

| Ação | Visitas | Vendas | Comissões | Ponto |
|------|---------|--------|-----------|-------|
| Excluir cliente sem visita | — | — | — | Soft-deleted (some da UI/mapa) |
| Tentar excluir cliente com visita | intactas | intactas | intactas | intacto |
| Excluir produto nunca usado | — | — | — | n/a |
| Tentar excluir produto usado | `product_id` intacto | SaleItem snapshot intacto | snapshot intacto | n/a |
| Desativar produto | — | histórico ok | ok | some do catálogo futuro |

Nenhum `forceDelete` de Property. Nenhum cascade novo.
