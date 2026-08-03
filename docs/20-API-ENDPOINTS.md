# GeoSales CRM

# Documento 20

# API REST e Endpoints

Versão: 1.0

Status: Oficial

---

# 1. Objetivo

Este documento define a arquitetura da API REST do GeoSales CRM.

A API será responsável pela comunicação entre:

- Frontend Web.
- Aplicativo Mobile.
- Integrações externas.
- Serviços internos.
- Inteligência Artificial.

---

# 2. Padrão da API

Tecnologia:

Laravel REST API


Formato:

JSON


Protocolo:

HTTPS


Autenticação:

Laravel Sanctum


---

# 3. Versão da API

Todas as rotas deverão utilizar versão.

Exemplo:

```
/api/v1/
```

---

# 4. Estrutura das Respostas

Resposta de sucesso:

```json
{
    "success": true,
    "message": "Operação realizada",
    "data": {}
}
```


---

Resposta de erro:

```json
{
    "success": false,
    "message": "Erro encontrado",
    "errors": {}
}
```

---

# 5. Autenticação

## Login

POST

```
/api/v1/auth/login
```


Enviar:

email

senha


Retorno:

token

usuário

permissões


---

## Logout

POST

```
/api/v1/auth/logout
```


---

## Usuário autenticado

GET

```
/api/v1/auth/me
```

---

# 6. Empresas

## Listar empresas

GET

```
/api/v1/empresas
```


---

## Criar empresa

POST

```
/api/v1/empresas
```


---

## Atualizar empresa

PUT

```
/api/v1/empresas/{id}
```


---

# 7. Usuários

## Listar usuários

GET

```
/api/v1/usuarios
```


---

## Criar usuário

POST

```
/api/v1/usuarios
```


---

## Atualizar usuário

PUT

```
/api/v1/usuarios/{id}
```


---

## Alterar status

PATCH

```
/api/v1/usuarios/{id}/status
```


---

# 8. Clientes

## Listar clientes

GET

```
/api/v1/clientes
```


Filtros:

nome

telefone

cidade

status


---

## Criar cliente

POST

```
/api/v1/clientes
```


---

## Visualizar cliente

GET

```
/api/v1/clientes/{id}
```


---

## Atualizar cliente

PUT

```
/api/v1/clientes/{id}
```


---

## Histórico cliente

GET

```
/api/v1/clientes/{id}/historico
```


---

# 9. Endereços e Geolocalização

## Criar endereço

POST

```
/api/v1/enderecos
```


---

## Buscar próximos clientes

GET

```
/api/v1/mapa/proximos
```


Parâmetros:

latitude

longitude

raio


---

# 10. Mapa

## Buscar marcadores

GET

```
/api/v1/mapa/marcadores
```


Filtros:

status

campanha

vendedor

região


---

## Heatmap

GET

```
/api/v1/mapa/heatmap
```


---

## Áreas comerciais

GET

```
/api/v1/mapa/setores
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


---

## Detalhes campanha

GET

```
/api/v1/campanhas/{id}
```


---

## Adicionar vendedor

POST

```
/api/v1/campanhas/{id}/usuarios
```


---

## Resultados campanha

GET

```
/api/v1/campanhas/{id}/resultado
```


---

# 12. Visitas

## Registrar visita

POST

```
/api/v1/visitas
```


Dados:

cliente

endereço

localização GPS

resultado

observação


---

## Listar visitas

GET

```
/api/v1/visitas
```


---

## Histórico de visitas

GET

```
/api/v1/clientes/{id}/visitas
```


---

# 13. Retornos

## Criar retorno

POST

```
/api/v1/retornos
```


---

## Listar retornos

GET

```
/api/v1/retornos
```


Filtros:

data

vendedor

status


---

## Finalizar retorno

PATCH

```
/api/v1/retornos/{id}/finalizar
```


---

# 14. Vendas

## Registrar venda

POST

```
/api/v1/vendas
```


---

## Listar vendas

GET

```
/api/v1/vendas
```


---

## Detalhes venda

GET

```
/api/v1/vendas/{id}
```


---

# 15. Produtos

## Listar produtos

GET

```
/api/v1/produtos
```


---

## Criar produto

POST

```
/api/v1/produtos
```


---

# 16. Dashboard

## Resumo geral

GET

```
/api/v1/dashboard
```


Retorno:

visitas

vendas

conversão

metas


---

## Ranking vendedores

GET

```
/api/v1/dashboard/ranking
```


---

## Indicadores territoriais

GET

```
/api/v1/dashboard/territorio
```


---

# 17. Relatórios

## Relatório comercial

GET

```
/api/v1/relatorios/comercial
```


---

## Relatório vendedor

GET

```
/api/v1/relatorios/vendedores
```


---

## Relatório campanha

GET

```
/api/v1/relatorios/campanhas
```


---

## Exportar relatório

POST

```
/api/v1/relatorios/exportar
```


Formatos:

PDF

Excel

CSV


---

# 18. Academia

## Cursos

GET

```
/api/v1/cursos
```


---

## Criar curso

POST

```
/api/v1/cursos
```


---

## Aulas

GET

```
/api/v1/cursos/{id}/aulas
```


---

## Progresso

POST

```
/api/v1/treinamentos/progresso
```


---

# 19. WhatsApp

## Enviar mensagem

POST

```
/api/v1/whatsapp/enviar
```


---

## Templates

GET

```
/api/v1/whatsapp/templates
```


---

## Histórico

GET

```
/api/v1/whatsapp/historico
```


---

# 20. IA

Preparação futura.

---

## Assistente comercial

POST

```
/api/v1/ia/assistente
```


Entrada:

dúvida vendedor

contexto cliente


---

## Análise comercial

GET

```
/api/v1/ia/analise
```


---

# 21. Permissões

Todas APIs deverão validar:

empresa_id.

Usuário.

Perfil.

Permissão.


---

# 22. Paginação

Listagens grandes deverão utilizar:

page

limit

order


Exemplo:

```
/clientes?page=1&limit=50
```

---

# 23. Auditoria

A API deverá registrar:

Usuário.

Data.

Ação.

IP.

Registro alterado.


---

# 24. Segurança

Obrigatório:

HTTPS.

Token seguro.

Rate Limit.

Validação.

Logs.


---

# 25. Documentação

A API deverá possuir documentação:

Swagger/OpenAPI.


---

# 26. Objetivo Final

Criar uma API robusta, segura e escalável, permitindo que o GeoSales CRM evolua para uma plataforma SaaS completa com aplicações web, mobile e integrações inteligentes.
