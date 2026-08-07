# Dashboard — auditoria UX

**Views:** `resources/views/dashboard/index.blade.php`  
**Rota:** `dashboard` → `/dashboard`  
**Shell:** `layouts.operational`

## Clareza imediata

O título “Dashboard” e os KPIs (visitas, vendas, conversão, retornos)
comunicam bem **para gestores**. Para o vendedor, o rail chama a mesma
tela de **“Resultado”** enquanto o H1 continua “Dashboard” — dissonância
de glossário (**P1**).

## Checklist das 20 perguntas

| # | Achado | Sev |
|---|--------|-----|
| 1 Clareza | Boa para admin; média para seller (label Resultado) | P1 |
| 2 Excesso | Header com período ×3 + Mapa + Comissão + Relatórios | P1 |
| 3 Escondido | Filtros colapsados (8.2.3) — positivo; avançados ok | — |
| 4 Duplicação | CTAs de comissão/relatórios também no Mais | P2 |
| 5 Botão desnecessário | Três botões de período poderiam ser um segmented control | P2 |
| 6 Card desnecessário | Card financeiro já removido (8.2.3); setup/ativação ainda pode poluir | P2 |
| 7 Unificar | Relatórios deveria absorver funil/gráficos que saíram daqui | P0 (via reports) |
| 8 Fluxo longo | Não — painel é leitura | — |
| 9 Nav confusa | Seller: Resultado vs Dashboard | P1 |
| 10 Nomenclatura | “Painel” no Mais vs “Dashboard” no rail admin | P2 |
| 11 Campo morto | N/A (leitura) | — |
| 12 Ação escondida | Nenhuma crítica | — |
| 13 Baixa produtividade | Overhead de CTAs no header | P1 |
| 14–16 Drawer/Modal/Wizard | Não aplicável ao painel diário | — |
| 17 Quick actions | Já existem; demais — reduzir a 1 primária + overflow | P2 |
| 18 Atalhos | Período via teclado ausente | P3 |
| 19 Mobile | Grid colapsa; header wrapa demais | P2 |
| 20 Menos cliques | Segmented period = −2 cliques vs 3 botões | P2 |

## Problemas

### P1 — Sobrecarga de CTAs no cabeçalho
Período (hoje / 7d / 30d) + atalhos competem visualmente com o título.
O dono da empresa precisa dos KPIs primeiro; ações secundárias deveriam
ficar em overflow ou na toolbar inferior.

### P1 — Label “Resultado” (seller) ≠ página
`ClientNav::sellerRail()` usa “Resultado”; a view não adapta o título.

### P2 — Onboarding / ativação permanente
Includes de ativação podem ocupar a primeira dobra mesmo após setup
parcial. Preferível um único banner dismissível.

### P2 — Ranking só para gestor
Correto em produto; garantir empty-state claro quando não há dados.

## Oportunidades

| Ideia | Tipo | Impacto | Estimativa |
|-------|------|---------|------------|
| Segmented control de período | Quick action / UI | Médio | 0,5 d |
| 1 CTA primária + menu “Mais ações” | Menos cliques | Médio | 0,5 d |
| Título dinâmico por papel (Resultado / Dashboard) | Glossário | Alto | 0,25 d |
| Banner único de ativação | Menos ruído | Médio | 1 d |

## Antes / depois desejado (conceitual)

| Antes | Depois |
|-------|--------|
| 6+ controles no header | KPIs + 1 período + 1 CTA |
| Seller vê “Dashboard” | Seller vê “Resultado do dia” |
| Clique em Relatórios → hub vazio | Relatórios com funil real (ver reports.md) |

## Dependências

Melhoria plena do Dashboard depende de **Relatórios honestos**
(`reports.md`) — senão o CTA “Ver relatórios” continua frustrante.
