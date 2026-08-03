# Sprint UX 3.0 — Preparação Piloto Real Expandor

## Entrega

Preparação do fluxo de campo da vendedora: tela inicial, primeiros passos, cadastro rápido, pós-venda (Contratou), fila offline, feedback e auditoria. Sem módulos novos.

## Prints

- `seller-tips.png` — orientação de primeiro uso (3 passos + Pular)
- `seller-day-brief.png` — resumo do dia + **Começar rota**
- `cadastro-rapido.png` — Nome / Telefone / Observação + GPS
- `visita-contratou.png` — plano + observação no Contratou

## Testes

```
95 passed (534 assertions)
```

Inclui `tests/Feature/Maps/PilotSellerUxTest.php`.

## Arquivos alterados

- `database/migrations/2026_08_02_160000_add_plan_to_visits_table.php`
- `app/Domains/Visits/Models/Visit.php`
- `app/Domains/Visits/Requests/StoreVisitRequest.php`
- `app/Domains/Visits/Services/VisitService.php`
- `app/Http/Controllers/Web/Maps/MapVisitController.php`
- `app/Http/Controllers/Web/Maps/MapPointController.php`
- `resources/views/maps/index.blade.php`
- `public/js/operational-map.js` (`?v=32`)
- `public/js/field-offline-queue.js` (`?v=3`)
- `tests/Feature/Maps/PilotSellerUxTest.php`
- `tests/Feature/Maps/MapsModuleTest.php`
- `docs/sprint-ux-30/*`

## Comportamento

1. **Tela inicial seller** — ao entrar no mapa: nome, visitas/interessados/contratos do dia, próxima casa, botão Começar rota.
2. **Primeira experiência** — tips locais (localStorage), 3 passos, Pular permitido.
3. **Cadastro rápido** — seller vê Nome, Telefone, Observação; GPS automático; rua default `Local GPS` se vazia.
4. **Contratou** — exige plano + observação opcional; coluna `visits.plan` para instalação futura.
5. **Offline** — fila sincroniza `point.create`, `point.update`, `visit.create` ao voltar online; badge de pendentes.
6. **Feedback** — toasts: Ponto salvo / Visita registrada / Cliente atualizado / Contrato registrado.
7. **Auditoria** — `visit.registered`, `point.created`, `point.updated` (+ PropertyHistory com lat/lng/horário/vendedor).
8. **Mobile** — botões grandes, brief/tips full-bleed, endereço oculto no seller, menos rolagem.

## Demo

```
seller@unicanetwork.demo / password
```

Após migrate: `php artisan migrate`
