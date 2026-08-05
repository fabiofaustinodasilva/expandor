# Roadmap — Pós-auditoria (Sprint 8.2.1+)

Somente após **aprovação** desta auditoria.

## Fase A — Fundação UX (Alta prioridade)

| Item | Complexidade | Impacto |
|------|--------------|---------|
| Unificar navegação (1 shell primário) | Alta | Alto |
| Glossário: Clientes ≠ Pontos ≠ Leads | Média | Alto |
| Fundir Equipe + Usuários | Média | Médio |
| Unificar Plano + Assinatura | Baixa | Médio |

## Fase B — Produto / permissões (Alta-Média)

| Item | Complexidade | Impacto |
|------|--------------|---------|
| Registrar Policies faltantes | Baixa | Médio |
| Ligar plan features + flags a menus/controllers | Média | Alto |
| Resolver `reports.*` (criar UI ou remover perms) | Média | Médio |
| Esconder AI/WhatsApp/estoque conforme plano | Baixa | Médio |

## Fase C — Ativação (Média)

| Item | Complexidade | Impacto |
|------|--------------|---------|
| Um único onboarding (deprecar setup legado com migrate de estado) | Alta | Alto |
| Tirar widgets de ativação do dashboard de Resultados | Média | Médio |

## Fase D — Performance / qualidade (Média-Baixa)

| Item | Complexidade | Impacto |
|------|--------------|---------|
| Lazy dashboard activation | Média | Médio |
| Audit N+1 mapa/CRM/comissões | Média | Médio |
| Testes de isolamento tenant | Média | Alto |
| Limpeza dead-code confirmada | Baixa | Baixo |

## Fase E — Mobile (contínua)

| Item | Complexidade | Impacto |
|------|--------------|---------|
| Priorizar sales-app + rail para sellers | Média | Alto |
| Revisar telas `layouts.app` em <900px | Média | Médio |
| Touch targets / scroll em formulários CRM | Baixa | Médio |

## O que NÃO fazer na primeira refatoração

- Reescrever o mapa do zero  
- Migrar para SPA (Vue/React) sem necessidade  
- Remover CRM sem validar clientes que usam  
- Alterar regras de comissão/billing  

## Critério de sucesso 8.2.1 (sugestão)

1. Um menu primário documentado por role.  
2. Zero labels ambíguos “Clientes”.  
3. Flags/planos refletem menus.  
4. Dashboard < X queries (baseline a medir).  
5. Nenhuma regressão nos fluxos mapa → visita → comissão.
