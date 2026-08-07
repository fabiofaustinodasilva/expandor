# Sprint 8.2.8 — Fluxo de Campo Mobile (Sales / Mapa)

## Objetivo

Tornar o fluxo do vendedor na rua **simples, rápido e usável com uma mão**:

**abrir → Meu Local / toque no mapa → formulário → situação → salvar → voltar ao mapa.**

Escopo: Blade, CSS e JS do mapa operacional. Sem banco, migrations, APIs, tenancy, auth ou permissões.

## Pré-requisito

Sprint **8.2.7** (`docs/sprint-827-map-ux-simplification/`): Meu Local → GPS → formulário direto; clique no mapa sem “Casa sem cadastro”.

## O que esta sprint entrega

1. **Meu Local** mantido como ação principal de registro com GPS.
2. **Minha localização** — FAB que recentraliza no GPS **sem** abrir formulário / criar ponto.
3. Formulário mobile: campos essenciais + situação rápida (VisitStatus existentes).
4. Lat/lng ocultos para o vendedor (`field-seller-hide-meta` / texto amigável).
5. Pós-salvar: toast **Ponto registrado** → mapa (sem modal de ajuste para field seller).
6. Feedback GPS/salvar alinhado ao briefing.
7. Testes `Sprint828FieldSalesMobileFlowTest` + docs desta pasta.

## Fluxo alvo

```
Mapa → Meu Local → GPS → form
     ou toque no mapa → form
→ Situação (Interessado / Não / Retorno / Venda·Instalação)
→ Salvar → “Ponto registrado” → mapa continua
```

## Artefatos

- [AUDIT.md](./AUDIT.md)
- [UX-FLOW.md](./UX-FLOW.md)
- [MOBILE-CHECKLIST.md](./MOBILE-CHECKLIST.md)
- [CHANGELOG.md](./CHANGELOG.md)

## Testes

```
.\.tools\php\php.exe artisan test --filter=Sprint828
.\.tools\php\php.exe artisan test --filter="Sprint828|Sprint827|PilotSellerUx|FirstApproach|MapsModule|SalesApp"
```

Última execução (mapa / Sales / sprints): **31 passed** (221 assertions).

Regressão completa: **465 passed / 4 failed** (2742 assertions). Falhas fora do escopo desta sprint: `SalesCommissionModuleTest` (2), `TeamHubTest` (1), `VisitHistoryTest` (1) — não tocam mapa/`operational-map.js`.
