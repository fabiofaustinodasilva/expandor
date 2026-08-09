# TEST-REPORT — Sprint 8.2.15

## Comando

```bash
.\.tools\php\php.exe vendor\bin\phpunit --filter "Sprint8215|Sprint8213|Sprint8212|ManagerNav|SalesAppModule|TeamHub|Product" --testdox
```

## Resultado

**75 tests, 494 assertions — 1 failure pré-existente**

### Sprint8215ProductsPrimaryNavTest — OK (6/6)

- admin rail Equipe → Produtos → Financeiro
- HTML/#op-nav-drawer mobile
- seller sem CRUD
- + Novo produto + catálogo seller + toggle
- Mais: 1× Comercial, 0× Empresa
- sem rotas CRUD paralelas

### Regressão OK

Sprint8213, Sprint8212, ManagerNav, SalesAppModule, Product (relacionados).

### Falha conhecida (não mascarada)

`TeamHubTest::test_manager_can_open_team_hub_and_cannot_open_technical_users`  
Espera “Equipe Comercial”; página atual usa título “Equipe”.  
Pré-existente (já conhecida nas sprints 8.2.11–8.2.13); fora do escopo desta sprint.
