# Sprint 4.3.1 — Corrigir botão Detalhes no Histórico

## Problema

O botão **Detalhes** não abria o modal: o payload JSON ia em `data-card` (emojis + URLs `wa.me`), o que quebrava o `JSON.parse` no browser.

## Correção

- Payload em `<script type="application/json" id="history-cards-data">`
- Botão usa só `data-visit-id`
- JS dedicado: `public/js/visit-history.js` (padrão modal Agenda)
- Drawer reorganizado: Cliente · Contato · Local · Atendimento · Complementos · Próxima ação
- Ações: WhatsApp · Rota · Ver no mapa · Novo retorno

## Arquivos

- `resources/views/operations/my-visits.blade.php`
- `public/js/visit-history.js`
- `tests/Feature/Visits/VisitHistoryTest.php`
- `docs/sprint-431/README.md`

## Prints

1. `historico-detalhes-aberto.png` — modal aberto
2. `historico-detalhes-gps.png` — fallback GPS no drawer
3. `historico-detalhes-acoes.png` — WhatsApp / Rota / mapa / novo retorno

## Testes

```
VisitHistory → 9 passed (48 assertions)
```

Relacionados (First Approach + Agenda + Visits) continuam verdes.
