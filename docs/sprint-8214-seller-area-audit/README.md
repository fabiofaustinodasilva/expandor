# Sprint 8.2.14 — Auditoria completa da Área do Vendedor / Sales App

## Escopo

**Somente auditoria e documentação.**  
Sem alteração de código de produto, banco, migrations ou regras de negócio.

## Base auditada

`feature/sprint-8215-products-primary-nav` @ `3e484cb`  
(branch desta auditoria: `feature/sprint-8214-seller-area-full-audit`)

Não confundir com: `feature/sprint-8214-products-nav-audit` (auditoria de Produtos na empresa).

## Pergunta-guia

> Se eu contratar amanhã um vendedor porta a porta que nunca usou o Expandor, ele consegue trabalhar o dia inteiro só com o celular, quase sem treinamento?

## Princípios de avaliação (campo)

1. Mapa no centro  
2. Uma mão  
3. Poucos toques  
4. Pouca digitação  
5. GPS automático quando apropriado  
6. Informação contextual  
7. Sem administração no seller  
8. CTA principal evidente  
9. Voltar ao mapa após ações de campo  
10. Não duplicar funcionalidades

## Artefatos

| Arquivo | Conteúdo |
|---------|----------|
| [SELLER-INVENTORY.md](./SELLER-INVENTORY.md) | Inventário de funções |
| [ROUTES.md](./ROUTES.md) | Rotas acessíveis |
| [NAVIGATION.md](./NAVIGATION.md) | Rail / Sales App / Mais |
| [MAP-AUDIT.md](./MAP-AUDIT.md) | Mapa |
| [FIRST-APPROACH-AUDIT.md](./FIRST-APPROACH-AUDIT.md) | Primeira abordagem |
| [PRODUCT-AUDIT.md](./PRODUCT-AUDIT.md) | Apresentação |
| [CONTRACT-AUDIT.md](./CONTRACT-AUDIT.md) | Contratação |
| [VISITS-AUDIT.md](./VISITS-AUDIT.md) | Visitas |
| [RETURNS-AUDIT.md](./RETURNS-AUDIT.md) | Retornos |
| [AGENDA-AUDIT.md](./AGENDA-AUDIT.md) | Agenda / dia |
| [COMMISSIONS-AUDIT.md](./COMMISSIONS-AUDIT.md) | Comissões |
| [ACADEMY-AUDIT.md](./ACADEMY-AUDIT.md) | Academia |
| [RESPONSIVE-AUDIT.md](./RESPONSIVE-AUDIT.md) | Mobile |
| [PERFORMANCE-AUDIT.md](./PERFORMANCE-AUDIT.md) | Performance |
| [FLOW-METRICS.md](./FLOW-METRICS.md) | Toques / telas |
| [SELLER-DAY.md](./SELLER-DAY.md) | Dia ideal |
| [MISSING-FEATURES.md](./MISSING-FEATURES.md) | Lacunas |
| [DEPRECATION-CANDIDATES.md](./DEPRECATION-CANDIDATES.md) | Candidatos a limpeza |
| [ISSUES.md](./ISSUES.md) | Issues P0/P1 |
| [ROADMAP.md](./ROADMAP.md) | 8.2.16+ |
| [TEST-REPORT.md](./TEST-REPORT.md) | Suites |

Canvas IDE: `canvases/sprint-8214-seller-area-audit.canvas.tsx`

## Veredito executivo (1 parágrafo)

O Expandor **já permite** um dia de venda porta a porta centrado no **mapa** (GPS → toque → status → Apresentar → Detalhes → Contratar → FirstApproach → mapa), com comissão e agenda existentes. O maior risco operacional é **retorno sem data** (visita `return_later` sem FollowUp na Agenda) e a **navegação paralela** (rail operacional + Sales App bottom nav + Mais com CRM/regras/território), que aumenta carga cognitiva para um vendedor sem treinamento. Sprint **8.2.16** deve focar quick wins de campo (retornos obrigatórios/visíveis, home = mapa, limpar Mais seller), sem regredir mapa/produtos/contratação já validados.
