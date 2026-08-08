# CHANGELOG — Sprint 8.2.12

## Apresentação

- Rota `sales-app.products.present` — deck full-screen, catálogo carregado uma vez (JSON)
- Swipe horizontal + botões Anterior/Próximo
- CTA **Apresentar produtos** no mapa e nav Sales App (**Apresentar**)
- **← Voltar ao mapa** → `map.index`

## Produtos (empresa)

- Título **Produtos** + CTA **+ Novo produto** (id `btn-new-product`)
- Empty state com CTA
- Ativar/Desativar (`commissions.products.toggle-status`)
- Link em Configurações → **Produtos**
- Nav Empresa + Comercial apontam para o mesmo CRUD existente

## Mapa

- Toolbar manager: Filtros/Métricas em menu **Mais**
- CTA Apresentar produtos + Minha localização sem sobrepor
- Cache bust `operational-map.js?v=48`

## Não alterado

Auth, tenancy, billing, MP, checkout, cálculo de comissão, status comerciais, schema de produtos (sem nova migration).
