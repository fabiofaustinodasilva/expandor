# GeoSales CRM

# Documento 04

# Arquitetura do Sistema

Versão: 1.0

Status: Oficial

---

# 1. Objetivo

Este documento define a arquitetura técnica oficial do GeoSales CRM.

Todas as implementações futuras deverão seguir estas definições.

O objetivo é garantir:

- Organização.
- Escalabilidade.
- Segurança.
- Facilidade de manutenção.
- Evolução contínua.
- Compatibilidade com crescimento SaaS.

---

# 2. Visão Geral da Arquitetura

O GeoSales CRM será desenvolvido utilizando arquitetura Web baseada em camadas.

Modelo:

Usuário

↓

Frontend

↓

Controllers

↓

Services

↓

Models

↓

Banco de Dados


---

# 3. Stack Oficial

## Backend

Framework:

Laravel 12

Linguagem:

PHP 8.2+

---

## Banco de Dados

MariaDB

ORM:

Eloquent

---

## Frontend

Blade Templates

Bootstrap 5

JavaScript ES6+

Ajax / Fetch API

---

## Mapas

Leaflet

OpenStreetMap

---

## Gráficos

Chart.js

---

## Cache

Redis

---

## Filas

Laravel Queue

---

## Servidor

Linux

Nginx

PHP-FPM

Supervisor

---

# 4. Arquitetura Multiempresa

O sistema seguirá arquitetura Multi Tenant.

Modelo:

Banco único.

Todas as tabelas comerciais possuirão:

empresa_id


Exemplo:

clientes

id

empresa_id

nome

telefone


---

# 5. Estrutura de Diretórios Laravel

Estrutura oficial:

app/

├── Console/

├── Exceptions/

├── Http/

│   ├── Controllers/

│   ├── Middleware/

│   └── Requests/

│

├── Models/

├── Services/

├── Actions/

├── Policies/

├── Jobs/

├── Events/

├── Listeners/

├── Notifications/

└── Providers/


---

# 6. Controllers

Controllers terão responsabilidade limitada.

Um Controller deverá:

- Receber requisição.
- Validar entrada.
- Chamar Service.
- Retornar resposta.

Controllers NÃO devem possuir:

- Regras de negócio.
- Cálculos complexos.
- Consultas extensas.
- Integrações externas.

---

# 7. Services

Toda regra de negócio deverá ficar nos Services.

Exemplo:

VisitaService

Responsável por:

- Criar visita.
- Validar regras.
- Atualizar histórico.
- Gerar eventos.

---

# 8. Models

Models representam entidades do sistema.

Exemplos:

Empresa

Usuario

Imovel

Morador

Visita

Campanha

Produto

Treinamento

---

# 9. Form Requests

Toda validação deverá utilizar Form Requests.

Exemplo:

StoreVisitaRequest

Responsável por validar:

- Campos obrigatórios.
- Formatos.
- Permissões.

---

# 10. Policies

Controle de autorização.

Exemplo:

ImovelPolicy

Define:

Quem pode visualizar.

Quem pode editar.

Quem pode excluir.

---

# 11. Middleware

Responsável por controles globais.

Exemplos:

TenantMiddleware

Identificar empresa.

AuthMiddleware

Verificar login.

PermissionMiddleware

Validar permissões.

---

# 12. Repository Pattern

O projeto utilizará Repository quando necessário.

Uso:

Consultas complexas.

Relatórios.

Dashboards.

Grandes filtros.

---

# 13. Actions

Operações específicas poderão utilizar Actions.

Exemplo:

CriarVisitaAction

GerarRelatorioAction

ImportarImoveisAction

---

# 14. Events

Eventos serão utilizados para ações automáticas.

Exemplo:

Evento:

VisitaCriada


Ações:

Atualizar mapa.

Criar histórico.

Enviar notificação.

---

# 15. Jobs

Processamentos pesados deverão utilizar filas.

Exemplos:

Gerar relatórios.

Enviar mensagens.

Processar imagens.

Sincronizações.

---

# 16. Notifications

Sistema de notificações.

Exemplos:

Novo retorno.

Nova tarefa.

Treinamento pendente.

Meta atingida.

---

# 17. Banco de Dados

Todas as tabelas deverão possuir:

id

empresa_id

created_at

updated_at


Quando aplicável:

deleted_at


---

# 18. Soft Delete

Dados estratégicos nunca serão removidos fisicamente.

Utilizar:

SoftDeletes


---

# 19. Auditoria

Toda ação importante deverá gerar registro.

Exemplo:

Usuário alterou visita.

Data.

Hora.

IP.

Dados anteriores.

Dados novos.

---

# 20. API

Toda funcionalidade importante deverá estar preparada para API.

Formato:

REST JSON


Exemplo:

GET

/api/imoveis


POST

/api/visitas


---

# 21. Segurança

Obrigatório:

HTTPS.

CSRF Protection.

Validação.

Controle de acesso.

Criptografia.

Rate Limit.

Logs.

---

# 22. Performance

Obrigatório:

Paginação.

Cache.

Eager Loading.

Índices no banco.

Filas.

Consultas otimizadas.


---

# 23. Frontend

Padrão:

Blade Components.


Exemplo:

components/

├── button

├── modal

├── table

├── card

├── map


---

# 24. JavaScript

Organização:

resources/js/


Estrutura:

components/

maps/

dashboard/

campaigns/

---

# 25. Mapa

O mapa será um componente central.

Responsável por:

- Marcadores.
- Filtros.
- Camadas.
- Heatmap.
- Rotas.
- Geolocalização.

---

# 26. Uploads

Arquivos serão armazenados utilizando:

Laravel Storage.


Tipos:

Fotos.

Vídeos.

PDFs.

Documentos.

---

# 27. Logs

Utilizar:

Laravel Logging.


Registrar:

Erros.

Eventos importantes.

Falhas de integração.

---

# 28. Testes

Obrigatório criar testes para:

Regras críticas.

Permissões.

Serviços.

APIs.

---

# 29. Padrão de Código

Seguir:

PSR-12.

Nomes em inglês no código.

Comentários apenas quando necessário.

Código limpo.

---

# 30. Desenvolvimento com IA

Antes de gerar qualquer código:

A IA deverá:

1. Ler documentos da pasta docs.
2. Confirmar entendimento.
3. Respeitar arquitetura.
4. Não criar estruturas fora do padrão.
5. Explicar alterações realizadas.

---

# 31. Decisões Arquiteturais

ADR-001

Banco único Multi Tenant.


ADR-002

Imóvel é entidade principal.


ADR-003

Controllers sem regra de negócio.


ADR-004

Services centralizam regras.


ADR-005

API preparada desde o início.


ADR-006

Arquitetura preparada para aplicativo móvel.


---

# 32. Objetivo Final

Construir uma plataforma SaaS robusta, escalável e preparada para atender milhares de empresas utilizando uma arquitetura profissional.
