# Sprint UX 3.2 — Polimento final piloto de campo

## Objetivo

Ajustes visuais e de usabilidade antes do primeiro uso real. Sem módulos novos, banco ou regras.

## Prints

- `seller-brief.png` — resumo do dia + Começar rota
- `seller-mapa.png` — Próxima casa em destaque + mapa limpo
- `seller-drawer.png` — Nome / Telefone / Situação + Registrar visita

## Mudanças

1. Tela inicial seller com ícones (🏠 ⭐ 📄) e **👣 Começar rota**
2. **Próxima casa** maior, com pulso e padding do mapa para não cobrir marcadores
3. Drawer: Nome → Telefone → Situação → ações → Mais opções
4. Textos: Residência, Situação, Nova oportunidade
5. Toasts mantidos: Ponto salvo / Visita registrada / Cliente atualizado

## Arquivos

- `resources/views/maps/index.blade.php`
- `public/js/operational-map.js` (`?v=34`)
- `tests/Feature/Maps/MapsModuleTest.php`
- `tests/Feature/Maps/PilotSellerUxTest.php`
- `docs/sprint-ux-32/*`

## Testes

```
96 passed (552 assertions)
```
