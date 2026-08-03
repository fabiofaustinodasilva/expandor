# GeoSales CRM

# Documento 16

# Padrão de Código e Desenvolvimento

Versão: 1.0

Status: Oficial

---

# 1. Objetivo

Este documento define os padrões obrigatórios de desenvolvimento do GeoSales CRM.

O objetivo é garantir:

- Código organizado.
- Fácil manutenção.
- Escalabilidade.
- Segurança.
- Facilidade para equipes futuras trabalharem no projeto.

---

# 2. Stack Oficial

Backend:

Laravel PHP


Banco:

MariaDB


Frontend:

Blade + Livewire ou Vue.js


API:

Laravel REST API


Autenticação:

Laravel Sanctum


Cache:

Redis


Fila:

Laravel Queue


---

# 3. Arquitetura

O sistema deverá seguir:

Arquitetura MVC + Service Layer.


Estrutura:

Controller

↓

Service

↓

Repository

↓

Model

↓

Database


---

# 4. Organização de Pastas

Estrutura:

```
app/

├── Models

├── Http

│   ├── Controllers

│   ├── Requests

│   └── Resources


├── Services

├── Repositories

├── Policies

├── Actions

├── Enums

├── Jobs

├── Events

└── Notifications
```

---

# 5. Models

Regras:

Um Model representa uma entidade do banco.

Não colocar regras complexas dentro do Model.

---

Exemplo:

Correto:

Cliente.php

Responsável por:

Relacionamentos.

Scopes.

Atributos.


Errado:

Colocar:

Cálculos.

Processos comerciais.

Integrações.

---

# 6. Controllers

Controllers devem ser simples.

Responsabilidade:

Receber requisição.

Validar entrada.

Chamar Service.

Retornar resposta.


---

Não permitido:

Controller com centenas de linhas.

Regras de negócio dentro do Controller.

---

# 7. Services

Services concentram regras de negócio.


Exemplo:

ClienteService.php


Responsável:

Criar cliente.

Atualizar cliente.

Validar regras.

Executar processos.


---

# 8. Repositories

Responsáveis pelo acesso aos dados.

Exemplo:

ClienteRepository.php


Responsável:

Buscar clientes.

Filtros.

Consultas complexas.

---

# 9. Form Requests

Toda entrada deverá possuir validação.


Exemplo:

StoreClienteRequest.php


Responsável:

Validar campos.

Mensagens de erro.

Regras.


---

# 10. API Resources

Nunca retornar Models diretamente pela API.


Utilizar:

Resources.


Exemplo:

ClienteResource.php


Objetivo:

Controlar formato das respostas.

---

# 11. Banco de Dados

Todas alterações deverão ser feitas através de:

Migrations.


Nunca alterar banco manualmente em produção.


---

# 12. Nomenclatura

Utilizar:

Inglês no código.

Português na interface.


Exemplo:

Código:

CustomerController


Tela:

Cliente


---

# 13. Models

Nome:

Singular.


Exemplo:

Cliente.php


Tabela:

Plural.


Exemplo:

clientes


---

# 14. Controllers

Nome:

Plural.


Exemplo:

ClientesController


---

# 15. Services

Nome:

Service.


Exemplo:

CampanhaService


---

# 16. Banco Multiempresa

Toda tabela comercial deverá possuir:

empresa_id


Toda consulta deverá respeitar:

empresa_id


---

# 17. Segurança

Obrigatório:

Policies.

Gates.

Middleware.

Validação.

Logs.


---

# 18. Controle de Permissões

Nunca verificar permissões diretamente no código.

Utilizar:

Policies.

Middleware.

Permission System.


---

# 19. Tratamento de Erros

Nunca mostrar erros técnicos ao usuário.


Utilizar:

Exceptions personalizadas.

Logs internos.

Mensagens amigáveis.

---

# 20. Logs

Registrar:

Erros.

Ações importantes.

Alterações críticas.


Utilizar:

Laravel Log.

Auditoria própria.

---

# 21. Código Limpo

Evitar:

Código duplicado.

Funções gigantes.

Variáveis sem sentido.

Comentários desnecessários.


---

# 22. Testes

Toda funcionalidade crítica deverá possuir testes.


Prioridade:

Login.

Permissões.

Multiempresa.

Vendas.

Campanhas.

Mapa.

---

# 23. Git

Padrão de commits:

feat:

Nova funcionalidade.


fix:

Correção.


refactor:

Melhoria interna.


docs:

Documentação.


Exemplo:

```
feat: criar cadastro de campanhas
```


---

# 24. Branches

Padrão:


main

Produção.


develop

Desenvolvimento.


feature/nome

Nova funcionalidade.


fix/nome

Correção.


---

# 25. Ambiente

Separar:

Desenvolvimento.

Homologação.

Produção.


---

# 26. Variáveis de Ambiente

Nunca colocar:

Senhas.

Tokens.

Chaves.

Dentro do código.


Utilizar:

.env


---

# 27. Uploads

Arquivos devem utilizar:

Storage Laravel.


Nunca salvar diretamente em public.


---

# 28. Processos Pesados

Utilizar filas para:

Relatórios.

Processamento de mapas.

Envio de mensagens.

IA.

Uploads grandes.


---

# 29. Frontend

Componentes deverão ser reutilizáveis.

Exemplo:

Button.

Modal.

Card.

Tabela.

Mapa.


---

# 30. Responsividade

Todo componente deverá funcionar em:

Desktop.

Tablet.

Mobile.


---

# 31. Código Gerado por IA

Toda sugestão da IA deverá respeitar:

Arquitetura.

Segurança.

Padrões existentes.

Documentação.


---

# 32. Regra para Cursor AI

Antes de criar código:

A IA deve:

1. Ler documentação.

2. Verificar estrutura existente.

3. Não criar arquivos duplicados.

4. Seguir padrões definidos.

5. Explicar alterações importantes.

---

# 33. Revisão

Antes de aceitar código:

Verificar:

Segurança.

Performance.

Organização.

Compatibilidade.


---

# 34. Objetivo Final

Criar um código profissional, escalável e preparado para transformar o GeoSales CRM em uma plataforma SaaS de grande porte.
