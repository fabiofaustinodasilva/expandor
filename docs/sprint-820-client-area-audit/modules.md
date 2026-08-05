# Modules — Auditoria por módulo

Para cada módulo: finalidade, dependências, uso real, simplificar / dividir / unificar.

## Dashboard / Resultados
- **Finalidade:** KPIs de campo e gestão (funil, produtividade, alertas).
- **Dependências:** Analytics, Territory, Onboarding, ActivationIntelligence, Company.
- **Uso real:** Alto (home pós-login).
- **Simplificar?** Sim — tirar cards de ativação para fluxo próprio.
- **Dividir?** Resultados vs Ativação.
- **Unificar?** Não com CRM dashboard (papéis diferentes).

## Campanhas
- **Finalidade:** Planejar e ativar campanhas de porta-a-porta.
- **Dependências:** Visits, Maps, Properties, SalesApp.
- **Uso:** Alto.
- **Simplificar?** UI de status (activate/pause/finish) ok.
- **Unificar?** Com mapa (atalhos já existem).

## Mapa
- **Finalidade:** Operação geográfica (pontos, visitas, 1ª abordagem).
- **Dependências:** Properties, Visits, Campaigns, Analytics markers.
- **Uso:** Muito alto.
- **Simplificar?** Cuidado — core do produto.
- **Unificar?** É o hub operacional.

## Imóveis / Pontos (`properties`)
- **Finalidade:** Cadastro de pontos de venda/imóveis.
- **Dependências:** Addresses, Residents, Cities, Sectors, Maps.
- **Uso:** Médio-Alto.
- **Simplificar?** Renomear label do menu.
- **Unificar?** Não com “Clientes” carteira.

## Clientes (`/clientes`)
- **Finalidade:** Carteira comercial (CustomerQueryService).
- **Dependências:** Properties/Visits (visão agregada).
- **Uso:** Alto no rail.
- **Unificar?** Glossário vs Pontos — **obrigatório na 8.2.1**.

## Moradores (`residents`)
- **Finalidade:** Pessoas no ponto.
- **Dependências:** Properties, Visits, Communication.
- **Uso:** Médio (via propriedade).
- **Simplificar?** Manter nested sob property.

## Visitas / Agenda
- **Finalidade:** Registrar visitas e follow-ups.
- **Dependências:** Campaigns, Properties, Commissions, SalesApp.
- **Uso:** Muito alto.
- **Unificar?** Três UIs (web agenda, my-visits, sales-app) → um domínio, duas skins no máx.

## Vendas / Comissões / Produtos
- **Finalidade:** Comissão por visita/contrato + estoque.
- **Dependências:** Visits, Products, Stock.
- **Uso:** Alto (comissões); médio (estoque).
- **Unificar?** Com CRM commissions (regras) sob “Financeiro”.

## Equipe
- **Finalidade:** Hub comercial de pessoas + permissões finas.
- **Dependências:** Users, CommercialProfileCatalog, FieldOps.
- **Uso:** Alto.
- **Unificar?** Absorver `/users`.

## Relatórios
- **Finalidade:** (declarada em permissões) export/visão.
- **Dependências:** — **sem UI**.
- **Uso:** Nulo.
- **Simplificar?** Criar módulo **ou** remover permissões órfãs.

## Treinamentos
- **Finalidade:** Academia (admin + consumo campo).
- **Dependências:** SalesApp.
- **Uso:** Médio.
- **Dividir?** Já dividido admin/consumo — ok.

## IA
- **Finalidade:** Assistente conversacional.
- **Dependências:** AIService, ProcessAIChatJob; flag `ai.enabled` **não** na nav.
- **Uso:** Baixo-médio.
- **Simplificar?** Esconder se flag/plano off.

## Configurações
- **Finalidade:** Hub de settings (venda, campo, integrações, branding…).
- **Dependências:** Vários.
- **Uso:** Médio.
- **Simplificar?** Reduzir cards; agrupar.

## Branding
- **Finalidade:** Logo/tema tenant.
- **Dependências:** Brand, ThemeService.
- **Uso:** Baixo (setup).
- **Unificar?** Dentro de Configurações / Empresa.

## Pagamentos / Assinatura / Plano
- **Finalidade:** Self-serve billing da empresa.
- **Dependências:** Payments Subscription*, Billing usage.
- **Uso:** Médio (admin).
- **Unificar?** `company.plan` + `company.subscription` em uma tela.

## Marketplace
- **Finalidade na área do cliente:** apenas links de upgrade/trial — CMS é Platform.
- **Uso tenant:** Baixo (conversão).
- **Não** é módulo operacional do cliente.

## Onboarding
- **Finalidade:** Ativar empresa (setup legado + SaaS wizard).
- **Dependências:** Demo data, branding, team, deal.
- **Uso:** Alto no trial; baixo depois.
- **Unificar?** Um único funil de ativação.

## CRM (Leads / Opportunities / Goals)
- **Finalidade:** Funil clássico além do mapa.
- **Dependências:** Pipeline, Conversion metrics.
- **Uso:** Variável por cliente.
- **Simplificar?** Pode ser módulo opcional por plano (`crm` no PlanCatalog — hoje **não** esconde menu).
