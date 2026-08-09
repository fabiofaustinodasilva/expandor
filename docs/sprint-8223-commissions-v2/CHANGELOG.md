# Changelog — Sprint 8.2.23

## Added

- `commission_type` + `commission_percentage` no Product
- Snapshots type/rate/base em SaleItem e SalesCommission
- `ProductCommissionCalculator` (fixed / percentage, HALF_UP)
- UI de comissão no cadastro de produto com preview
- Payload `commission_awarded` nas respostas de mapa pós-Contratar
- Feedback visual + som local pós-confirmação (idempotente por visit_id)

## Changed

- Cálculo de comissão field-sales passa pelo calculator (não só amount × qty)

## Unchanged (by design)

- CRM `CommissionRule` / Opportunity entries
- Sem cancelamento/estorno novo
- Sem recalc histórico
- Sem push/merge
