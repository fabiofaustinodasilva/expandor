# SELLER-AUDIT

## Jornada

Mapa → toque → Novo ponto → marker → Registrar visita → (venda) Confirmar venda → reward.

Pós-8.2.26: sheets unificados; empty não bloqueia; GPS opcional; footer CTA corrigido (hotfix).

## Pontos positivos

- Map Operation Sheet coerente.
- Reward de comissão estável.
- Sessão única com `mapFetch`.
- FAB “Minha localização” claro.

## Gaps de polish

| Item | Severidade |
|------|------------|
| Markers círculos | P1 |
| CTAs `sky-500` ≠ brand | P1 |
| Drawer “📍 Residência” / “casa” | P1 glossário |
| Day brief com emojis | P2 |
| Tips “Voltar ao mapa” (deck) | P2 |
| Uso com uma mão: FAB/bottom controls ok; drawer lateral em mobile vira bottom sheet — ok |

## Hierarquia visual

Primário deveria ser: mapa + ação de campo.  
Secundário: busca / Hoje / basemap.  
Evitar competição tipográfica entre brief, tips e sheet.

## Uma mão (mobile)

- Targets ≥44px na maior parte dos CTAs de sheet.
- Bottom bar operacional + sheet `z-index:100` — ok pós-8.2.26.
- Teclado: body scroll interno — validar iOS real (P1 manual).
