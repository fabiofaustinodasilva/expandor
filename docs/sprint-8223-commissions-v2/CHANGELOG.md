# Changelog — Sprint 8.2.23

## Hotfix (reward UX)

- Overlay dedicado de recompensa no mapa seller
- Payload `commission_awarded` com `commission_id` + `awarded`
- Flash one-time no GET do mapa
- WAV local de moedas regenerado
- Cache bust `operational-map.js?v=55`
- Sem alteração de cálculo/snapshot/fixo/%

## Added

- `commission_type` + `commission_percentage` no Product
- Snapshots type/rate/base em SaleItem e SalesCommission
- `ProductCommissionCalculator` (fixed / percentage, HALF_UP)
- UI de comissão no cadastro de produto com preview
- Payload `commission_awarded` nas respostas de mapa pós-Contratar
- Feedback visual + som local pós-confirmação (idempotente)
- `Sprint8223CommissionsV2Test` + `Sprint8223CommissionRewardHotfixTest`

## Unchanged (by design)

- CRM `CommissionRule` / Opportunity entries
- Sem cancelamento/estorno novo
- Sem recalc histórico
