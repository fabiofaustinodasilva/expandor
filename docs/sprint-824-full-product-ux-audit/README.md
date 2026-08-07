# Sprint 8.2.4 — Auditoria completa de UX, Consistência e Produtividade

## Escopo

Auditoria **somente leitura** da Área do Cliente do Expandor.
Nenhuma alteração em banco, migrations, models, controllers, services,
repositories, APIs, billing, pagamentos, autenticação, tenancy, policies
ou regras de negócio.

Contexto: pós 8.2.2 (navegação + componentes) e 8.2.3 (CRUD chrome /
a11y). Esta sprint mede o **estado real do produto** e prioriza o que
ainda impede a sensação de SaaS premium único.

## Método

Para cada módulo foram respondidas as 20 perguntas do brief (clareza,
excesso/ocultação, duplicação, botões/cards, unificação, fluxo, nav,
nomenclatura, campos mortos, ações escondidas, produtividade, drawer /
modal / wizard / quick actions / atalhos, mobile, cliques).

Também: consistência visual, tipografia, ícones, hierarquia, contraste,
responsividade, tempo/cliques por tarefa e fluxos por papel
(proprietário, administrador/gestor, vendedor).

Severidades:

| Código | Significado |
|--------|-------------|
| **P0** | Bloqueia entendimento ou trabalho diário |
| **P1** | Fricção alta / inconsistência estrutural |
| **P2** | Melhoria de produtividade ou padrão |
| **P3** | Estética / polish |

## Veredito executivo

A Área do Cliente **melhorou de verdade** em listagens, rail e a11y
básica — mas ainda se comporta como **três produtos**:

1. Shell operacional (`layouts.operational` + rail)
2. Shell clássico (`layouts.app` + sidebar)
3. Sales App (`layouts.sales-app` + bottom nav)

Os maiores buracos de expectativa:

1. **Relatórios** promete análise e entrega só atalhos.
2. **Configurações** existe, mas **não está no ClientNav / Mais**.
3. **Mapa** é o coração do campo e ainda é uma ilha Tailwind + emoji,
   fora do design system.
4. Create/edit de Campanhas, Pontos e Leads **trocam de shell** em
   relação ao índice.

## Contagem de achados (consolidado)

| Severidade | Qtd. aprox. | Temas |
|------------|-------------|--------|
| P0 | 5 | 3 shells; Relatórios fake; Settings órfão; Mapa emoji/glossário; CRM como 2º painel |
| P1 | 18 | Glossário (Resultado, oportunidade); dual comissões; Team CSS; filtros; empty states; Sales App paralelo |
| P2 | 22 | Empty → client.empty-state; Plano vs Assinatura; MoreController morto; modal chrome; Academia admin |
| P3 | 8 | Marketplace público; brand hardcoded no campo; polish tipográfico |

Detalhamento por arquivo abaixo.

## Mapa de navegação (estado atual)

### Rail — Admin / Gestor

Dashboard → Mapa → Campanhas → Clientes → Equipe → Financeiro  
(+ Perfil, Mais, Sair)

### Rail — Vendedor

Mapa → Agenda → Clientes → **Resultado** → Comissão

### Mais (`ClientNav::sections`)

| Seção | Itens |
|-------|--------|
| Início | Painel, Mapa, Relatórios, App de campo |
| Comercial | CRM, Campanhas, Agenda, Clientes, Pontos, Cidades, Setores, Comissões, Estoque, Regras de comissão |
| Empresa | Equipe, Branding, Integrações, Plano e uso, Minha assinatura |
| Sistema | Auditoria, Privacidade, WhatsApp, Assistente, Academia |

**Ausente no Mais:** Configurações (`operations.settings`), CRUD `/users`,
endereços legados.

### Sales App (`/app`)

Início · Campanhas · Retornos · Academia (+ banner “App de campo”).

## Índice dos documentos

| Arquivo | Conteúdo |
|---------|----------|
| [dashboard.md](./dashboard.md) | Painel, CTAs, KPIs, ativação |
| [maps.md](./maps.md) | Mapa, drawers, glossário de campo |
| [campaigns.md](./campaigns.md) | Campanhas + shell switch |
| [sales.md](./sales.md) | Pontos, visitas, agenda, Sales App, carteira |
| [crm.md](./crm.md) | Hub CRM, leads, kanban, metas, regras |
| [team.md](./team.md) | Equipe hub, drawers, dual `/users` |
| [reports.md](./reports.md) | Relatórios + Financeiro/Comissões |
| [settings.md](./settings.md) | Configurações, billing UI, branding |
| [mobile.md](./mobile.md) | Responsividade, rail, landscape |
| [performance.md](./performance.md) | Cliques, tempo, produtividade visual |
| [quickwins.md](./quickwins.md) | Ganhos rápidos só-UX |
| [roadmap.md](./roadmap.md) | Sequência sugerida pós-auditoria |

## Fluxos por papel (síntese)

### Proprietário / Admin

Espera: ver saúde do negócio → configurar equipe → acompanhar dinheiro.  
Realidade: Dashboard limpo (bom), Relatórios vazios (ruim), Settings
escondido (ruim), Plano e Assinatura separados (médio).

### Gestor / Supervisor

Espera: ranking, aprovar comissão, mapa filtrado, equipe.  
Realidade: caminho sólido no rail; CRM e Relatórios competem com
Dashboard; ações densas nas linhas de campanha/comissão.

### Vendedor

Espera: bater ponto no mapa → registrar visita → ver retornos/comissão.  
Realidade: Map + Agenda bons; **Resultado** ≠ “Dashboard”; Sales App é
terceiro caminho para as mesmas tarefas; glossário “oportunidade”
confunde com CRM.

## O que NÃO fazer a seguir

- Não misturar refactors de domínio com UX.
- Não “inventar” relatórios no backend nesta sequência sem decidir
  produto (ver `reports.md` / `roadmap.md`).
- Não reescrever o mapa inteiro de uma vez — priorizar glossário +
  ícones + chrome.

## Fontes

- `app/Support/ClientArea/ClientNav.php`, `NavVisibility.php`
- `resources/views/**` (operational, app, sales-app, maps, crm, …)
- `public/css/client-ui.css` + `components/client/*` (21)
- Docs 8.2.0–8.2.3 em `docs/sprint-82*`

**Esta sprint não implementa mudanças.** Apenas audita.
