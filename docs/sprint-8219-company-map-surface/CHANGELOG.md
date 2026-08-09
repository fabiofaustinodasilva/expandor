# CHANGELOG — 8.2.19

## Changed (manager map only)
- Filtros (cidade/setor/campanha/vendedor/situação + camadas + área·campanha) em painel sob demanda `[Filtros · N]`
- Legenda fechada por padrão; abre via `[Legenda]` (Escape / clique fora / X)
- Painel flutuante top-left “Mostrar” removido da superfície permanente (foi para Filtros)
- Exclusividade: Filtros ↔ Legenda ↔ Mais
- Apresentar produtos (azul) saiu do stack inferior no manager → menu Mais (causa da sobreposição com legenda)
- Zoom Leaflet permanece bottom-left com área livre
- Cache-bust `operational-map.js?v=51`

## Preserved
- Lógica de filtros/markers/API
- Painel direito Equipe · hoje (desktop)
- Seller chrome (GPS, Apresentar, Hoje)
