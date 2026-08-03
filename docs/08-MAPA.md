# GeoSales CRM

# Documento 08

# Sistema de Mapa e Inteligência Territorial

Versão: 1.0

Status: Oficial

---

# 1. Objetivo

Este documento define o funcionamento do módulo de mapa do GeoSales CRM.

O mapa é um dos principais componentes do sistema e será responsável por transformar dados comerciais em inteligência territorial.

---

# 2. Conceito Principal

O mapa representa todo o território comercial da empresa.

Cada ponto registrado representa uma oportunidade, uma abordagem ou um histórico comercial.

O objetivo não é apenas visualizar clientes.

O objetivo é entender o território.

---

# 3. Princípios

O mapa deverá:

- Mostrar oportunidades.
- Mostrar resultados.
- Mostrar áreas trabalhadas.
- Mostrar áreas não exploradas.
- Auxiliar decisões comerciais.
- Guiar vendedores.

---

# 4. Tecnologias

Tecnologia inicial:

Leaflet


Mapa base:

OpenStreetMap


Preparado para futuras integrações:

Google Maps API

Mapbox

Outros provedores.

---

# 5. Elementos do Mapa

O mapa deverá suportar:

Marcadores.

Camadas.

Filtros.

Heatmap.

Setores.

Rotas.

Polígonos.

Áreas comerciais.

---

# 6. Marcadores

Cada imóvel cadastrado possuirá um marcador.

O marcador representa o estado comercial atual daquele endereço.

---

# 7. Sistema de Cores

As cores dos marcadores serão configuráveis por empresa.

Padrão inicial:

---

## 🟢 Verde

Cliente contratado.

Significado:

Venda realizada.

Instalação solicitada.

---

## 🔵 Azul

Retorno agendado.

Significado:

Cliente demonstrou interesse.

Existe uma próxima ação.

---

## 🟠 Laranja

Pensando.

Significado:

Cliente possui interesse, porém não decidiu.

---

## 🔴 Vermelho

Sem interesse.

Significado:

Cliente recusou.

---

## ⚪ Cinza

Sem contato.

Significado:

Não encontrado.

Casa fechada.

Ausência.

---

## 🟣 Roxo

Cliente potencial.

Significado:

Alta possibilidade de conversão.

---

# 8. Cadastro de Coordenadas

Todo imóvel deverá possuir:

Latitude.

Longitude.

Precisão GPS.

Data de captura.


---

# 9. Histórico Geográfico

Cada ação deverá permanecer ligada ao local.

Exemplo:

Imóvel:

Rua Brasil, 100


Histórico:

01/08/2026

Primeira abordagem.

Status:

Pensando.


15/08/2026

Retorno.

Status:

Contratou.


---

# 10. Ficha do Imóvel no Mapa

Ao clicar no marcador deverá abrir:

Nome.

Telefone.

Endereço.

Status atual.

Última visita.

Responsável.

Campanha.

Histórico.

Botões de ação.


---

# 11. Ações Rápidas

Dentro do mapa:

Registrar visita.

Agendar retorno.

Ligar.

Enviar WhatsApp.

Abrir rota.

Editar cadastro.


---

# 12. Filtros

O usuário poderá filtrar o mapa por:

Empresa.

Cidade.

Setor.

Bairro.

Campanha.

Produto.

Vendedor.

Status.

Data.


---

# 13. Pesquisa

Pesquisar:

Nome.

Telefone.

Endereço.

Rua.

Número.

Bairro.


---

# 14. Heatmap Comercial

O sistema deverá possuir mapa de calor.

O objetivo é identificar regiões com maior potencial.


Exemplo:


Vermelho:

Alta concentração de oportunidades.


Amarelo:

Médio potencial.


Verde:

Baixa exploração.


---

# 15. Indicadores Territoriais

O sistema deverá calcular:

Quantidade de imóveis visitados.

Quantidade de vendas.

Conversão por região.

Conversão por vendedor.

Conversão por campanha.


---

# 16. Setores Comerciais

O território poderá ser dividido em áreas.

Exemplo:

Cidade:

Bom Jardim de Goiás


Setores:

Centro.

Setor Norte.

Setor Sul.

Zona Rural.


Cada setor poderá possuir:

Meta.

Equipe responsável.

Histórico.

---

# 17. Controle de Cobertura Comercial

O sistema deverá identificar:

Áreas trabalhadas.

Áreas parcialmente trabalhadas.

Áreas nunca visitadas.


---

# 18. Rotas

O vendedor poderá gerar rotas.

Exemplo:

Retornos do dia.


Sistema deverá mostrar:

Ordem dos endereços.

Distância.

Tempo estimado.


---

# 19. Mapa do Supervisor

Supervisor poderá visualizar:

Equipe em campo.

Visitas realizadas.

Produtividade.

Rotas.

Resultados.


---

# 20. Mapa do Gestor

Gestor poderá analisar:

Melhores regiões.

Piores regiões.

Campanhas.

Conversões.

Potencial de expansão.


---

# 21. Inteligência Territorial Futura

O sistema poderá utilizar IA para:

Sugerir regiões.

Encontrar padrões.

Prever conversão.

Identificar oportunidades.

---

# 22. Regras Técnicas

Toda informação exibida no mapa deverá respeitar:

empresa_id.

Permissões.

Filtros ativos.


---

# 23. Performance

O mapa deverá utilizar:

Clusterização de marcadores.

Carregamento por área.

Paginação geográfica.

Cache.

---

# 24. Cluster de Marcadores

Quando existirem muitos pontos próximos:

Agrupar automaticamente.

Exemplo:

500 imóveis próximos.

Mostrar:

"500 oportunidades"


Ao aproximar:

Exibir pontos individuais.

---

# 25. Camadas

O usuário poderá ativar ou desativar:

Imóveis.

Clientes.

Retornos.

Vendas.

Equipe.

Setores.

Heatmap.


---

# 26. Auditoria

Toda alteração de localização deverá ser registrada.

---

# 27. Segurança

Usuário somente visualizará áreas permitidas pelo seu perfil.

---

# 28. Objetivo Estratégico

O módulo de mapa deverá responder:

Onde vender?

Quando vender?

Quem deve vender?

Qual região possui maior oportunidade?


---

# 29. Visão Final

O mapa do GeoSales CRM deverá se tornar uma inteligência comercial territorial, onde cada endereço representa uma informação estratégica para crescimento da empresa.
