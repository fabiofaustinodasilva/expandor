# Changelog — Sprint 8.2.7

## Added
- Fluxo **Meu Local** com GPS, `centerMapOnCoords` e marcador rascunho.
- `Sprint827MapUxSimplificationTest`.
- Docs `docs/sprint-827-map-ux-simplification/`.

## Changed
- Labels “Novo ponto” → **Meu Local** (UI + título do modal).
- Clique no mapa abre formulário direto (sem confirm).
- Mensagem GPS: “Não foi possível obter sua localização.”
- Pós-visita: “Continuar no mapa” (não dispara mais próxima casa).
- Brief/tips do vendedor alinhados ao novo fluxo.
- `operational-map.js?v=42`.

## Removed
- Botão e wrap “Próxima casa”.
- Modal “Casa sem cadastro” (UI).

## Compatibility
- Endpoints de criação de ponto / first-approach inalterados.
- Função interna `goToNextHouse` permanece no JS (não exposta na UI).
