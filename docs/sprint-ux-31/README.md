# Sprint UX 3.1 — Mapa limpo Seller

## Objetivo

Mapa do vendedor com foco em venda na rua. Sem funcionalidades novas — só reorganização visual.

## Prints

- `antes-seller.png` — mapa seller antes
- `depois-seller.png` — mapa limpo + Filtros / Camadas / Legenda
- `depois-manager.png` — manager com mapa completo (filtros, camadas, equipe, região)

## Comportamento

### Seller
- Fora da tela principal: filtros, camadas “Mostrar”, legenda, visão da equipe, região atual
- Botões recolhidos: **Filtros**, **Camadas**, **Legenda**
- Visível: mapa, Próxima casa, Adicionar ponto, Rua/Satélite compacto

### Manager/Admin
- Mapa completo: filtros, camadas, equipe, indicadores

## Arquivos

- `resources/views/maps/index.blade.php`
- `public/js/operational-map.js` (`?v=33`)
- `tests/Feature/Maps/MapsModuleTest.php`
- `docs/sprint-ux-31/*`

## Testes

```
96 passed (547 assertions)
```

Sem alteração de banco, regras, permissões ou APIs.
