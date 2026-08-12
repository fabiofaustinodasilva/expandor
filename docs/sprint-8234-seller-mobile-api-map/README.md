# Sprint 8.2.34 — Seller mobile API + GPS / map adapter

O app Capacitor passa a operar online os fluxos Seller principais via `/api/mobile/v1`, reutilizando serviços de domínio. Offline, SQLite e fila de sync continuam fora de escopo.

## Entrega

- Bootstrap público do app
- Markers + bbox + config de mapa
- Points: lista, busca, detalhe, criação
- Visitas, retorno, agenda, venda e comissão
- Produtos vendáveis e resultado existente
- LocationService (web) + adapter Capacitor Geolocation (foreground)
- MapAdapter Leaflet no shell
- Navegação mínima: Mapa, Agenda, Clientes, Resultado, Comissão, Mais

## Fora de escopo

Offline, SQLite, sync, Idempotency-Key, push, background GPS, mapa nativo, redesign, alteração de comissão/venda/MapMarkerColor/sessão única, 8.2.35.

## Branch

`feature/sprint-8234-seller-mobile-api-map`
