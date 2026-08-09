# NAVIGATION — Seller

## Navegação atual

### A) Rail operacional (`client-rail` / `#op-nav-drawer`)

Fonte: `ClientNav::sellerRail()`

1. Mapa  
2. Agenda  
3. Clientes  
4. Resultado  
5. Comissão  
6. Apresentar  

Fixos: Perfil · Mais · Sair  

Mobile: mesmo drawer `#op-nav-drawer`.

### B) Bottom nav Sales App (`layouts/sales-app`)

1. Início (`sales-app.dashboard`)  
2. Campanhas  
3. Retornos  
4. Apresentar (`sales-app.products.index` — lista, não necessariamente deck)  
5. Academia  

### C) Mapa (chrome campo)

Sempre: Busca · Apresentar produtos · Minha localização · basemap  
Não: Filtros/Métricas “Mais” do mapa (manager only)

### D) Mais (`operations.more` = `ClientNav::sections`)

Pode incluir (seed + flags): Painel, Mapa, App de campo, CRM, Campanhas, Agenda, Clientes, Pontos, Cidades, Setores, Comissões, Regras de comissão, Academia, WhatsApp, AI.

## Problema

**Dois “apps” no mesmo usuário:** rail operacional (mapa-first) vs Sales App (campanhas-first).  
Apresentar aponta para `present` no rail e para `products.index` no bottom Sales App — inconsistência leve.

## Navegação ideal (proposta — não implementar)

Rail seller mínimo:

| Direto | Em Mais | Remover da UX seller |
|--------|---------|----------------------|
| Mapa (Home) | Academia | CRM / Leads |
| Agenda (retornos) | Perfil | Regras de comissão |
| Apresentar | WhatsApp/AI se usados | Cidades/Setores no dia a dia |
| Comissão | — | Lista Pontos paralela |
| — | — | Sales App home se mapa for home |

Clientes: manter P1 ou fundir com busca do mapa.

## Comparação

| Atual | Ideal |
|-------|-------|
| 6 itens rail + Sales App 5 | 3–4 diretos + Mais |
| Mais com CRM/regras | Mais só suporte |
| Login → mapa (bom) | Manter |
| Duas agendas (Agenda + Retornos SA) | Uma Agenda |
