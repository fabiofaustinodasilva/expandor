# Dashboard — Proposta Sprint 8.2.1

> Home diária = **Dashboard**. Análises profundas = **Relatórios**.  
> Sem alterar regras de cálculo nesta fase — só o que fica na home vs o que sai.

## Princípio

Se não responde “**preciso ver isso todo dia para operar?**” → Relatórios (ou Config/Ativação).

---

## Widgets oficiais (permanecem no Dashboard)

| Widget | Por quê diário? | Papel |
|--------|-----------------|-------|
| **Vendas hoje** (ou Instalações hoje, por segmento) | Meta do dia | Todos com `dashboard.view` |
| **Visitas hoje** | Ritmo de campo | Todos |
| **Conversão** (vendas/visitas do período curto) | Eficiência | Gestor + seller (própria) |
| **Retornos pendentes** | Agenda quente | Campo + gestor |
| **Instalações / vendas pendentes** | Fila pós-conversão | Gestor |
| **Ranking vendedores** (hoje / 7d) | Competição saudável | Gestor (oculto p/ seller ou só “meu lugar”) |
| **Receita do mês** | Saúde comercial | Admin/Gerente (`billing`/`commissions` conforme permissão) |

Filtros mínimos na home: **Hoje | 7 dias | 30 dias** (+ vendedor só para gestor).  
Cidade/setor: opcional colapsado (“Filtros”) — não bloquear first paint.

---

## O que sai da home → Relatórios

| Bloco atual | Destino | Motivo |
|-------------|---------|--------|
| Funil completo longo (pontos→… histórico amplo) | Relatórios → Funil | Análise, não operação diária |
| Produtividade detalhada por vendedor (tabelas longas) | Relatórios → Equipe | |
| Alertas de setor densos | Relatórios → Território / Alertas | Ou card compacto “N alertas” → drill-down |
| Comparativos longos / gráficos extras | Relatórios | |
| Exportações | Relatórios (`reports.export`) | |

---

## O que sai da home → Ativação / Onboarding

| Bloco | Destino |
|-------|---------|
| Setup progress | Banner só se incompleto **ou** rota `/onboarding` |
| SaaS activation card | Idem |
| Activation guidance / intelligence | Central de ativação; não misturar com KPIs |

**Regra:** usuário com workspace ready **não** vê cards de ativação no Dashboard.

---

## O que some ou vira atalho

| Item | Decisão |
|------|---------|
| Company name + plan no header | Manter no chrome do layout (não como widget) |
| Botões Mapa / Comissões | Atalhos no header — ok |
| Duplicata CRM dashboard KPIs | CRM tem seu hub; Dashboard não replica pipeline completo |

---

## Layout proposto (wireframe textual)

```
[ Dashboard — Visão do dia ]     [ Hoje | 7d | 30d ]  [ Abrir mapa ]

┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
│ Vendas   │ │ Visitas  │ │ Conversão│ │ Retornos │
│ hoje     │ │ hoje     │ │          │ │ pendentes│
└──────────┘ └──────────┘ └──────────┘ └──────────┘

┌──────────┐ ┌────────────────────────────────────┐
│ Instalações│ │ Ranking vendedores                │
│ pendentes  │ │                                    │
└──────────┘ └────────────────────────────────────┘

┌──────────────────────────────────────────────────┐
│ Receita do mês (admin/gerente)                   │
└──────────────────────────────────────────────────┘

(Banner ativação — condicional)
```

---

## Critérios por pergunta da sprint

| Widget | Importante diariamente? | Cliente usa? | Virar relatório? | Desaparecer? |
|--------|-------------------------|--------------|------------------|--------------|
| Vendas hoje | Sim | Sim | Não | Não |
| Visitas hoje | Sim | Sim | Não | Não |
| Conversão | Sim | Sim | Detalhe sim | Não |
| Retornos pendentes | Sim | Sim | Não | Não |
| Instalações pendentes | Sim | Sim | Lista completa sim | Não |
| Ranking | Sim (gestão) | Sim | Histórico sim | Não p/ gestor |
| Receita mês | Sim (gestão) | Sim | Detalhe sim | Não p/ admin |
| Funil completo atual | Não | Às vezes | **Sim** | Da home |
| Setup/activation sempre | Não | Early life | Não | Da home se ready |
| Alertas densos | Parcial | Médio | **Sim** | Compactar |

---

## Dependências técnicas (8.2.2 — sem mudar negócio)

- Reusar `DashboardMetricsService` / agregações existentes.  
- Lazy-load ativação.  
- Criar hub Relatórios mínimo que **hospede** o que saiu da home (mesmo sem gráficos novos sofisticados — views relocadas).  
- **Não** alterar API nesta sprint de implementação de UI.

---

## Métricas de sucesso

1. First contentful KPIs sem esperar onboarding services.  
2. ≤ 7 blocos visíveis no estado “workspace ready”.  
3. Zero labels “Clientes/Pontos” no dashboard.
