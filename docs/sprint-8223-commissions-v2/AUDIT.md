# Sprint 8.2.23 — Auditoria Comissões 2.0

**Data:** 2026-08-09  
**Branch:** `feature/sprint-8223-commissions-v2`  
**Veredito:** **GO (escopo field-sales)** — base confiável existe; CRM rules são domínio paralelo.

---

## Respostas (1–30)

### 1. Onde comissão do produto é armazenada hoje?
`products.commission_amount` (decimal, valor **fixo** por unidade). Sem tipo nem percentual.

### 2. Existe `commission_amount`?
Sim — em `products`, `sale_items` e `sales_commissions`.

### 3. Existe `commission_percentage`?
Não no produto field-sales. CRM tem `commission_rules.percent` (Opportunity only).

### 4. Existe tipo de comissão?
Não no Product (antes desta sprint). CRM usa percent implícito.

### 5. Como comissão é calculada hoje?
`round(product.commission_amount * quantity, 2)` em `VisitService::normalizeSaleCartLines` e `GenerateVisitCommissionAction`.

### 6. Em que momento é criada?
No mesmo transaction de `VisitService::register` quando status = `installation_requested` (Contratar / FirstApproach).

### 7. Qual evento/status dispara comissão?
`VisitStatus::INSTALLATION_REQUESTED` → cria `Sale` + `SaleItem` + `SalesCommission`.

### 8. Existe snapshot no Sale/SaleItem/Commission?
Sim parcial:
- `sale_items`: `unit_price`, `line_total`, `commission_amount`, `product_name`
- `sales_commissions`: `commission_amount`, `product_name`, `quantity`
- **Falta:** tipo, taxa %, base usada

### 9. Alterar produto hoje altera histórico?
Não — comissão já persistida em `sale_items` / `sales_commissions`. Relatórios somam snapshot.

### 10. Como regras de comissão funcionam?
Dois mundos:
1. **Field sales:** produto → venda visita
2. **CRM:** `CommissionRule` % → `CommissionEntry` em Opportunity ganha

### 11. Regras por produto / campanha / vendedor / equipe / empresa?
- Produto: sim (`commission_amount`)
- Campanha / vendedor / equipe: **não** no fluxo Contratar
- Empresa: CRM `CommissionRule` por `company_id` (Opportunity)

### 12. Qual regra ganha em conflito?
Não há conflito no mesmo calculator. Field sales **não** consulta CRM rules.

### 13. Existe comissão padrão?
Default produto = `0` se não informado.

### 14. Existe override manual?
Não no Contratar. Seller não envia valor de comissão.

### 15. Como descontos/preço final são armazenados?
`sales.negotiated_amount` = soma dos `line_total`. Input negociado no front **não** altera unit_price hoje (preço catálogo × qty).

### 16. SaleItem possui unit_price/final_price?
`unit_price` + `line_total`. Sem coluna `final_price`.

### 17. Qual valor real da venda para %?
**Confiável:** `sale_items.line_total` (= `unit_price * qty` no momento da venda).  
Preferência produto (usar valor efetivo) atendida por essa base — **não** preço live do catálogo.

### 18. Comissão 1x por venda ou pode duplicar?
1x por `sale_item_id` (unique / lock). Legado: visita+produto.

### 19. Há idempotência?
Sim — `GenerateVisitCommissionAction` com `lockForUpdate` + early return.

### 20–21. Cancelamento / estorno?
Não há fluxo de cancelamento/estorno de comissão field-sales. Preservar ausência.

### 22. Status pago/pendente?
`SalesCommissionStatus`: pending / approved / paid.

### 23. Relatórios somam como?
`SUM(commission_amount)` nas linhas persistidas — sem recalc.

### 24–25. Vendedor / manager vê?
Seller: página/listagens de comissão própria. Manager: agregados por empresa/equipe via repository + policies.

### 26. Tenancy/policies
`company_id` em Sale/SaleItem/SalesCommission/Product. Policies CRM e Sales existentes; seller não edita comissão do produto sem permissão de catálogo.

### 27. Migrations necessárias
Aditivas:
- `products.commission_type`, `products.commission_percentage`
- snapshots type/rate/base em `sale_items` e `sales_commissions`  
Backfill: `commission_type = fixed` (valores intactos).

### 28. Compatibilidade legado
Produtos sem type → `fixed`. Comissão histórica sem snapshot type → tratar como fixed já materializado (não recalcular).

### 29. Riscos financeiros
- Misturar CRM % com Product (evitar)
- Recalc histórico (proibido)
- Float (usar decimal + HALF_UP)
- % sobre preço live (proibido — usar line_total)

### 30. Plano mínimo
1. Schema type + % + snapshots  
2. Calculator  
3. VisitService + GenerateVisitCommissionAction  
4. Product form + validation  
5. JSON `commission_awarded` + UX seller  
6. Testes + docs  

---

## Stop conditions

| Condição | Resultado |
|----------|-----------|
| Sem base confiável de valor vendido | N/A — `line_total` existe |
| Conflito Product vs CommissionRule no mesmo fluxo | N/A — domínios paralelos; v1 não unifica |

**GO para implementação escopada.**
