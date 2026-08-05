# Sprint 8.2.0 — Auditoria Completa da Área do Cliente

> **Escopo:** Área do Cliente (tenant autenticado).  
> **Fora de escopo:** Platform Admin (`/platform/*`), Marketplace público (landing/checkout guest).  
> **Modo:** Somente levantamento. **Nenhuma** alteração de funcionalidade, banco, migration ou remoção de código.

## Relatório executivo

### Diagnóstico em uma frase

A Área do Cliente é um **monólito Blade com três shells de navegação** (sidebar clássica, rail operacional, app de campo), ~**200 rotas web autenticadas**, ~**110 views**, ~**55 controllers**, permissões ricas — e **sobreposição conceitual** entre Clientes / Pontos / CRM, Equipe / Usuários, e Comissões de venda / Comissões CRM.

### Números-chave

| Métrica | Valor (aprox.) |
|---------|----------------|
| Rotas web tenant (`auth` + tenancy) | ~200 endpoints |
| Controllers Web tenant | ~55 |
| Views Blade tenant | ~110–120 |
| Itens de menu (sidebar clássica) | ~25 |
| Itens rail operacional (admin) | ~12 |
| Itens rail vendedor | ~6 |
| Bottom nav Sales App | 4 |
| Roles tenant | 5 (+ Platform Admin fora) |
| Policies tenant | ~28 |
| Módulos funcionais auditados | 18 |
| Livewire / Vue / React | **0** (Blade + Vite mínimo) |

### Problemas principais (prioridade)

| # | Problema | Prioridade | Complexidade | Impacto |
|---|----------|------------|--------------|---------|
| 1 | Três navegações paralelas confundem o usuário | Alta | Alta | UX / onboarding / suporte |
| 2 | “Clientes” vs “Clientes/Pontos” vs CRM Leads | Alta | Média | Confusão comercial |
| 3 | Equipe operacional vs Usuários técnicos | Média | Baixa | Admin duplicado |
| 4 | Comissões (`/comissoes`) vs CRM commissions | Média | Média | Menus/rotas duplicadas |
| 5 | Permissão `reports.*` sem telas de Relatórios | Média | Média | Expectativa vs produto |
| 6 | Feature flags / plan features **não** ocultam menus | Média | Média | Billing vs UX |
| 7 | Policies não registradas (`CustomerPolicy`, `SalesAppPolicy`) | Média | Baixa | Segurança / consistência |
| 8 | Dashboard carrega muitos serviços (onboarding + métricas + ativação) | Média | Média | Performance |
| 9 | Follow-ups em 3 lugares (agenda, sales-app, minhas visitas) | Baixa | Média | Duplicação |
| 10 | Setup wizard + SaaS onboarding coexistentes | Média | Alta | Ativação |

### Melhorias sugeridas (para Sprint 8.2.1+ — **não implementar agora**)

1. **Unificar navegação** em um shell primário (rail operacional) e rebaixar sidebar clássica.  
2. **Glossário único:** Pontos (imóveis) ≠ Clientes (carteira) ≠ Leads (CRM).  
3. **Uma central de pessoas:** Equipe como hub; `/users` como avançado ou merge.  
4. **Relatórios:** criar módulo real ou remover permissões órfãs.  
5. **Ligar plan features / flags** à visibilidade de menus (AI, WhatsApp, estoque).  
6. **Registrar** todas as Policies no Gate.  
7. **Lazy-load** widgets de ativação/onboarding no dashboard.

### Documentos desta pasta

| Arquivo | Conteúdo |
|---------|----------|
| [architecture.md](./architecture.md) | Inventário técnico (controllers, services, layouts) |
| [navigation.md](./navigation.md) | Mapa de navegação, cliques, órfãs |
| [ux.md](./ux.md) | Classificação de telas + UX |
| [modules.md](./modules.md) | Auditoria por módulo |
| [permissions.md](./permissions.md) | Roles, policies, inconsistências |
| [performance.md](./performance.md) | N+1, widgets pesados, controllers |
| [security.md](./security.md) | TenantScope, guards, vazamentos |
| [dead-code.md](./dead-code.md) | Candidatos a código morto / órfãos |
| [roadmap.md](./roadmap.md) | Priorização para 8.2.1+ |

### Próximo passo

**Aprovar esta auditoria** antes de iniciar a Sprint **8.2.1** (refatoração da Área do Cliente).
