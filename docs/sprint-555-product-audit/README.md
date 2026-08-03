# Sprint 5.5.5 — Auditoria Geral (Pré-Lançamento)

## Objetivo

Revisar o Expandor como um cliente novo usaria a plataforma.  
**Sem funcionalidades novas.** Foco em qualidade, consistência e estabilidade.

## Escopo da auditoria

1. Fluxo ponta a ponta (Landing → Login → Cadastro → Setup → Operação → Owner)  
2. Visual / UX / responsividade  
3. Funcional (rotas, formulários, mensagens)  
4. Terminologia comercial  
5. Permissões por perfil  
6. Performance (eager load)  
7. Mobile  
8. Código morto / limpeza  
9. Segurança (policies, tenant, CSRF — revisão sem mudança de regras)  

## Documentos

| Arquivo | Conteúdo |
|---------|----------|
| [Correções realizadas.md](./Correções%20realizadas.md) | O que foi corrigido nesta sprint |
| [Lista de melhorias.md](./Lista%20de%20melhorias.md) | Melhorias aplicadas / sugeridas |
| [Pendências.md](./Pendências.md) | Itens conscientes para sprints futuras |

## Testes

```bash
.\.tools\php\php.exe artisan test
```

Regressão não é aceitável. Novos testes só se uma correção exigir.

## Princípio

Não alterar regras de negócio, banco ou fluxos críticos — apenas polimento e padronização.
