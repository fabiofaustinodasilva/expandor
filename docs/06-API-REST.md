# GeoSales CRM

# Documento 06

# API REST

Versão: 1.0

Status: Oficial

---

# 1. Objetivo

Este documento define o padrão oficial da API REST do GeoSales CRM.

A API será responsável pela comunicação entre:

- Frontend Web.
- Aplicativo Mobile.
- Integrações externas.
- Serviços internos.
- Inteligência Artificial.

---

# 2. Padrão da API

Arquitetura:

REST API


Formato:

JSON


Protocolo:

HTTPS


Autenticação:

Laravel Sanctum


Versão:

Todas as rotas deverão possuir versão.


Exemplo:

```
/api/v1/
```

---

# 3. Estrutura das Rotas

Padrão:

```
/api/v1/recurso
```


Exemplo:

```
GET /api/v1/imoveis
```

---

# 4. Autenticação

Método:

Bearer Token


Exemplo:

Header:

```
Authorization:
Bearer TOKEN
```


---

# 5. Resposta Padrão

Todas as respostas deverão seguir o mesmo formato.

## Sucesso

```json
{
    "success": true,
    "message": "Operação realizada",
    "data": {}
}
```


---

## Erro

```json
{
    "success": false,
    "message": "Erro encontrado",
    "errors": []
}
```

---

# 6. Multiempresa

Toda requisição autenticada deverá identificar:

empresa_id


Nenhum usuário poderá acessar dados de outra empresa.


---

# 7. Paginação

Todas as listagens deverão possuir paginação.


Exemplo:

```
?page=1
&per_page=20
```


Resposta:

```json
{
"data": [],
"meta":{
"current_page":1,
"total":100
}
}
```

---

# 8. Módulo Autenticação

## Login


POST

```
/api/v1/auth/login
```


Entrada:

```json
{
"email":"",
"password":""
}
```


Retorno:

Usuário

Token

Empresa

Permissões


---

## Logout


POST

```
/api/v1/auth/logout
```


---

## Usuário Atual


GET

```
/api/v1/auth/me
```


---

# 9. Empresas


GET

```
/api/v1/empresas
```


POST

```
/api/v1/empresas
```


PUT

```
/api/v1/empresas/{id}
```


DELETE

```
/api/v1/empresas/{id}
```


---

# 10. Usuários


GET

```
/api/v1/usuarios
```


POST

```
/api/v1/usuarios
```


PUT

```
/api/v1/usuarios/{id}
```


DELETE

```
/api/v1/usuarios/{id}
```


---

# 11. Campanhas


## Listar campanhas

GET

```
/api/v1/campanhas
```


---

## Criar campanha

POST

```
/api/v1/campanhas
```


Dados:

```json
{
"nome":"",
"cidade_id":"",
"produto_id":"",
"inicio":"",
"fim":""
}
```


---

## Finalizar campanha

POST

```
/api/v1/campanhas/{id}/finalizar
```


---

# 12. Imóveis


## Listar imóveis


GET

```
/api/v1/imoveis
```


Filtros:


cidade

bairro

setor

status

latitude

longitude


---

## Criar imóvel


POST

```
/api/v1/imoveis
```


---

## Visualizar imóvel


GET

```
/api/v1/imoveis/{id}
```


Retornar:

Dados do imóvel.

Moradores.

Histórico.

Visitas.

Retornos.

Localização.


---

# 13. Moradores


GET

```
/api/v1/moradores
```


POST

```
/api/v1/moradores
```


PUT

```
/api/v1/moradores/{id}
```


---

# 14. Visitas


## Registrar visita


POST

```
/api/v1/visitas
```


Dados:

```json
{
"imovel_id":1,
"campanha_id":1,
"status_id":2,
"latitude":"",
"longitude":"",
"observacao":""
}
```


---

## Histórico de visitas


GET

```
/api/v1/visitas/imovel/{id}
```


---

# 15. Retornos


GET

```
/api/v1/retornos
```


POST

```
/api/v1/retornos
```


PUT

```
/api/v1/retornos/{id}
```


---

# 16. Produtos


GET

```
/api/v1/produtos
```


POST

```
/api/v1/produtos
```


---

# 17. Mapa


## Dados para mapa


GET

```
/api/v1/mapa
```


Retorno:


Imóveis.

Marcadores.

Status.

Coordenadas.


---

# 18. Heatmap


GET

```
/api/v1/mapa/heatmap
```


Retorno:

Pontos de intensidade comercial.


---

# 19. Dashboard


GET

```
/api/v1/dashboard
```


Retorno:


Vendas.

Conversão.

Visitas.

Ranking.

Campanhas.


---

# 20. Relatórios


GET

```
/api/v1/relatorios
```


Filtros:

Data.

Cidade.

Vendedor.

Campanha.


---

# 21. Academia Corporativa


## Treinamentos


GET

```
/api/v1/treinamentos
```


---

## Progresso


POST

```
/api/v1/treinamentos/{id}/progresso
```


---

# 22. Notificações


GET

```
/api/v1/notificacoes
```


PUT

```
/api/v1/notificacoes/{id}/lida
```


---

# 23. Upload de Arquivos


POST

```
/api/v1/uploads
```


Suporte:

Fotos.

Vídeos.

PDF.

Documentos.


---

# 24. Webhooks


Preparado para:

WhatsApp.

Pagamentos.

Integrações externas.


---

# 25. Segurança


Obrigatório:

HTTPS.

Tokens seguros.

Rate Limit.

Validação.

Permissões.

Logs.


---

# 26. Performance


Utilizar:

Cache.

Paginação.

Filtros.

Fila para processos pesados.


---

# 27. Versionamento


Nunca quebrar versões existentes.


Exemplo:

```
/api/v1/

/api/v2/
```


---

# 28. Documentação da API


Utilizar futuramente:

Swagger / OpenAPI


---

# 29. Regras de Desenvolvimento


Toda nova funcionalidade deverá:


1. Possuir rota API.

2. Possuir validação.

3. Possuir documentação.

4. Possuir controle de permissão.


---

# 30. Objetivo Final


Criar uma API robusta que permita ao GeoSales CRM evoluir de uma aplicação web para uma plataforma completa com aplicativos, integrações e inteligência artificial.