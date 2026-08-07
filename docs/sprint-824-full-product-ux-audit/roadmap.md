# Roadmap sugerido pós-auditoria (implementação futura)

Esta sprint (**8.2.4**) **não implementa**. Sequência recomendada para
sprints seguintes, sempre preferindo UI/Support e evitando domínio.

## Princípios

1. Honestidade de produto antes de polish (Relatórios, Settings).  
2. Um glossário, um design system, no máximo dois shells.  
3. Campo (mapa) e gestão (operational) primeiro; CRM avançado depois.  
4. Não misturar Mercado Pago / tenancy / policies com UX.

## Fase A — Confiança e descoberta (≈ 1 sprint, 3–5 d)

| Item | Pri | Est. |
|------|-----|------|
| Settings no ClientNav | P0 | 0,25 d |
| Relatórios: copy honesta **ou** primeiros widgets reais | P0 | 0,5–3 d |
| Mapa: “Novo ponto” + Lucide nos CTAs principais | P0 | 1–2 d |
| Glossário seller Resultado/Dashboard | P1 | 0,25 d |
| Strip emoji settings/products/my-visits/customers show | P1 | 1 d |

**Impacto esperado:** usuário encontra setup; para de desconfiar de
“Relatórios”; campo fala a língua do glossário.

## Fase B — Shell único de gestão (≈ 1–2 sprints, 5–8 d)

| Item | Pri | Est. |
|------|-----|------|
| Create/edit campanhas, pontos, leads em `operational` | P1 | 2–3 d |
| Overflow menus em tabelas densas | P1 | 1–2 d |
| Empty states + alert session `x-client` | P2 | 1 d |
| Hub Financeiro (tabs comissões/estoque/regras) | P1 | 2–3 d |
| Filtros colapsáveis comissões | P1 | 0,25 d |

**Impacto:** sensação de um só app de gestão; menos reorientação.

## Fase C — Design system nos hubs restantes (≈ 2 sprints, 8–12 d)

| Item | Pri | Est. |
|------|-----|------|
| Team → `client-drawer` / `client-btn` (+ testes) | P1 | 3–5 d |
| CRM hub tabs + adopt client components | P1 | 3–4 d |
| Confirm/wizard Convert lead | P1 | 1–2 d |
| Billing UI unificada (tabs) | P2 | 2–3 d |
| Academia admin crud-toolbar | P2 | 1 d |

## Fase D — Campo unificado (≈ 2+ sprints)

| Item | Pri | Est. |
|------|-----|------|
| Outcome de visita compartilhado (mapa/agenda/app) | P1 | 3–5 d |
| Sales App como skin/PWA do mesmo fluxo | P1 | 5–8 d |
| Mapa: sheet único de camadas + a11y | P1 | 2–3 d |
| Landscape map rail auto-hide | P1 | 1 d |

## Fase E — Produtividade avançada

| Item | Pri | Est. | Nota |
|------|-----|------|------|
| Bulk approve comissões | P1 | 1–3 d | Pode precisar API |
| Relatórios com funil/export | P0 residual | 3–8 d | Pode precisar service |
| Kanban drag | P2 | 5+ d | |
| Wizard create campanha/ponto | P2 | 2–3 d | |
| Atalhos teclado listagens | P3 | 1–2 d | |

## Dependências entre fases

```
A (confiança) ──► B (shell) ──► C (hubs DS)
                      │
                      └──► D (campo unificado) ──► E (avançado)
```

Não iniciar D antes de A (glossário/mapa labels).  
Não iniciar bulk/API (E) em sprint “só UX”.

## Critérios de sucesso (produto)

- [ ] Configurações achável em ≤ 2 cliques a partir de qualquer tela operational  
- [ ] Relatórios não promete o que não entrega  
- [ ] Zero emoji como ícone de ação nas telas tenant logadas  
- [ ] Create de entidades principais sem troca de shell  
- [ ] Vendedor descreve Pontos/Clientes/Oportunidades sem misturar  
- [ ] Gestor aprova lote de comissões em &lt; 10 cliques  

## Relação com sprints anteriores

| Sprint | Herança |
|--------|---------|
| 8.2.0–8.2.1 | Diagnóstico / glossário |
| 8.2.2 | Nav + 15 componentes |
| 8.2.3 | CRUD chrome + a11y |
| **8.2.4** | **Auditoria profunda (este pacote)** |
| 8.2.5+ | Implementar Fase A/B… |
