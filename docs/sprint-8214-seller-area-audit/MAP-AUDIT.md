# MAP-AUDIT

## Papel

Mapa = home pós-login (`maps.view`) e centro operacional do seller.

## CTAs sempre visíveis (seller)

- Busca (`#map-search`)  
- **Apresentar produtos** (`#btn-present-products`)  
- **Minha localização** (`#btn-recenter-location`) — só GPS, não cria  
- Basemap  

## Em Mais / escondido do seller no mapa

- Filtros / Métricas (`map-more-tools`) — **manager only**  
- Legend / zoom UI reduzidos via CSS `body.field-seller`  

## Fluxos preservados (não regredir)

- Toque → formulário direto  
- Status com cores (Interessado / Cliente / Retorno / Sem interesse)  
- Offline queue badge  
- Day brief + tips  

## Sempre visíveis? (recomendação futura)

| Sempre | Mais | Sumir do seller |
|--------|------|-----------------|
| Minha localização | Basemap satélite | Filtros admin |
| Toque = criar | — | Métricas complexas |
| Apresentar | — | Team view |
| Busca | — | — |

## Glossário inconsistente no mapa

Cliente / Ponto / Residência / Casa / “Venda realizada” vs “Contratou” — ver ISSUES.

## Pós-salvar

Toast + permanece no mapa (bom). Seller não abre adjust modal.
