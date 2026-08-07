# Navegação oficial — Área do Cliente

> Sprint 8.2.1 · **Proposta estrutural** (não implementada).  
> Base: auditoria 8.2.0. Glossário: [glossary.md](./glossary.md).

## Princípios

1. **Um shell primário** para gestão web (rail / sidebar unificada).  
2. **Sales App (`/app`)** permanece como shell de **campo mobile** — mesmos termos do glossário, menu reduzido.  
3. Sidebar clássica atual (`layouts.app` mega-menu) deixa de ser navegação oficial; vira legado a migrar.  
4. Itens só aparecem se: **permissão** ∧ **feature flag** ∧ **feature do plano** (ver [feature-flags.md](./feature-flags.md)).  
5. Sem novos módulos; só reorganização de entrada.

---

## Árvore oficial (Admin / Gerente / Supervisor)

```
Dashboard                         → /dashboard
Operação
  ├── Mapa                        → /map
  ├── Campanhas                   → /campaigns
  ├── Pontos                      → /properties          (ex-“Clientes/Pontos”)
  ├── Visitas
  │     ├── Agenda / Retornos     → /follow-ups
  │     └── Minhas visitas*       → /operacao/minhas-visitas
  └── Vendas                      → entrada sugerida: filtro Dashboard/Relatórios
                                   ou atalho para conversões do dia (sem nova API)
Pessoas
  ├── Clientes                    → /clientes
  ├── Equipe                      → /operacao/equipe     (absorve /users)
  └── CRM                         → /crm  [se plano.crm]
        ├── Leads
        ├── Oportunidades
        └── Metas
Financeiro
  ├── Comissões                   → /comissoes
  ├── Regras de comissão          → /crm/commissions     [se plano.crm + finance]
  └── Produtos / Estoque          → …/produtos           [se plano.stock]
Relatórios                        → /reports (UI a criar na 8.2.2+)  [reports.view]
Configurações
  ├── Empresa / Plano / Assinatura
  ├── Branding                    [se plano.white_label]
  ├── Integrações / API           [se plano.api / integrations.view]
  ├── WhatsApp                    [se flag + plano.whatsapp]
  ├── Assistente IA               [se flag + plano.ai]
  ├── Academia (gestão)
  ├── Território (Cidades, Setores, Endereços)
  ├── Auditoria / Privacidade
  └── Mais (overflow residual)
```

\*“Minhas visitas” no menu gestor pode ficar só para papel Vendedor.

---

## Árvore oficial (Vendedor)

```
Dashboard (meu dia)
Operação
  ├── Mapa / App Campo
  ├── Campanhas
  ├── Agenda (Retornos)
  └── Minhas visitas
Pessoas
  └── Clientes (carteira)
Financeiro
  └── Minha comissão
Academia                          → sales-app training / training.view
```

CRM / Config avançada / Equipe: ocultos sem permissão.

---

## Sales App (campo) — bottom nav oficial

| Slot | Label | Rota |
|------|-------|------|
| 1 | Início | `sales-app.dashboard` |
| 2 | Campanhas | `sales-app.campaigns.index` |
| 3 | Retornos | `sales-app.follow-ups.index` |
| 4 | Academia | `sales-app.training.index` |

Mesmo glossário; sem “Clientes/Pontos”.

---

## Mapeamento: hoje → amanhã

| Hoje (label / rota) | Destino oficial | Ação |
|---------------------|-----------------|------|
| Painel / Resultados | **Dashboard** | Renomear |
| Clientes / Pontos | **Pontos** | Renomear |
| Clientes (`/clientes`) | **Clientes** | Manter |
| CRM dashboard | Pessoas → CRM | Reparentar |
| Usuários (`/users`) | Equipe (aba/avançado) | Fundir UI |
| Equipe | Equipe | Hub único |
| Comissões + CRM commissions | Financeiro (2 itens claros) | Separar labels |
| Academia sidebar + sales-app | Config (gestão) + App (consumo) | Manter split |
| Setup + SaaS onboarding | Fluxo único Ativação (depois) | Não na 8.2.2 imediata |
| Sidebar clássica 25 itens | Deprecar como primária | Migrar para árvore |
| Rail operacional | Base do shell único | Evoluir |
| Relatórios (perms sem UI) | Novo hub Relatórios | Criar na implementação |

---

## Telas a fundir ou sair do menu primário

| Tela / entrada | Decisão | Não remover módulo? |
|----------------|---------|---------------------|
| `/users` | Fundir em Equipe | Sim — rotas podem permanecer temporariamente |
| Sidebar “Comercial” longa | Colapsar em Operação/Pessoas | Sim |
| CRM Commissions no mesmo label “Comissões” | Renomear “Regras de comissão” | Sim |
| Setup legado vs Onboarding | Unificar depois (8.2.3+) | Sim |
| Integrations vazia | Só se houver conteúdo / plano | Sim |
| Emoji nos links do Mais | Remover na UX | — |

**Nada é apagado do código nesta sprint.** “Remover do menu” ≠ delete.

---

## Unificação Equipe + Usuários (Fase 5)

### Problema
Dois CRUDs: `operations.team` (hub comercial + overrides) e `users.*` (CRUD técnico).

### Proposta

| Aba / seção em Equipe | Conteúdo |
|-----------------------|----------|
| Membros | Lista, ativar/inativar, reset senha, papel |
| Permissões | Overrides comerciais (já no Team) |
| Avançado | O que hoje só existe em `/users` (se restar gap) |

Papéis (Admin, Gerente, Supervisor, Vendedor, Viewer) continuam os mesmos — **sem alterar permissões**.

### Critério de sucesso
Um único item de menu **Equipe**; deep link `/users` redireciona ou mostra o mesmo hub.

---

## Cliques-alvo (pós-refatoração)

| Jornada | Cliques alvo |
|---------|--------------|
| Visita no mapa | ≤ 3 |
| Ver comissão | ≤ 2 |
| Convidar vendedor | ≤ 3 (via Equipe) |
| Abrir carteira de clientes | 1 |

---

## Fora de escopo desta proposta

- Novas rotas de negócio  
- Mudança de API / mobile contracts  
- Remoção física de controllers
