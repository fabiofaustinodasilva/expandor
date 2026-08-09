# CHANGELOG — Sprint 8.2.13

## Apresentação

- UI foto-first full-screen (menos texto/cards)
- Botão discreto **Contratar** + **Voltar ao mapa**
- Swipe + setas discretas; catálogo ainda em JSON único

## Contratação

- `Contratar` → `map.index?contract_product={id}`
- JS `openContractRegistration` abre o modal FirstApproach existente
- Pré-seleciona status `installation_requested` e produto no carrinho
- GPS via `getGps()` / `fillPointCoords` com estado "Obtendo localização..."
- Após salvar: toast de confirmação e permanece no mapa (fluxo existente)
- Sem formulário paralelo / sem novo GPS

## Empresa

- Equipe: aba **Produtos** → `commissions.products.index`
- Financeiro: botão **Produtos** (mesmo CRUD)

## Mapa

- Cache bust `operational-map.js?v=49`

## Não alterado

Auth, tenancy, billing, MP, cálculo de comissão, status comerciais, schema (sem migration).
