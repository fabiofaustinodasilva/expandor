# Sprint 8.2.1 — Refatoração Área do Cliente (Fase 1 · Planejamento)

> **Modo:** somente documentação e arquitetura de UX.  
> **Não** implementa features, **não** altera banco, API, integrações, permissões ou regras de negócio.  
> Base: [Sprint 8.2.0 — Auditoria](../sprint-820-client-area-audit/README.md).

## Entrega desta sprint

| # | Entregável | Doc |
|---|------------|-----|
| 1 | Nova arquitetura da Área do Cliente | Este README + navigation |
| 2 | Novo mapa de navegação | [navigation.md](./navigation.md) |
| 3 | Novo Dashboard proposto | [dashboard.md](./dashboard.md) |
| 4 | Menus reorganizados (+ por plano) | [navigation.md](./navigation.md), [feature-flags.md](./feature-flags.md) |
| 5 | Telas a fundir / sair do primário | Abaixo + navigation |
| 6 | Melhorias de UX | [ux.md](./ux.md) |
| 7 | Plano Sprint 8.2.2 | [roadmap.md](./roadmap.md) |
| — | Glossário oficial | [glossary.md](./glossary.md) |
| — | Segurança / Performance | [security.md](./security.md), [performance.md](./performance.md) |

---

## 1. Nova arquitetura (resumo)

```
┌─────────────────────────────────────────────────────┐
│  Shell único de gestão (rail evoluído)              │
│  Dashboard · Operação · Pessoas · Financeiro ·      │
│  Relatórios · Configurações                         │
└─────────────────────────────────────────────────────┘
          │
          ├── Sales App (/app) = campo mobile (mesmo glossário)
          └── APIs / permissões / domínio = inalterados
```

- Três shells atuais → **um oficial** + Sales App campo.  
- Vocabulário único: Ponto ≠ Cliente ≠ Lead.  
- Menus = permissão ∧ flag ∧ plano.

---

## 2. Glossário (essência)

| Termo | Significa |
|-------|-----------|
| **Ponto** | Unidade no mapa / cadastro geográfico (`Property`) |
| **Cliente** | Carteira comercial (`/clientes`) |
| **Lead** | Prospecto CRM |
| **Morador** | Pessoa no Ponto |
| **Visita / Retorno / Venda** | Campo e conversão |
| **Equipe** | Pessoas da empresa (substitui “Usuários” no menu) |
| **Supervisor** | Papel de liderança de campo |

Detalhes: [glossary.md](./glossary.md).

---

## 3. Navegação oficial

```
Dashboard
Operação → Mapa, Campanhas, Pontos, Visitas, Vendas
Pessoas  → Clientes, Equipe, CRM (se plano)
Financeiro → Comissões, Regras, Estoque (se plano)
Relatórios
Configurações
```

Detalhes + unificação Equipe/Usuários: [navigation.md](./navigation.md).

---

## 4. Dashboard proposto

Permanecem: vendas hoje, visitas hoje, conversão, retornos, instalações pendentes, ranking, receita do mês.  
Saem para Relatórios / Ativação: funil denso, alertas longos, setup cards permanentes.

Detalhes: [dashboard.md](./dashboard.md).

---

## 5. Telas a fundir ou remover do menu primário

| Item | Decisão |
|------|---------|
| Label “Clientes / Pontos” | → **Pontos** |
| Menu Usuários | → dentro de **Equipe** |
| Sidebar clássica como default | → deprecar |
| CRM commissions com label genérico | → **Regras de comissão** sob Financeiro |
| Widgets extras do Dashboard | → Relatórios |
| Cards ativação permanentes | → só se onboarding incompleto |
| Setup + SaaS duplicados | → unificar em 8.2.3 (não 8.2.2) |

**Nenhum módulo é removido do código nesta fase.**

---

## 6. Melhorias UX (top)

1. Glossário em todos os menus  
2. Um shell de navegação  
3. Dashboard enxuto  
4. Sem emoji em navegação  
5. Breadcrumbs em CRUD  
6. Touch targets mobile  
7. Banner único de ativação  

Lista completa: [ux.md](./ux.md).

---

## 7. Plano 8.2.2

Implementação mecânica do que foi aprovado aqui: labels, nav, dashboard, NavVisibility, Equipe hub, policies no Gate, perf P0.  
Ver [roadmap.md](./roadmap.md).

---

## Fora de escopo (reafirmado)

- Novas funcionalidades  
- Banco / migrations  
- APIs / Mercado Pago / webhooks  
- Mudança de matriz de permissões  
- Delete de módulos  

---

## Próximo passo

**Aprovar esta Fase 1** → iniciar Sprint **8.2.2** (implementação).
