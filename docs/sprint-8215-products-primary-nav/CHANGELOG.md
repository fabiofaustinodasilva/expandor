# CHANGELOG — Sprint 8.2.15

## Navegação

- `adminRail`: item **Produtos** (`stock` → `commissions.products.index`) entre Equipe e Financeiro
- Mesmo CRUD; sem rota/módulo paralelo
- Mais: removida duplicata Produtos da seção Empresa (permanece em Comercial)
- MoreController: removido link Produtos redundante do array legado
- **Hotfix:** `NavVisibility` módulo `stock` deixa de exigir `plan_feature=stock` (rail alinhado ao ProductPolicy / `commissions.manage`) — ver [HOTFIX-RAIL-VISIBILITY.md](./HOTFIX-RAIL-VISIBILITY.md)

## Copy

- Listagem: “Área da Empresa → Produtos — catálogo comercial…”
- Removido botão “← Configurações”

## Preservado

CRUD, ProductPolicy, tenancy, Sales App, apresentação, Contratar, Detalhes, GPS, mapa, comissão, billing.
