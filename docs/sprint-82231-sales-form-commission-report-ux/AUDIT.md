# AUDIT — Sprint 8.2.23.1

## Causa do formulário cortado

Containers: `#visit-modal` e `#point-modal` em `resources/views/maps/index.blade.php`.

**Antes:**
- Painel único com `max-height` + `overflow-y: auto` no wrapper inteiro (header + campos + CTA).
- Em `#visit-modal`, no desktop não havia max-height; no mobile field-seller CSS forçava `88dvh` + scroll no painel inteiro.
- `#point-modal` usava `sticky` no footer **dentro** do mesmo overflow — sticky falha com frequência em mobile quando o teclado abre / bottom sheet `items-end`.
- Resultado: conteúdo “Finalizar venda” + CTA ficavam inacessíveis sem zoom.

## Correção

Estrutura `.map-sheet-panel`:
- header `flex-shrink: 0`
- body `flex:1; min-height:0; overflow-y:auto`
- footer `flex-shrink: 0` (CTA sempre visível)
- `max-height: min(100dvh, 100%)` / `92dvh` desktop
- safe-area no footer

## Valor da venda

- Fonte: `sale_items.line_total` (eager `saleItem`)
- Fallback: `sales_commissions.commission_base` (snapshot %)
- Legado sem vínculo: `—` (nunca `Product.price`)
- Multi-item: 1 comissão = 1 `SaleItem` → mostra `line_total` daquele item

## Migrations

Zero — `line_total` já existe.
