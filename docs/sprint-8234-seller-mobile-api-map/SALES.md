# Vendas

Confirmar venda = visita `installation_requested` em `POST /points/{id}/sales` (mesmo service da web).

Payload: items[], qty, customer_name/phone, notes.  
O app **não** envia valor de comissão.

2xx devolve sale_id, total, items, commission_awarded, commission_id/amount, status.  
Reward/som só depois desse 2xx.
