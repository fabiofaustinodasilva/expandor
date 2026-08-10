# CHANGELOG

## Sprint 8.2.26

- Map Operation Sheet: min-height:0, z-index 100, CTAs padronizados
- Copy Novo ponto / Registrar visita / Confirmar venda
- Empty state não bloqueante (hint 1×)
- Drawer passivo com sheet aberto
- `operational-map.js?v=57`
- Testes `Sprint8226FieldFlowUxTest`

## Hotfix — footer CTA cortado

- Causa: `.map-operation-btn-* { width:100% }` + row flex + `min-width:7.5rem` no Cancelar
- Correção: `width:auto` / `flex:1 1 0%` + `min-width:0` em ≥480px; stack abaixo disso
- Sem bump JS (só CSS/Blade)
