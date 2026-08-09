# Rule precedence (auditada)

## Field-sales (Contratar / FirstApproach) — v1

Única fonte de verdade **no momento da venda**:

1. **Snapshot histórico** já persistido (nunca sobrescrever / nunca recalc)
2. **Configuração do Product** (`commission_type` + amount/%) × quantity, base = `line_total` da linha

Não consultado neste fluxo:

- CRM `CommissionRule`
- Campanha / vendedor / equipe overrides (não existem hoje)

## CRM Opportunity — paralelo

1. `CommissionRule` ativa da empresa (`percent`)
2. Gera `CommissionEntry` no win

**Não aplicar** hierarquia campaign > seller > product > default por suposição — isso **não** existe no código Contratar.

## Conflito Product vs CRM

Domínios separados. Unificação = sprint futura, com especificação explícita.
