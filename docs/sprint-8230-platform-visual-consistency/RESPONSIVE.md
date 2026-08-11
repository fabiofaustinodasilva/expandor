# RESPONSIVE

## Viewports obrigatórios

320×568 · 360×640 · **390×844** · 430×932 · 768×1024 · 1024×768 · 1280×720 · **1366×768** · 1440×900 · 1920×1080

Prioridades: **390×844** (Seller campo) e **1366×768** (Manager).

## Primitivas

- Rail → drawer mobile (`op-mobile-bar`)
- Page header empilha em ≤640px
- Tabelas: `.client-data-table--responsive` (cards) ou overflow-x
- Sheets: `items-end` no mapa (8.2.26)
- Touch: `--client-touch: 44px`
- Grids: 4→2→1

## Field mode

Alto contraste via ThemeService; CTA óbvio no sheet; textos curtos; uma mão (bottom sheet). Sem textos longos novos em operação.
