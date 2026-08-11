# CUSTOMER-LIST

Antes: grid de cards (`minmax(280px)`).

Depois: tabela `client-data-table--responsive`.

| Coluna | Fonte |
|--------|--------|
| Cliente | Resident primário |
| Telefone | phone / whatsapp |
| Local / Endereço | Address |
| Status | `CommercialTerminology::customerSituation` |
| Última visita | `AppTime::formatInstant` |
| Última venda | `sales.negotiated_amount` |
| Ações | Ver / Excluir (se permitido) |

Mobile (390): cards via `data-label` (padrão 8.2.30). Busca `q` preservada. Sem filtros novos.
