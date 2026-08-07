# Mobile, Tablet e Responsividade — auditoria

## Breakpoints observados

| Superfície | Comportamento | Sev |
|------------|---------------|-----|
| Rail operacional | Vira bottom bar ≤900px | — (bom) |
| `client-ui` | Espaçamento denso ≤640px (8.2.3) | — |
| Mapa | Sheets + rail = colisão vertical | P1 |
| CRM kanban | Scroll horizontal hostil a polegar | P1 |
| Campanhas / comissões | Ações de linha wrapam / some | P1 |
| Sales App | Melhor experiência mobile nativa do produto | — (positivo) |
| Equipe drawers | Largura excessiva em portrait | P2 |
| Agenda modal | Longo com checklist de venda | P1 |
| Dashboard header | Wrap excessivo de CTAs | P2 |
| Landscape phone | Mapa e kanban pouco testados visualmente | P1 |
| Tablet 768–1024 | Zona cinza: rail lateral vs bottom | P2 |

## Checklist por dispositivo

### Desktop (≥1280)
- Listagens 8.2.3 legíveis.
- Mapa ocupa bem; overlays densos para gestor.
- CRM kanban usável.

### Notebook (1280–1440, altura baixa)
- Dashboard CTAs competem com KPIs.
- Tabelas com muitas colunas de ação.

### Tablet portrait
- Bottom rail + conteúdo ok.
- Drawers equipe e mapa precisam full-height sheets.

### Mobile portrait
- Sales App e Mapa são os caminhos reais do vendedor.
- CRUD admin (campanhas create no shell app) sofrível.

### Landscape
- Bottom rail reduz área útil do mapa (**P1**).
- Preferir rail compacto ou auto-hide em landscape mapa.

## Oportunidades mobile

| Ideia | Sev origem | Estimativa |
|-------|------------|------------|
| Menu “⋯” em linhas de tabela | P1 | 1 d |
| Mapa: um bottom sheet de “Camadas” | P1 | 2 d |
| Auto-hide rail em `/map` landscape | P1 | 1 d |
| CRM: lista em mobile, kanban só ≥900px | P1 | 1–2 d |
| Agenda: steps no modal de venda | P1 | 1 d |
| Skip-link / focus no sales-app | P2 | 0,5 d |

## Contraste e toque

- `.client-btn` 44px — bom onde adotado.
- Mapa / team / sales-app locais — auditar alvos &lt; 44px (**P2**).
- Emoji-as-icon falha contraste e leitores (**P1** mapa/settings).
