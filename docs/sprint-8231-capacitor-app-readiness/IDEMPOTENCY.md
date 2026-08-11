# IDEMPOTENCY

Obrigatório **antes** de sync de venda.

Cada operação offline:

```
Idempotency-Key: {device_id}:{client_operation_id}
```

`client_operation_id` = UUID gerado no enqueue. Nunca regenerar no retry.

Backend: tabela futura `client_operations` (company_id, user_id, key, request_hash, response_status, created_at) — **não criar migration nesta sprint**.

Retry após timeout **não** gera segunda venda/comissão.

Fila atual usa `Date.now()+random` — **insuficiente** (colisão + retry cria outro id).
