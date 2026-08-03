# Sprint UX 2.8 — Jornada Real do Vendedor em Campo

## Entrega

Fluxo de rua no mapa: Próxima casa → abrir ponto → visita rápida → próxima casa.

## Prints

- `proxima-casa.png` — botão principal + drawer com distância
- `visita-rapida.png` — modal “Como foi?” com chips

## Testes

```
90 passed (495 assertions)
```

## Arquivos

- `resources/views/maps/index.blade.php`
- `public/js/operational-map.js`
- `tests/Feature/Maps/MapsModuleTest.php`
- `docs/sprint-ux-28/*`

## Comportamento

1. **Próxima casa** — prioriza `new` → `return_later` → `interested`, por proximidade GPS (Leaflet `distanceTo`), preferindo pontos do seller.
2. **Drawer** — nome, telefone, status, distância; ações Ligar / WhatsApp / Rota / Registrar visita.
3. **Visita rápida** — chips VisitStatus existentes (sem tabela nova).
4. **Pós-visita** — modal “Visita salva” + “Próxima casa”.
5. **Seller** — esconde painel de métricas, filtros comerciais e extras do drawer.
