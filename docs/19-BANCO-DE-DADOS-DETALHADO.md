# GeoSales CRM

# Documento 19

# Banco de Dados Detalhado

Versão: 1.0

Status: Oficial

---

# 1. Objetivo

Este documento define a estrutura detalhada do banco de dados do GeoSales CRM.

O banco deverá suportar:

- Modelo SaaS Multiempresa.
- Operações comerciais.
- Mapas.
- Campanhas.
- Usuários.
- Histórico.
- Relatórios.
- Inteligência futura.

---

# 2. Banco Oficial

Banco:

MariaDB


ORM:

Laravel Eloquent


Padrão:

Migrations Laravel.

---

# 3. Regra Multiempresa

Toda informação comercial obrigatoriamente pertence a uma empresa.

Regra:

Todas as tabelas comerciais devem possuir:

empresa_id


Exemplo:

clientes

empresa_id


campanhas

empresa_id


visitas

empresa_id


---

# 4. Tabela: empresas

Responsável:

Cadastro das empresas clientes do SaaS.

Campos:

id

nome

razao_social

cnpj

telefone

email

logo

plano_id

status

created_at

updated_at

deleted_at


Relacionamentos:

Empresa possui:

Usuários.

Clientes.

Campanhas.

Produtos.

Equipes.

---

# 5. Tabela: planos

Responsável:

Planos SaaS.

Campos:

id

nome

descricao

valor

limite_usuarios

limite_clientes

limite_campanhas

status

created_at

updated_at


---

# 6. Tabela: usuarios

Responsável:

Usuários do sistema.

Campos:

id

empresa_id

nome

email

telefone

senha

perfil_id

equipe_id

foto

status

ultimo_acesso

created_at

updated_at

deleted_at


---

# 7. Tabela: perfis

Responsável:

Controle de funções.

Campos:

id

empresa_id

nome

descricao

created_at

updated_at


Exemplo:

Administrador

Gestor

Supervisor

Vendedor

---

# 8. Tabela: permissoes

Responsável:

Permissões do sistema.

Campos:

id

nome

codigo

modulo

created_at

updated_at


Exemplo:

clientes.criar

campanhas.visualizar

relatorios.exportar


---

# 9. Tabela: perfil_permissoes

Relacionamento:

Perfis x Permissões


Campos:

id

perfil_id

permissao_id


---

# 10. Tabela: equipes

Responsável:

Organização dos vendedores.

Campos:

id

empresa_id

nome

supervisor_id

descricao

status

created_at

updated_at


---

# 11. Tabela: clientes

Responsável:

Pessoas abordadas comercialmente.

Campos:

id

empresa_id

nome

telefone

email

cpf

observacao

status

created_at

updated_at

deleted_at


Status:

Cliente.

Interessado.

Sem interesse.

---

# 12. Tabela: enderecos

Responsável:

Localização física.

Campos:

id

empresa_id

cliente_id

logradouro

numero

bairro

cidade

estado

cep

latitude

longitude

created_at

updated_at


---

# 13. Tabela: imoveis

Responsável:

Casa ou ponto visitado.

Campos:

id

empresa_id

endereco_id

status_comercial

cor_marcador

observacao

created_at

updated_at


Status:

verde

azul

laranja

vermelho

cinza

---

# 14. Tabela: historicos

Responsável:

Linha do tempo comercial.

Campos:

id

empresa_id

cliente_id

usuario_id

tipo

descricao

data

created_at


Exemplo:

Visita realizada.

Mudança de status.

Venda criada.

---

# 15. Tabela: campanhas

Campos:

id

empresa_id

nome

descricao

produto_id

cidade

data_inicio

data_fim

meta

status

created_at

updated_at


---

# 16. Tabela: campanha_usuarios

Relacionamento:

Campanhas x vendedores


Campos:

id

campanha_id

usuario_id

meta_individual


---

# 17. Tabela: visitas

Responsável:

Registro da abordagem.

Campos:

id

empresa_id

campanha_id

usuario_id

cliente_id

imovel_id

resultado

observacao

latitude

longitude

data_visita

created_at


Resultados:

Venda.

Interessado.

Retorno.

Sem interesse.

---

# 18. Tabela: retornos

Responsável:

Agenda comercial.

Campos:

id

empresa_id

cliente_id

usuario_id

data_retorno

observacao

status

created_at


Status:

Pendente.

Realizado.

Cancelado.

---

# 19. Tabela: produtos

Responsável:

Produtos vendidos.

Campos:

id

empresa_id

nome

descricao

valor

status

created_at

updated_at


---

# 20. Tabela: vendas

Responsável:

Contratos realizados.

Campos:

id

empresa_id

cliente_id

campanha_id

usuario_id

produto_id

valor

status

data_venda

created_at


---

# 21. Tabela: cursos

Academia.

Campos:

id

empresa_id

titulo

descricao

categoria

status

created_at

updated_at


---

# 22. Tabela: aulas

Campos:

id

curso_id

titulo

tipo

arquivo

conteudo

ordem

created_at


---

# 23. Tabela: progresso_treinamentos

Campos:

id

usuario_id

curso_id

percentual

concluido

data_conclusao


---

# 24. Tabela: mensagens

WhatsApp.

Campos:

id

empresa_id

cliente_id

usuario_id

tipo

mensagem

status

enviada_em


---

# 25. Tabela: templates_mensagem

Campos:

id

empresa_id

nome

conteudo

variaveis

status


---

# 26. Tabela: logs

Auditoria.

Campos:

id

empresa_id

usuario_id

acao

tabela

registro_id

descricao

ip

created_at


---

# 27. Índices Obrigatórios

Criar índices:

empresa_id

usuario_id

campanha_id

cliente_id

latitude

longitude

created_at


---

# 28. Campos Geográficos

Para mapas:

latitude

longitude


Preparar futuro:

PostGIS.

Dados geográficos.


---

# 29. Soft Delete

Utilizar:

deleted_at


Nas tabelas:

Clientes.

Usuários.

Empresas.

---

# 30. Histórico Permanente

Nunca apagar:

Visitas.

Vendas.

Treinamentos.

Logs.


---

# 31. Performance

Preparar para:

Milhões de clientes.

Milhões de visitas.

Grandes mapas.


Utilizar:

Índices.

Cache.

Paginação.

Filas.


---

# 32. Segurança

Todas consultas devem validar:

empresa_id.

Permissão.

Usuário.


---

# 33. Objetivo Final

Criar uma estrutura de banco preparada para transformar o GeoSales CRM em uma plataforma SaaS de grande escala, com inteligência territorial, histórico comercial e crescimento contínuo.
