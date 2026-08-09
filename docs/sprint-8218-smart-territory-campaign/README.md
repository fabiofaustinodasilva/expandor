# README — Sprint 8.2.18

Território inteligente para campanhas: Estado/UF → Cidade → Setores (**Todos** ou múltiplos), sem APIs externas.

## Branch

`feature/sprint-8218-smart-territory-campaign` (base 8.2.17)

## Decisão Fase A

Schema atual **já** tem `campaign_sectors` e “Todos” = pivot vazio.  
**Zero migrations estruturais.** Implementação = UX + filtro territorial no mapa + testes/docs.

## Entrega

| Área | O quê |
|------|--------|
| Form campanha | Rádio Todos / Selecionar + checkboxes + busca + load por cidade |
| Mapa | `campaign_id` filtra por cidade ± setores do pivot |
| Tenancy | Endpoint e sync rejeitam setor/cidade de outro tenant |
| Docs | AUDIT, DATA-MODEL, TENANCY, PERFORMANCE, FUTURE-INTEGRATIONS, UX-CHECKLIST, TEST-REPORT, CHANGELOG |

## Semântica

- Pivot `campaign_sectors` **vazio** ⇒ cidade inteira (não cria setor “Todos”).
- Pivot com N IDs ⇒ somente esses setores.
