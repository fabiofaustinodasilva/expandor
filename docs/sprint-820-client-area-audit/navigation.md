# Navigation — Mapa de navegação

## Fluxo principal (após login)

```
Login (/login)
    ↓
Dashboard / Resultados (/dashboard)     ← redirect default
    ↓
┌───────────────────────────────────────────────────────────┐
│  Shell A: Rail operacional (layouts.operational)          │
│  Mapa → Equipe → Campanhas → Agenda → Clientes →         │
│  Resultados → Comissões → Config → Mais                   │
└───────────────────────────────────────────────────────────┘
    ↓ (se seller + sales_app)
┌───────────────────────────────────────────────────────────┐
│  Shell B: Sales App (/app) bottom nav                     │
│  Início → Campanhas → Retornos → Academia                 │
└───────────────────────────────────────────────────────────┘
    ↓ (CRUDs / admin clássico)
┌───────────────────────────────────────────────────────────┐
│  Shell C: Sidebar clássica (layouts.app)                  │
│  Painel, CRM, Properties, Cities, Users, AI, WhatsApp…    │
└───────────────────────────────────────────────────────────┘
```

## Contagem de telas (Área do Cliente)

| Tipo | Quantidade aprox. |
|------|-------------------|
| Telas index / hub | ~35 |
| Formulários create/edit | ~40 |
| Detalhe (show) | ~15 |
| Wizards onboarding/setup | ~10 |
| Sales-app screens | ~8 |
| **Total telas úteis** | **~100–110** |

## Cliques típicos (jornadas)

| Jornada | Cliques (estimado) | Caminho |
|---------|--------------------|---------|
| Vendedor: visita no mapa | 2–4 | Mapa → ponto → visita |
| Gestor: ver resultados filtrados | 1–3 | Resultados → filtros |
| Admin: criar usuário | 3–5 | Mais/Equipe **ou** Usuários → criar |
| CRM: lead → oportunidade | 4–6 | CRM → Leads → converter → Kanban |
| Campo (PWA): visita via campanha | 3–5 | App → Campanhas → imóvel → visita |

## Telas / rotas órfãs ou de baixo acesso

| Item | Evidência | Classificação |
|------|-----------|---------------|
| `reports.view` / `reports.export` | Permissão sem rotas `/reports` | Órfã de produto |
| `/users` (Usuários técnico) | Só admin + fora do rail; Equipe cobre o dia a dia | Acesso escondido / duplicado |
| Sidebar clássica completa | Muitos itens só via `layouts.app`; rail é o default do mapa | Menu paralelo |
| `/crm/commissions` | Paralelo a `/comissoes` | Possível duplicata |
| Setup (`/setup`) vs SaaS onboarding (`/onboarding`) | Dois funis de ativação | Sobreposição |
| Feature flags (`ai.enabled`, etc.) | Não escondem menus | Capacidade “escondida” no código |

## Páginas sem entrada de menu (acessíveis por URL / deep link)

- `visits.show`, várias rotas de edição CRM
- `operations.my-visits` (no rail seller, não no admin)
- `trial.conversion`, tour endpoints
- Detalhes de residents / property status
- API-only markers (`/api/v1/maps/markers`)

## Páginas conceitualmente duplicadas

| Conceito A | Conceito B | Nota |
|------------|------------|------|
| `customers.index` “Clientes” | `properties.index` “Clientes / Pontos” | Vocabulário conflita |
| `operations.team` | `users.index` | Gestão de pessoas em dois lugares |
| `follow-ups.index` | `sales-app.follow-ups` | Mesma agenda, shells diferentes |
| `commissions.index` | `crm.commissions.index` | Comissões de visita vs regras CRM |
| `dashboard` | `crm.dashboard` | Dois “painéis” |
| `training.categories` | `sales-app.training` | Admin vs consumo |

## Proposta de reorganização de menus (somente proposta)

### Primário (rail) — Admin
1. Mapa  
2. Resultados  
3. Campanhas  
4. Agenda  
5. Carteira (Clientes)  
6. Equipe  
7. Financeiro (Comissões)  
8. Configurações  
9. Mais  

### Primário — Vendedor
1. Mapa / App Campo  
2. Agenda  
3. Minhas visitas  
4. Carteira  
5. Meu resultado  
6. Minha comissão  

### Secundário (Mais / Config)
Território (cidades/setores/endereços/pontos), CRM pipeline, Academia admin, WhatsApp, AI, Branding, Plano, Auditoria, Privacidade.

### Remover do primário / unificar depois
- Sidebar clássica como default  
- Label “Clientes / Pontos” → **Pontos** ou **Imóveis**  
- “Usuários (técnico)” fundir em Equipe
