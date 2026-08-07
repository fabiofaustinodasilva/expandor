# AUDIT — Sprint 8.2.10

## Problema

`RETURN_LATER` e `NO_INTEREST` compartilhavam `MapCommercialGroup::VISITED` → mesma cor amarela `#eab308` nos pins.

## Onde estava definido

| Camada | Arquivo | Papel |
|--------|---------|-------|
| Grupo filtro | `MapCommercialGroup` | VISITED = return + no_interest |
| Cor | `MapMarkerColor::forStatus` | Antes: delegava ao grupo |
| Payload | `MapQueryService::toMarker` | `color` + `commercial_group` |
| Fallback JS | `map-provider.js` | groupOf → visited |
| Render | `operational-map.js` `coloredIcon` | usa `marker.color` |
| Legenda | `MapCommercialGroup::legend` + Blade | listava “Visitado” único |
| Filtro API | `MapRepository::resolveStatusFilter` | `commercial_groups=visited` |

## O que mudou (apresentação)

- Cores por status em `MapMarkerColor::forStatus`.
- Legenda com Retorno / Sem interesse.
- Marcas R / × no pin e na legenda.
- Legenda do vendedor: painel recolhível à direita (não cobre Minha localização).

## O que NÃO mudou

- PropertyStatus / VisitStatus / banco.
- `commercial_group=visited` no payload.
- Filtro `commercial_groups=visited`.
- Fluxo Meu Local / toque / salvar (8.2.9).
- Auth, tenancy, billing, MP.
