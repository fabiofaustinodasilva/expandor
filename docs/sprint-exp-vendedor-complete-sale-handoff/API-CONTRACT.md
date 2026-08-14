# Contrato API mobile — venda completa + handoff

## Reutilizados (sem segundo service)

- `POST /api/mobile/v1/first-approach`
- `POST /api/mobile/v1/points/{point}/visits`
- `POST /api/mobile/v1/points/{point}/sales` (alias do visit)
- `POST /api/mobile/v1/follow-ups/{id}/complete`
- `GET /api/mobile/v1/bootstrap` — agora inclui `sale_fields.due_days`
- `GET /api/mobile/v1/products`
- `GET /api/mobile/v1/markers` / `points/{id}`

## Novos (mesmo `SaleHandoffService` da Web)

- `GET /api/mobile/v1/sales/{sale}/handoff`
- `POST /api/mobile/v1/sales/{sale}/handoff/copied`
- `POST /api/mobile/v1/sales/{sale}/handoff/opened`

## Flag `complete_sale`

Quando `true` e `status=installation_requested`:

- `due_day` required ∈ {5,10,15,20,25,30}
- `customer_birth_date` required
- `install_street` required

Sem a flag: comportamento legado (due_day nullable).

## Payload de venda (domínio já usado na Web)

`customer_name`, `customer_document`, `customer_birth_date`, `customer_phone`, `customer_whatsapp`, `install_street`, `install_number`, `install_neighborhood`, `install_reference`, `install_city`, `due_day`, `items[]`, `campaign_id`.

Resposta 2xx inclui `sale_id`, `commission_awarded`, `office_handoff` (`SaleHandoffData::toArray()`).

Mensagens 422: primeiro erro humano (`Escolha o dia de vencimento.`, etc.). Envelope ainda tem `code: validation_error` e `errors`, mas a UI usa `message`.
