# Auditoria — EXP Vendedor venda completa + handoff

Data: 2026-08-14

## Fluxo mobile antes desta sprint

EXP Vendedor → Novo ponto / Registrar visita → outcomes (cores preservadas) → **Venda realizada** abria um bloco reduzido:

- nome, telefone, WhatsApp, CPF, RG, e-mail
- carrinho de produtos (catálogo API)
- observações

**Não havia:** nascimento, endereço de instalação, chips de vencimento, revisão, tela de sucesso com Sale ID, GET handoff, copiar/ver/WhatsApp do escritório.

## Fonte de verdade (Web)

- `SaleFieldsPolicyResolver` + `SaleDueDays::ALLOWED`
- `RegisterVisitAction` / `RegisterFirstApproachAction` / `CompleteFollowUpAction` → `VisitService`
- `Sale` + `SaleItems` + comissão
- `SaleHandoffData` / `SaleHandoffFormatter` / `SaleHandoffService`
- `office_sales_whatsapp` / `office_sales_whatsapp_enabled`

## Arquivos auditados

| Área | Arquivo |
|---|---|
| Shell | `resources/js/mobile/bootstrap-shell.js` |
| Carrinho | `resources/js/mobile/sale-cart.js` |
| Mapa | `resources/js/mobile/map-adapter.js` |
| API JS | `resources/js/mobile/mobile-api.js` |
| Requests | `MobilePointVisitRequest`, `MobileFirstApproachRequest`, `MobileStoreVisitRequest`, `MobileCompleteFollowUpRequest` |
| Ops | `MobileSellerOpsService` |
| Bootstrap | `GET /api/mobile/v1/bootstrap` (`sale_fields`, `campaign_context`) |
| Capacitor | Geolocation no `package.json`. Clipboard/Browser/App **não** são pacotes npm; o shell usa `Capacitor.Plugins.*` com fallback `navigator.clipboard` / `window.open`. |
| Android | INTERNET + GPS; queries `wa.me` adicionadas nesta sprint |

## Decisão

Estender os endpoints mobile existentes (`first-approach`, `points/{id}/sales`, follow-up complete) com `complete_sale=true` (due_day obrigatório). Legado sem a flag continua com due_day opcional. Handoff via o mesmo `SaleHandoffService` da Web.
