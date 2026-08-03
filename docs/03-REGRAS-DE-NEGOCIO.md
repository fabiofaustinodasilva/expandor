# GeoSales CRM

# Documento 03

# Regras de Negócio

Versão: 1.0

Status: Oficial

---

# Objetivo

Este documento define todas as regras de negócio do GeoSales CRM.

Toda implementação deverá obedecer rigorosamente este documento.

Nenhuma funcionalidade poderá contrariar estas regras.

As regras aqui descritas possuem prioridade sobre qualquer implementação técnica.

---

# ADR-001

## Arquitetura Multiempresa

O sistema será Multiempresa (Multi Tenant).

Todos os registros do banco deverão obrigatoriamente pertencer a uma empresa.

Toda tabela deverá possuir:

empresa_id

Exceto tabelas internas do Laravel.

---

# ADR-002

## O imóvel é a entidade principal do sistema.

O GeoSales CRM trabalha baseado em imóveis.

Não em clientes.

O imóvel nunca será excluído.

Mesmo que:

• o morador mude

• a venda não aconteça

• a campanha termine

O imóvel continuará existindo.

---

# ADR-003

## Moradores podem mudar.

Um imóvel poderá possuir vários moradores durante sua existência.

Exemplo

2026

João

↓

2028

Maria

↓

2030

Pedro

O histórico deverá permanecer.

---

# ADR-004

## Uma visita nunca será apagada.

Toda visita registrada faz parte da inteligência territorial.

Mesmo visitas incorretas.

Caso exista erro.

A visita será cancelada.

Nunca excluída.

---

# ADR-005

## Todo imóvel possui histórico.

O histórico deverá armazenar.

Visitas

Mudança de morador

Mudança de status

Contratações

Retornos

Observações

Fotos

Campanhas

---

# ADR-006

## Toda visita pertence a uma campanha.

Não poderá existir visita sem campanha.

---

# ADR-007

## Toda visita pertence a um vendedor.

O vendedor responsável sempre será registrado.

---

# ADR-008

## GPS obrigatório

Toda visita deverá registrar.

Latitude

Longitude

Precisão

Data

Hora

---

# ADR-009

## Fotos

Fotos nunca serão substituídas.

Sempre será criada nova versão.

---

# ADR-010

## Histórico imutável

Depois que um histórico for criado.

Nunca poderá ser alterado.

Somente novos registros poderão ser adicionados.

---

# ADR-011

## Imóveis nunca serão duplicados.

Antes de criar novo imóvel.

O sistema deverá procurar.

Mesmo endereço.

Mesma cidade.

Mesmo número.

Caso exista.

Abrir cadastro existente.

---

# ADR-012

## Campanhas

Uma campanha possui.

Cidade

Produto

Equipe

Data início

Data fim

Meta

Status

---

# ADR-013

## Campanhas encerradas

Campanhas encerradas.

Nunca poderão ser alteradas.

Apenas consultadas.

---

# ADR-014

## Status da visita

O sistema possuirá status padronizados.

Contratou

Retorno

Sem interesse

Casa fechada

Sem morador

Concorrente

Decidindo

Outros

Empresas poderão criar novos status.

Nunca remover os padrões.

---

# ADR-015

## Retornos

Todo retorno deverá possuir.

Data

Hora

Responsável

Status

---

# ADR-016

## Linha do tempo

Cada imóvel possuirá linha do tempo completa.

Exemplo.

Primeira visita.

↓

Retorno.

↓

Venda.

↓

Instalação.

↓

Upgrade.

---

# ADR-017

## Concorrentes

O sistema armazenará.

Nome.

Plano.

Valor.

Velocidade.

Observações.

---

# ADR-018

## Produtos

Cada campanha poderá vender vários produtos.

---

# ADR-019

## Academia

Treinamentos poderão ser vinculados.

Produto

Campanha

Equipe

Cargo

---

# ADR-020

## Usuários

Todo usuário pertence a uma empresa.

Todo usuário possui um perfil.

---

# ADR-021

## Permissões

Toda permissão será baseada em perfis.

Nunca diretamente em usuários.

---

# ADR-022

## Auditoria

Toda alteração deverá gerar auditoria.

---

# ADR-023

## Exclusões

Sempre utilizar Soft Delete.

Nunca apagar registros importantes.

---

# ADR-024

## Dashboard

Todos os indicadores deverão ser calculados em tempo real.

---

# ADR-025

## Performance

Nenhuma tela poderá carregar milhares de registros.

Utilizar paginação.

Filtros.

Lazy Loading.

---

# ADR-026

## API

Toda comunicação futura.

Aplicativo.

Integrações.

Dashboard.

Utilizarão API REST.

---

# ADR-027

## Offline

Toda estrutura deverá ser preparada para funcionamento offline.

Mesmo que ainda não implementado.

---

# ADR-028

## Inteligência Territorial

Toda informação importante deverá possuir localização geográfica.

---

# ADR-029

## Histórico Comercial

Nenhuma informação comercial poderá ser perdida.

---

# ADR-030

## Simplicidade

Toda funcionalidade deverá exigir o menor número possível de cliques.

---

# ADR-031

## Segurança

Toda entrada de dados deverá ser validada.

---

# ADR-032

## Escalabilidade

Toda implementação deverá considerar crescimento para milhares de empresas.

---

# ADR-033

## Padrão de Código

Nenhum Controller conterá regra de negócio.

Toda regra ficará em Services.

---

# ADR-034

## Banco de Dados

Nunca duplicar informações.

Priorizar normalização.

---

# ADR-035

## Integrações

Toda integração externa deverá utilizar camada de Services.

Nunca diretamente nos Controllers.

---

# ADR-036

## IA

A Inteligência Artificial nunca alterará dados automaticamente.

Ela apenas sugerirá ações.

A decisão final sempre será do usuário.

---

# ADR-037

## Backlog

Toda nova funcionalidade deverá ser registrada no Backlog antes de ser implementada.

---

# ADR-038

## Documentação

Nenhum módulo poderá ser implementado sem documentação correspondente.

---

# ADR-039

## Filosofia

O sistema existe para organizar o território.

Não apenas clientes.

---

# ADR-040

## Objetivo Final

Transformar visitas comerciais em inteligência territorial.