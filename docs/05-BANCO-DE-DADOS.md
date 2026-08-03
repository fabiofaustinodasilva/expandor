# GeoSales CRM

# Documento 05

# Banco de Dados

Versão: 1.0

Status: Oficial

---

# 1. Objetivo

Este documento define a estrutura oficial do banco de dados do GeoSales CRM.

O banco deverá suportar:

- Múltiplas empresas.
- Milhares de usuários.
- Milhões de imóveis.
- Milhões de visitas.
- Histórico comercial completo.
- Inteligência territorial.

---

# 2. Banco de Dados

Sistema:

MariaDB

ORM:

Laravel Eloquent


---

# 3. Padrões Gerais

Todas as tabelas deverão utilizar:

id

BIGINT UNSIGNED


created_at

TIMESTAMP


updated_at

TIMESTAMP


Quando aplicável:

deleted_at

TIMESTAMP NULL


---

# 4. Multiempresa

Todas as tabelas comerciais deverão possuir:

empresa_id


Objetivo:

Garantir isolamento dos dados entre empresas.


---

# 5. Tabela: empresas

Responsável pelo cadastro das empresas clientes do SaaS.

## Campos

id

nome

razao_social

cnpj

email

telefone

logo

endereco

cidade

estado

pais

status

plano_id

created_at

updated_at

deleted_at


---

# 6. Tabela: planos

Define planos comerciais do SaaS.

## Campos

id

nome

descricao

valor

limite_usuarios

limite_imoveis

limite_visitas

recursos

status

created_at

updated_at


---

# 7. Tabela: usuarios

Usuários do sistema.

## Campos

id

empresa_id

nome

email

telefone

senha

foto

perfil_id

status

ultimo_login

created_at

updated_at

deleted_at


---

# 8. Tabela: perfis

Define níveis de acesso.

## Campos

id

nome

descricao

created_at

updated_at


Exemplos:

Administrador

Gerente

Supervisor

Vendedor

Visualizador


---

# 9. Tabela: permissoes

Permissões disponíveis.

## Campos

id

nome

descricao

modulo

acao

created_at

updated_at


Exemplo:

clientes.visualizar

clientes.editar

campanhas.criar


---

# 10. Tabela: perfil_permissoes

Relacionamento entre perfil e permissões.

Campos:

id

perfil_id

permissao_id


---

# 11. Tabela: equipes

Agrupamento de vendedores.

## Campos

id

empresa_id

nome

supervisor_id

descricao

status

created_at

updated_at


---

# 12. Tabela: equipe_usuarios

Relaciona usuários às equipes.

Campos:

id

equipe_id

usuario_id


---

# 13. Tabela: cidades

Cadastro de áreas atendidas.

## Campos

id

empresa_id

nome

estado

pais

latitude

longitude

status


---

# 14. Tabela: setores

Divisão territorial.

Exemplo:

Bairro.

Região.

Área comercial.


Campos:

id

empresa_id

cidade_id

nome

tipo

geometria

status


---

# 15. Tabela: imoveis

ENTIDADE PRINCIPAL DO SISTEMA.

Representa o endereço físico.

## Campos

id

empresa_id

cidade_id

setor_id

cep

logradouro

numero

complemento

bairro

latitude

longitude

precisao_gps

referencia

observacoes

status

created_at

updated_at

deleted_at


---

# 16. Tabela: moradores

Representa pessoas relacionadas ao imóvel.

## Campos

id

empresa_id

imovel_id

nome

telefone

whatsapp

email

responsavel_decisao

observacoes

ativo

created_at

updated_at

deleted_at


---

# 17. Tabela: historico_imovel

Linha do tempo do imóvel.

## Campos

id

empresa_id

imovel_id

tipo

descricao

dados

usuario_id

created_at


Tipos:

visita

mudanca_morador

venda

retorno

observacao


---

# 18. Tabela: campanhas

Campanhas comerciais.

## Campos

id

empresa_id

nome

descricao

produto_id

cidade_id

data_inicio

data_fim

meta

status

created_at

updated_at


---

# 19. Tabela: campanha_equipes

Relacionamento campanha/equipe.

Campos:

id

campanha_id

equipe_id


---

# 20. Tabela: visitas

Registro de abordagem.

## Campos

id

empresa_id

campanha_id

imovel_id

morador_id

vendedor_id

latitude

longitude

precisao_gps

status_visita_id

observacoes

foto

data_visita

hora_visita

created_at

updated_at


---

# 21. Tabela: status_visitas

Status personalizáveis.

Campos:

id

empresa_id

nome

cor

ordem

ativo


Exemplo:

Verde

Instalação solicitada


Azul

Retornar


Laranja

Pensando


Vermelho

Sem interesse


---

# 22. Tabela: retornos

Agenda comercial.

Campos:

id

empresa_id

visita_id

imovel_id

responsavel_id

data_retorno

hora_retorno

status

observacao

created_at

updated_at


---

# 23. Tabela: produtos

Produtos vendidos.

Campos:

id

empresa_id

nome

descricao

valor

ativo

created_at

updated_at


---

# 24. Tabela: treinamentos

Academia Corporativa.

Campos:

id

empresa_id

titulo

descricao

categoria_id

tipo

arquivo

video_url

duracao

status

created_at

updated_at


---

# 25. Tabela: categorias_treinamento

Campos:

id

empresa_id

nome

descricao


---

# 26. Tabela: cursos

Agrupamento de treinamentos.

Campos:

id

empresa_id

nome

descricao

nivel

status


---

# 27. Tabela: progresso_treinamento

Controle dos usuários.

Campos:

id

usuario_id

treinamento_id

status

percentual

data_conclusao


---

# 28. Tabela: notificacoes

Comunicação interna.

Campos:

id

empresa_id

usuario_id

titulo

mensagem

lida

created_at


---

# 29. Tabela: auditorias

Registro de alterações.

Campos:

id

empresa_id

usuario_id

acao

tabela

registro_id

dados_antigos

dados_novos

ip

created_at


---

# 30. Tabela: configuracoes_empresa

Configurações individuais.

Campos:

id

empresa_id

chave

valor


---

# 31. Tabela: arquivos

Gerenciamento de arquivos.

Campos:

id

empresa_id

usuario_id

nome

caminho

tipo

tamanho

created_at


---

# 32. Índices Obrigatórios

Criar índices:

empresa_id

cidade_id

setor_id

latitude

longitude

data_visita

vendedor_id

campanha_id


---

# 33. Relacionamentos Principais


Empresa

possui muitos:

Usuários

Campanhas

Imóveis

Visitas

Treinamentos


---

Usuário

possui:

Perfil

Equipe

Visitas

Treinamentos


---

Imóvel

possui:

Moradores

Visitas

Histórico

Retornos


---

Campanha

possui:

Equipes

Visitas


---

# 34. Regras Importantes

Nenhum imóvel será duplicado.

Nenhuma visita será apagada.

Todo histórico será preservado.

Todo dado comercial pertence a uma empresa.

---

# 35. Preparação para Futuro

Banco preparado para:

Aplicativo Mobile.

Offline.

IA.

BI.

Integrações.

Geoprocessamento avançado.

---

# 36. Objetivo Final

Criar uma base de dados capaz de armazenar toda inteligência comercial territorial gerada pelas empresas utilizando o GeoSales CRM.
