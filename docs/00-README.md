# 🌎 GeoSales CRM

> Transformando visitas em inteligência territorial.

---

# 📖 Sobre o Projeto

O GeoSales CRM é uma plataforma SaaS de inteligência comercial desenvolvida para empresas que realizam vendas externas, principalmente equipes porta a porta.

O sistema foi projetado para transformar visitas comerciais em dados estratégicos através de geolocalização, mapas interativos, indicadores de desempenho e gestão inteligente de campanhas.

Diferente de um CRM tradicional, o GeoSales CRM não trabalha apenas com clientes, mas também com imóveis, visitas, campanhas e territórios, criando uma base histórica extremamente valiosa para análise comercial.

Inicialmente o sistema foi idealizado para provedores de internet, porém sua arquitetura foi planejada para atender qualquer empresa que trabalhe com equipes externas.

---

# 🎯 Objetivo

Desenvolver uma plataforma moderna, rápida e escalável que permita:

- Gerenciar campanhas porta a porta.
- Organizar equipes comerciais.
- Registrar visitas em mapa.
- Controlar retornos.
- Gerar inteligência territorial.
- Acompanhar indicadores em tempo real.
- Capacitar vendedores através da Academia Corporativa.

---

# 🏗 Arquitetura

O sistema será desenvolvido utilizando arquitetura SaaS Multiempresa (Multi Tenant).

Cada empresa possuirá seus próprios:

- Usuários
- Campanhas
- Clientes
- Imóveis
- Visitas
- Relatórios
- Configurações
- Treinamentos

Todos os dados serão isolados através do campo **empresa_id**.

---

# 💻 Tecnologias

## Backend

- Laravel 12
- PHP 8.2+
- Eloquent ORM
- API REST

## Frontend

- Blade
- Bootstrap 5
- JavaScript ES6
- Leaflet
- Chart.js

## Banco de Dados

- MariaDB

## Infraestrutura

- Linux
- Nginx
- Redis (cache e filas)
- Supervisor
- Git
- Composer

---

# 🎯 Público-Alvo

- Provedores de Internet
- Energia Solar
- Segurança Eletrônica
- Telefonia
- TV por Assinatura
- Consórcios
- Representantes Comerciais
- Planos de Saúde
- Empresas de vendas externas

---

# 🧩 Módulos do Sistema

## Dashboard

Indicadores em tempo real.

---

## Comercial

- Campanhas
- Imóveis
- Moradores
- Visitas
- Agenda
- Retornos

---

## Inteligência Territorial

- Mapa
- Heatmap
- Estatísticas
- Indicadores
- IA (futuro)

---

## Equipe

- Usuários
- Vendedores
- Supervisores
- Permissões

---

## Academia Corporativa

- Cursos
- Vídeos
- PDFs
- Documentos
- Quiz
- Certificados

---

## Comunicação

- WhatsApp
- Notificações
- Mensagens

---

## Administração

- Empresas
- Configurações
- Assinaturas
- Auditoria
- Logs

---

# 📁 Estrutura da Documentação

docs/

00-README.md

01-VISAO-GERAL.md

02-REQUISITOS-FUNCIONAIS.md

03-REGRAS-DE-NEGOCIO.md

04-ARQUITETURA.md

05-BANCO-DE-DADOS.md

06-API-REST.md

07-INTERFACE.md

08-MAPA.md

09-CAMPANHAS.md

10-ACADEMIA.md

11-USUARIOS.md

12-DASHBOARD.md

13-RELATORIOS.md

14-WHATSAPP.md

15-ROADMAP.md

16-PADRAO-DE-CODIGO.md

17-PROMPT-CURSOR.md

18-BACKLOG.md

---

# 📜 Princípios do Projeto

Todos os módulos devem seguir os mesmos padrões.

Código limpo.

Arquitetura organizada.

Componentes reutilizáveis.

Baixo acoplamento.

Alta coesão.

Segurança.

Escalabilidade.

Performance.

Documentação obrigatória.

Testes sempre que possível.

---

# 🎨 Padrão Visual

Tema moderno.

Interface limpa.

Poucos cliques.

Responsiva.

Mapa como elemento principal.

Ícones Bootstrap.

Paleta profissional.

Modo escuro (padrão).

Modo claro (opcional).

---

# 🔐 Segurança

Autenticação Laravel.

Controle de permissões.

Proteção CSRF.

Validação de todos os formulários.

Logs de auditoria.

Soft Delete quando aplicável.

Criptografia de senhas.

---

# 🌎 Filosofia

Cada visita importa.

Cada imóvel possui um histórico.

Cada campanha gera inteligência.

O mapa é o centro da operação.

O sistema deve ser simples para o vendedor e poderoso para o gestor.

---

# 📈 Escalabilidade

O projeto deverá suportar:

- Milhares de empresas.
- Milhões de imóveis.
- Milhões de visitas.
- Milhares de usuários simultâneos.

A arquitetura deve ser preparada para crescimento contínuo.

---

# 🚀 Roadmap

Versão 1

- MVP Comercial

Versão 2

- Academia

Versão 3

- WhatsApp

Versão 4

- Inteligência Artificial

Versão 5

- Aplicativo Mobile

---

# 👨‍💻 Regras para Desenvolvedores

Antes de implementar qualquer funcionalidade:

1. Ler toda a documentação da pasta docs.
2. Nunca criar funcionalidades não documentadas.
3. Seguir o padrão arquitetural.
4. Reutilizar componentes existentes.
5. Documentar alterações relevantes.
6. Manter compatibilidade entre módulos.
7. Priorizar desempenho e legibilidade.

---

# 📌 Missão

Criar a melhor plataforma brasileira para gestão de equipes de vendas externas, utilizando geolocalização, inteligência territorial e tecnologia de ponta para aumentar a produtividade e a conversão comercial.

---

**Versão do Documento:** 1.0

**Status:** Em desenvolvimento

**Projeto:** GeoSales CRM

**Licença:** Proprietária