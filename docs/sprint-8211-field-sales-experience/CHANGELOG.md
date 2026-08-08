# CHANGELOG — Sprint 8.2.11

## GPS / Mapa

- Removidos CTAs **Meu Local** (header, side, empty state)
- Mantido único **Minha localização** (`#btn-recenter-location`)
- Lock anti-duplo-clique + label **Localizando...**
- Erros: permissão, timeout, indisponível, sem suporte
- Localização **não** cria ponto; toque no mapa continua criando
- Cache bust `operational-map.js?v=47`

## Status

- Formulário e visit-quick usam cores/marcas de `MapMarkerColor`
- Interessado = azul; Cliente/instalação = verde; Retorno/Sem interesse = 8.2.10

## Comissão

- Cliente/Ponto via `VisitHistoryPresenter::commissionClientLabel`
- Fallback: **Ponto sem cliente** (não mais street “Local GPS”)

## Produtos

- Campos: category, benefits, sort_order, video_url
- Empresa: form CRUD estendido
- Vendedor: lista + apresentação no Sales App (`Produtos`)
- Inativos e cross-tenant bloqueados pela Policy/controller
