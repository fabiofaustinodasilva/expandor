# ROADMAP — pós auditoria 8.2.14

## Sprint 8.2.15 (implementação recomendada)

Objetivo: cumprir a regra **EQUIPE | PRODUTOS | FINANCEIRO** sem nova arquitetura de produtos.

1. **Rail admin** — adicionar Produtos entre Equipe e Financeiro (`ClientNav::adminRail`)
2. **Testes** — atualizar `ManagerNavCommissionsIntegrationTest` + novo assert Sprint 8215
3. **Copy** — remover linguagem “Configurações → Produtos” da listagem admin
4. **Higiene Mais** — eliminar duplicata Comercial/Empresa de Produtos
5. **Regressão** — Sprint8213, Sprint8212, SalesApp, commissions products CRUD

### Fora de escopo 8.2.15 (a menos que necessário)

- Nova migration / novo modelo de produto
- Mudança de ProductPolicy / tenancy
- Billing / Mercado Pago / cálculo de comissão
- Refatorar fluxo Contratar / apresentação 8.2.13

## Já entregue (não reabrir)

- CRUD único em `commissions.products.*`
- Atalhos Equipe/Financeiro (8.2.13)
- Configurações → Produtos (manter como caminho secundário, se desejado)
- Apresentação vendedor: Apresentar → Detalhes → Contratar
