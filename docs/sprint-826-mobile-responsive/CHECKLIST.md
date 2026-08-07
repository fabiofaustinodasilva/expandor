# Checklist — Sprint 8.2.6

## Breakpoints a validar manualmente

| Viewport | Largura | Foco |
|----------|---------|------|
| iPhone SE | 360 | Drawer, cards, forms |
| iPhone 12/13 | 390 | Idem |
| Pixel | 412 | Idem |
| iPad portrait | 768 | Grid 2 col, drawer |
| iPad landscape / small laptop | 1024 | Rail desktop |
| Notebook | 1366 | Layout pleno |
| Desktop | 1920 | Layout pleno |

## Por tela

| Tela | Sem overflow-x | Nav usável | Forms 100% | Tabelas/cards | Modais OK |
|------|----------------|------------|------------|---------------|-----------|
| Dashboard | ☐ | ☐ | ☐ | ☐ | ☐ |
| Mapa | ☐ | ☐ | ☐ | — | ☐ |
| Campanhas | ☐ | ☐ | ☐ | ☐ | ☐ |
| Pontos | ☐ | ☐ | ☐ | ☐ | ☐ |
| Leads | ☐ | ☐ | ☐ | ☐ | ☐ |
| CRM hub | ☐ | ☐ | ☐ | ☐ | ☐ |
| Equipe | ☐ | ☐ | ☐ | ☐ | ☐ |
| Financeiro | ☐ | ☐ | ☐ | ☐ | ☐ |
| Atalhos análise | ☐ | ☐ | — | — | — |
| Configurações | ☐ | ☐ | — | — | — |
| Mais | ☐ | ☐ | — | — | — |
| Agenda | ☐ | ☐ | ☐ | — | ☐ |

## Critérios de aceite (automatizados)

- [x] `op-nav-toggle` / `op-nav-drawer` / `op-nav-backdrop` no dashboard
- [x] `client-mobile.js` carregado em operational e app
- [x] CSS contém tokens 8.2.6 e modo card
- [x] Campanhas, Financeiro, Relatórios, Equipe, Settings, Mais, Pontos, CRM, Leads, Mapa respondem 200

## Critérios de aceite (manual)

- [ ] Nenhum scroll horizontal em 360–412px nas telas acima
- [ ] Menu fecha ao tocar fora e após navegar
- [ ] Botões ≥ 44px de altura útil
- [ ] Mapa: drawer de ponto abre de baixo
- [ ] Landscape phone: conteúdo não fica sob a barra

## Como testar rápido

1. Chrome DevTools → device toolbar → 390×844
2. Login admin → Dashboard → Menu → navegar Campanhas
3. Repetir como seller no Mapa
4. Em `/properties` (app shell) abrir Menu e fechar no backdrop
