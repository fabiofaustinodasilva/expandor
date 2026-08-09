# Sprint 8.2.14 — Auditoria: Produtos na Área da Empresa

## Escopo

Somente **auditoria e documentação**.  
Preserva a implementação funcional da Sprint 8.2.13.  
Não altera código de navegação, CRUD, policies, tenancy ou schema.

## Decisão a preservar (8.2.12 / 8.2.13)

Na Área da Empresa deve existir acesso direto a **Produtos** na navegação principal da gestão:

**EQUIPE | PRODUTOS | FINANCEIRO**

Produtos **não** pode ficar escondido apenas em Configurações → Produtos.

Fluxo conceitual:

```
EMPRESA → Produtos → cadastrar/editar/ativar
        ↓
catálogo do vendedor
        ↓
VENDEDOR → Apresentar → Detalhes → Contratar
```

## Artefatos

- [AUDIT-PRODUCTS-NAV.md](./AUDIT-PRODUCTS-NAV.md) — checklist 1–10
- [ISSUES.md](./ISSUES.md) — gaps encontrados (sem fix nesta sprint)
- [ROADMAP.md](./ROADMAP.md) — backlog para Sprint 8.2.15
- [CHANGELOG.md](./CHANGELOG.md)

## Branch

`feature/sprint-8214-products-nav-audit`

Base: `feature/sprint-8213-sales-presentation-contract` @ `a78f779`

## Sem push automático / sem deploy
