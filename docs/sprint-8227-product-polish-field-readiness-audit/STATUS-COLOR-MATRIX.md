# STATUS-COLOR-MATRIX

Fonte: `MapMarkerColor` + `MapCommercialGroup` + labels comerciais.

| Status / grupo | Label UI | Cor | Hex | Marca | Onde aplica |
|----------------|----------|-----|-----|-------|-------------|
| NEW | Novo | Vermelho | `#ef4444` | — | mapa, badge, legenda |
| INTERESTED | Interessado | Azul | `#3b82f6` | — | mapa, visita, cards |
| RETURN_LATER | Retorno | Laranja | `#f97316` | `R` | mapa, agenda |
| NO_INTEREST | Sem interesse | Slate | `#64748b` | `×` | mapa |
| CUSTOMER / INSTALLATION_REQUESTED | Cliente / instalação | Verde | `#22c55e` | — | mapa, carteira |
| visited (filtro agregado) | Visitado | Amarelo grupo `#eab308` | — | filtros manager |

## Regras

1. Uma cor = um significado em **todas** as superfícies.  
2. Marcas `R`/`×` obrigatórias (acessibilidade daltonismo).  
3. Contraste: fill + stroke branco/escuro no pin.  
4. Não inventar roxo/rosa para status de campo sem atualizar enum.

## House pin (futuro)

Mesma matriz; só muda a forma do marker/legenda.
