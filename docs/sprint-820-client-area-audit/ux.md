# UX — Auditoria de experiência

Legenda: 🟢 Essencial · 🟡 Melhorar · 🟠 Duplicada · 🔴 Sem sentido (no produto atual)

## Por tela / hub

| Tela | Class. | Objetivo | Uso | Complexidade | Melhorias |
|------|--------|----------|-----|--------------|-----------|
| Login | 🟢 | Autenticar | Alto | Baixa | — |
| Resultados `/dashboard` | 🟢 | KPIs comerciais | Alto | Alta | Separar ativação/onboarding; menos cards |
| Mapa `/map` | 🟢 | Operação de campo | Muito alto | Alta | Manter como home operacional |
| Campanhas | 🟢 | Planejar território | Alto | Média | — |
| Agenda follow-ups | 🟢 | Retornos | Alto | Baixa | Unificar com sales-app |
| Minhas visitas | 🟢 | Histórico seller | Alto | Baixa | — |
| Clientes `/clientes` | 🟢 | Carteira comercial | Alto | Média | Renomear vs Pontos |
| Pontos `/properties` | 🟡 | Cadastro imóveis | Médio | Média | Renomear para Pontos/Imóveis |
| CRM dashboard | 🟡 | Pipeline | Médio | Média | Clarificar vs Resultados |
| CRM Leads/Opp | 🟡 | Funil formal | Médio-Baixo* | Alta | *Depende do segmento ISP |
| Equipe | 🟢 | Gestão pessoas | Alto | Média | Absorver Usuários |
| Usuários `/users` | 🟠 | CRUD técnico | Baixo | Baixa | Merge com Equipe |
| Comissões `/comissoes` | 🟢 | Pagar/aprovar | Alto | Média | — |
| CRM Commissions | 🟠 | Regras CRM | Baixo | Média | Unificar sob Financeiro |
| Produtos/Estoque | 🟡 | Catálogo | Médio | Média | Só se plano tem stock |
| Config hub | 🟢 | Atalhos settings | Médio | Baixa | — |
| Branding | 🟡 | Identidade | Baixo | Baixa | — |
| Plano / Assinatura | 🟢 | Billing self-serve | Médio | Média | Unificar “Plano” + “Assinatura” |
| Setup wizard | 🟠 | Onboarding legado | Baixo pós-go-live | Alta | Unificar com SaaS onboarding |
| SaaS onboarding | 🟢 | Ativação trial | Alto em trial | Alta | — |
| Sales App `/app` | 🟢 | Campo mobile | Alto (seller) | Média | — |
| Academia (admin) | 🟡 | Conteúdo | Médio | Baixa | — |
| Academia (sales-app) | 🟢 | Consumo | Médio | Baixa | — |
| WhatsApp msgs | 🟡 | Comunicação | Médio* | Média | *Flag não ligada |
| AI Assistente | 🟡 | Assistência | Baixo-Médio* | Média | *Flag não ligada |
| Auditoria | 🟡 | Compliance | Baixo | Baixa | — |
| Privacidade | 🟡 | LGPD | Baixo | Baixa | — |
| Cities/Sectors/Addresses | 🟡 | Território | Médio | Baixa | Agrupar em “Território” |
| Relatórios | 🔴 | — | — | — | Permissão sem UI |
| Integrations page | 🟡 | Placeholder/config | Baixo | Baixa | Validar conteúdo real |

\*Frequência depende do plano/segmento; flags de produto existem mas **não** escondem menu.

## Dashboard — widgets

Fonte: `DashboardController` + `dashboard/index.blade.php` + `DashboardMetricsService`.

| Widget / bloco | Agrega valor? | Usado? | Remover? | Virar relatório? | Card secundário? | Repetido? |
|----------------|---------------|--------|----------|------------------|------------------|-----------|
| Filtros período/cidade/setor/vendedor | Sim | Sim | Não | Não | Não | Não |
| Setup progress card | Sim (trial) | Sim early | Após complete | Não | Sim | Sobreposição com activation-card |
| SaaS activation card | Sim (trial) | Sim early | Após ready | Não | Sim | Com setup |
| Activation guidance | Parcial | Baixo | Avaliar | Não | Sim | Com intelligence platform |
| Funil (pontos→visitas→interessados→contratos) | Sim | Sim | Não | Pode export | Não | Conceito perto do CRM |
| Produtividade por vendedor | Sim | Sim (gestor) | Não | Sim (relatório) | Não | — |
| Alertas de setor / seller | Sim | Médio | Não | Sim | Sim | — |
| Atalhos Mapa / Comissões | Sim | Sim | Não | Não | Já é | — |
| Company summary (plano/nome) | Baixo | Passivo | Não | Não | Header | — |

**Conclusão dashboard:** essencial, mas **sobrecarregado na ativação**. Separar “Resultados” de “Ativação” reduziria ruído.

## Achados UX transversais

1. **Vocabulário inconsistente** (Clientes em 2 sentidos).  
2. **Emoji em menus** (`MoreController`) vs rail sem emoji — tom visual misto.  
3. **Dois painéis** (Resultados vs CRM) sem hierarquia clara.  
4. **Mobile:** rail vira bottom bar ≤900px (bom); sidebar clássica colapsa de forma genérica (pior).  
5. **Sales-app** é o melhor caminho mobile para seller — mas admin ainda mistura shells.
