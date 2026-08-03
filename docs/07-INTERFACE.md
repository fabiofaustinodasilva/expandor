# GeoSales CRM

# Documento 07

# Interface do Sistema

Versão: 1.0

Status: Oficial

---

# 1. Objetivo

Este documento define os padrões visuais e de experiência do usuário (UX/UI) do GeoSales CRM.

A interface deverá ser:

- Simples.
- Rápida.
- Moderna.
- Profissional.
- Responsiva.
- Fácil de utilizar em campo.

---

# 2. Princípios de Interface

A interface deverá seguir os seguintes princípios:

## Simplicidade

O usuário deve conseguir realizar suas principais tarefas com poucos cliques.

---

## Velocidade

A informação mais importante deve estar sempre acessível.

---

## Mobile First

O vendedor utilizará principalmente o celular.

Toda tela deverá funcionar perfeitamente em dispositivos móveis.

---

## Mapa como centro

A localização é parte fundamental do sistema.

O mapa deverá ter grande importância visual.

---

## Baixa curva de aprendizado

Um novo vendedor deverá conseguir utilizar o sistema sem treinamento técnico.

---

# 3. Perfis de Usuário

A interface será adaptada conforme o perfil.

---

# Administrador

Visualiza:

- Todas as configurações.
- Empresas.
- Usuários.
- Planos.
- Auditoria.

---

# Gestor

Visualiza:

- Dashboard.
- Campanhas.
- Equipe.
- Relatórios.
- Mapa completo.

---

# Supervisor

Visualiza:

- Equipe.
- Rotas.
- Visitas.
- Retornos.

---

# Vendedor

Visualiza:

- Suas campanhas.
- Mapa.
- Visitas.
- Agenda.
- Academia.

---

# 4. Layout Principal

Estrutura:

```
------------------------------------------------

LOGO

Menu lateral

Dashboard

Campanhas

Mapa

Clientes

Agenda

Academia

Relatórios

Configurações


------------------------------------------------

Conteúdo principal


------------------------------------------------
```

---

# 5. Menu Lateral

O menu deverá possuir:

## Principal

Dashboard

Mapa


## Comercial

Campanhas

Imóveis

Visitas

Retornos


## Gestão

Equipe

Metas

Relatórios


## Conhecimento

Academia


## Sistema

Configurações

Usuários

Auditoria


---

# 6. Dashboard Principal

Tela inicial após login.

Objetivo:

Mostrar rapidamente a situação da operação.

---

## Cards principais

Exemplo:

```
Visitas Hoje

250
```

```
Contratos

38
```

```
Conversão

15%
```

```
Retornos Pendentes

42
```

---

# 7. Dashboard por Perfil

## Gestor

Mostrar:

- Todas campanhas.
- Todas equipes.
- Mapa resumido.
- Ranking.

---

## Vendedor

Mostrar:

- Minha meta.
- Minhas visitas.
- Meus retornos.
- Minha agenda.

---

# 8. Tela de Mapa

O mapa será uma das telas principais.

---

## Elementos:

Marcadores.

Filtros.

Pesquisa.

Legenda.

Camadas.


---

# 9. Marcadores do Mapa

Cada status possuirá uma cor.


Exemplo:

🟢 Verde

Instalação solicitada


🟠 Laranja

Pensando


🔵 Azul

Retornar


🔴 Vermelho

Sem interesse


⚪ Cinza

Sem contato


---

# 10. Filtros do Mapa

Permitir filtrar:

Cidade.

Bairro.

Rua.

Campanha.

Vendedor.

Status.

Data.

Produto.


---

# 11. Card do Imóvel

Ao clicar no mapa:

Abrir ficha rápida.

Exemplo:

```
Rua Goiás, 120

João Silva

Telefone

Status:

Retorno

Última visita:

02/08/2026


[Ver histórico]

[Novo contato]

[Registrar visita]

```

---

# 12. Tela de Cadastro de Visita

Deverá ser extremamente rápida.


Fluxo:

Abrir imóvel.

↓

Registrar visita.

↓

Selecionar resultado.

↓

Adicionar observação.

↓

Salvar.


---

# 13. Botões Principais

Os botões mais utilizados deverão ter destaque.

Exemplo:

Botão:

"NOVA VISITA"

"AGENDAR RETORNO"

"ABRIR ROTA"


---

# 14. Tela do Vendedor

Pensada para uso na rua.


Mostrar:

Campanha atual.

Mapa.

Próximos retornos.

Meta.

Treinamentos.


---

# 15. Academia

Interface semelhante a plataformas de cursos.


Mostrar:

Cursos.

Vídeos.

Progresso.

Certificados.


---

# 16. Relatórios

Utilizar:

Cards.

Gráficos.

Tabelas.

Filtros.


---

# 17. Padrão Visual

Estilo:

Tecnologia.

Profissional.

Moderno.


---

# 18. Cores

Tema principal:

Escuro moderno.


Sugestão:

Fundo:

#0F1117


Elementos:

Cinza escuro.

Branco.

Vermelho ou azul para ações.


Status:

Verde.

Laranja.

Azul.

Vermelho.


---

# 19. Tipografia

Utilizar fonte moderna.

Sugestão:

Inter.

Roboto.


---

# 20. Componentes Reutilizáveis

Criar componentes:

Button.

Card.

Modal.

Tabela.

Filtro.

Mapa.

Badge.

Alert.

Dropdown.

Timeline.


---

# 21. Responsividade

Deverá funcionar em:

Desktop.

Notebook.

Tablet.

Celular.


---

# 22. Mobile

No celular:

Menu lateral vira menu inferior ou menu hambúrguer.


Principais ações:

Mapa.

Nova visita.

Agenda.


---

# 23. Offline

A interface deverá estar preparada para:

Salvar ações localmente.

Sincronizar depois.


---

# 24. Feedback ao Usuário

Toda ação deverá informar:

Sucesso.

Erro.

Carregamento.

Confirmação.


---

# 25. Acessibilidade

Considerar:

Contraste.

Tamanho de fonte.

Navegação simples.


---

# 26. Performance Visual

Evitar:

Animações pesadas.

Carregamentos longos.

Elementos desnecessários.


---

# 27. Experiência Ideal

O vendedor deve conseguir:

Chegar no endereço.

Abrir o sistema.

Ver histórico.

Fazer abordagem.

Registrar resultado.

Agendar retorno.

Tudo em menos de 1 minuto.

---

# 28. Objetivo Final

Criar uma interface que transforme o GeoSales CRM em uma ferramenta simples para o vendedor e poderosa para o gestor.
