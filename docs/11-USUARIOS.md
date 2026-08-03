# GeoSales CRM

# Documento 11

# Usuários, Perfis e Permissões

Versão: 1.0

Status: Oficial

---

# 1. Objetivo

Este documento define o gerenciamento de usuários, perfis, cargos e permissões do GeoSales CRM.

O módulo será responsável por controlar:

- Quem acessa o sistema.
- Quais informações cada usuário pode visualizar.
- Quais ações cada usuário pode executar.
- A hierarquia da empresa.

---

# 2. Conceito de Usuário

Um usuário representa uma pessoa que utiliza o GeoSales CRM dentro de uma empresa.

Exemplos:

- Proprietário.
- Administrador.
- Gerente comercial.
- Supervisor.
- Vendedor.
- Instrutor.
- Analista.

---

# 3. Multiempresa

Todo usuário pertence obrigatoriamente a uma empresa.

Regra:

Um usuário de uma empresa nunca poderá visualizar dados de outra empresa.

---

# 4. Cadastro de Usuário

Campos:

Nome.

Email.

Telefone.

Foto.

Senha.

Cargo.

Perfil.

Equipe.

Status.

Data de cadastro.

Último acesso.

---

# 5. Status do Usuário

Usuário poderá possuir:

---

## Ativo

Pode acessar normalmente.

---

## Inativo

Não consegue acessar.

Histórico permanece.

---

## Bloqueado

Acesso suspenso por segurança.

---

# 6. Perfis de Acesso

O sistema possuirá perfis padrões.

---

# 6.1 Administrador

Maior nível de acesso.

Permissões:

- Configurar empresa.
- Gerenciar usuários.
- Gerenciar permissões.
- Visualizar todos dados.
- Configurar integrações.
- Acessar auditoria.

---

# 6.2 Gestor

Responsável pela operação comercial.

Permissões:

- Criar campanhas.
- Visualizar equipes.
- Acompanhar resultados.
- Ver mapas completos.
- Criar relatórios.

---

# 6.3 Supervisor

Responsável por equipes de campo.

Permissões:

- Acompanhar vendedores.
- Visualizar visitas.
- Gerenciar retornos.
- Acompanhar rotas.

---

# 6.4 Vendedor

Usuário operacional.

Permissões:

- Visualizar suas campanhas.
- Registrar visitas.
- Atualizar clientes.
- Consultar treinamentos.
- Acompanhar sua agenda.

---

# 6.5 Visualizador

Somente consulta.

Permissões:

- Visualizar dashboards autorizados.
- Consultar relatórios.

---

# 7. Hierarquia

Estrutura:

Empresa

↓

Administrador

↓

Gestores

↓

Supervisores

↓

Vendedores


---

# 8. Equipes

Usuários poderão pertencer a equipes.

Exemplo:

Equipe Fibra Centro.

Equipe Fibra Norte.


Uma equipe poderá possuir:

Supervisor.

Vendedores.

Meta.

Área de atuação.

---

# 9. Permissões

As permissões serão divididas por módulos.

Exemplo:

---

## Dashboard

dashboard.visualizar

dashboard.exportar

---

## Campanhas

campanha.criar

campanha.editar

campanha.visualizar

campanha.finalizar

---

## Imóveis

imovel.criar

imovel.editar

imovel.excluir

imovel.visualizar

---

## Visitas

visita.criar

visita.editar

visita.visualizar

---

## Academia

treinamento.criar

treinamento.editar

treinamento.visualizar

---

# 10. Controle de Acesso

O sistema deverá validar:

Usuário.

Empresa.

Perfil.

Permissão.

---

# 11. Visibilidade de Dados

Cada perfil possuirá limites de visualização.

---

Administrador:

Toda empresa.

---

Gestor:

Toda operação.

---

Supervisor:

Sua equipe.

---

Vendedor:

Somente seus registros.

---

# 12. Usuário Vendedor

O vendedor deverá possuir:

Meta.

Equipe.

Campanhas.

Área.

Ranking.

Histórico.


---

# 13. Supervisor

O supervisor deverá acompanhar:

Quantidade de visitas.

Vendas.

Conversão.

Produtividade.

Rotas.

---

# 14. Gerente

O gerente deverá acompanhar:

Todas equipes.

Campanhas.

Resultados.

Indicadores.

---

# 15. Login

O sistema deverá permitir:

Email e senha.

Recuperação de senha.

Lembrar sessão.

Logout.


---

# 16. Segurança de Login

Obrigatório:

Senha criptografada.

Proteção contra tentativas excessivas.

Sessão segura.

Registro de acesso.

---

# 17. Auditoria de Usuários

Registrar:

Login.

Logout.

Alteração de senha.

Alteração de permissões.

Bloqueios.

---

# 18. Convite de Usuários

Futuro:

Administrador poderá enviar convite.

Usuário recebe link.

Define senha.

Ativa conta.

---

# 19. Usuários Temporários

Preparar estrutura para:

Consultores.

Terceiros.

Representantes.

---

# 20. Troca de Empresa

Não será permitido.

Um usuário pertence a uma empresa.

Alteração somente por administrador.

---

# 21. Exclusão

Usuários nunca serão removidos definitivamente.

Utilizar:

Soft Delete.

---

# 22. Perfil Personalizado

Empresas poderão criar perfis próprios.

Exemplo:

Coordenador Regional.

Consultor.

Auditor.

---

# 23. Logs

Registrar:

Usuário.

Data.

Hora.

IP.

Ação realizada.


---

# 24. Futuro

Possíveis evoluções:

Login com Google.

Autenticação em dois fatores.

Biometria.

Aplicativo mobile.

Controle por localização.


---

# 25. Objetivo Final

Criar um sistema seguro e flexível onde cada empresa consiga organizar sua equipe comercial, controlar acessos e manter seus dados protegidos.
