# Glossário oficial — Área do Cliente Expandor

> Sprint 8.2.1 · Fonte de verdade para **labels de UI**.  
> Valores internos (enums, tabelas, APIs) **não mudam** nesta fase.  
> Implementação de texto: Sprint **8.2.2** via `CommercialTerminology` + menus.

## Regras

1. Um conceito = um nome em toda a Área do Cliente.  
2. Proibido usar “Cliente” para ponto/imóvel.  
3. Proibido “Usuários” no menu primário (usar **Equipe**).  
4. Segmento da empresa pode variar substantivos de **conversão** (já previsto em `CommercialTerminology`), mas os conceitos abaixo permanecem.

---

## Conceitos canônicos

| Termo oficial | O que é | Modelo / rota hoje | Nomes **proibidos** na UI |
|---------------|---------|---------------------|---------------------------|
| **Ponto** | Unidade geográfica de abordagem (casa, comércio, endereço no mapa) | `Property` · `/properties` · mapa | “Clientes / Pontos”, “Imóvel”* no menu primário, “Cliente” |
| **Lead** | Prospecto no funil CRM (ainda não é Cliente da carteira operacional) | `Lead` · `/crm/leads` | “Cliente” (antes da conversão) |
| **Cliente** | Relação comercial na **carteira** (visão agregada de pontos/situação) | `CustomerQueryService` · `/clientes` | “Ponto”, “Lead” |
| **Morador** | Pessoa vinculada a um **Ponto** | `Resident` · nested em property | “Cliente” (exceto se for o contratante explícito) |
| **Visita** | Atendimento/registro de campo em um Ponto | `Visit` · mapa / campanhas / agenda | “Atendimento” genérico no menu |
| **Retorno** | Follow-up agendado após visita | `FollowUp` · agenda | “Agenda” como sinônimo de Visita |
| **Venda** | Conversão comercial bem-sucedida (contrato / instalação solicitada, conforme segmento) | Visit/Property status `installation_requested` + Sales | Misturar com “Lead ganho” sem contexto |
| **Instalação** | Alias de conversão quando segmento Internet/Solar (`CommercialTerminology::conversionNoun`) | Mesmo domínio de Venda | Usar “Venda” e “Instalação” no **mesmo** card sem regra de segmento |
| **Campanha** | Planejamento territorial de abordagem | `Campaign` · `/campaigns` | “Operação” como sinônimo |
| **Equipe** | Pessoas da empresa (vendedores, supervisores, admins) + papéis/permissões | `User` · `/operacao/equipe` | “Usuários” no menu principal |
| **Supervisor** | Papel que acompanha equipe de campo e resultados | Role `supervisor` | “Gerente” como sinônimo (Gerente = outro role) |
| **Vendedor** | Papel de operação de campo | Role `seller` | “Usuário” |
| **Administrador** | Papel com gestão completa do tenant | Role `administrator` | — |
| **Gerente** | Papel quase-admin operacional | Role `manager` | Confundir com Supervisor |
| **Oportunidade** | Negócio em pipeline CRM | `Opportunity` · `/crm/opportunities` | “Venda” (até ganha) |
| **Comissão** | Valor a pagar ao vendedor por conversão de campo | `/comissoes` | Misturar com “regras CRM” sem rótulo |
| **Regra de comissão (CRM)** | Configuração de comissão no módulo CRM | `/crm/commissions` | Só “Comissões” no menu (ambíguo) |
| **Dashboard** | Painel diário de operação (home) | `/dashboard` | “Resultados” *ou* padronizar um dos dois — ver abaixo |
| **Relatório** | Análise histórica / exportação (não é home diária) | `reports.*` (hoje sem UI) | Widgets pesados no Dashboard |

\*“Imóvel” pode aparecer em formulários técnicos de endereço; no **menu** o termo oficial é **Ponto**.

---

## Decisão: Dashboard vs Resultados

| Opção | Decisão 8.2.1 |
|-------|----------------|
| Label do menu / título da home | **Dashboard** |
| Subtítulo / SEO interno | “Visão do dia” |
| Label antigo “Resultados” | Deprecar na UI; redirecionar mentalmente para Dashboard + Relatórios |

---

## Mapa mental (relação)

```
Campanha
   └── contém Pontos (no mapa)
         └── recebe Visitas
               ├── gera Retornos
               └── pode gerar Venda / Instalação
                     └── Cliente (carteira) atualiza situação
                     └── Comissão (financeiro)

Lead (CRM) ──converte──► Oportunidade ──ganha──► alinhado a Cliente/Venda
Morador ──vive em──► Ponto
Equipe (Vendedor / Supervisor / …) ──executa──► Visitas
```

---

## Alinhamento com código existente

| Já existe | Ação na 8.2.2 |
|-----------|----------------|
| `CommercialTerminology::customerNoun()` → “Cliente” | Manter |
| `conversionNoun()` → Venda/Instalação por segmento | Manter |
| Menu “Clientes / Pontos” | Renomear para **Pontos** |
| Menu “Clientes” (`/clientes`) | Manter **Clientes** |
| Menu “Usuários” | Remover do primário; conteúdo sob **Equipe** |
| `properties.*` permissions | Interno; label UI = Pontos |

---

## Checklist de conformidade (UI)

- [ ] Nenhum menu com “Clientes / Pontos”  
- [ ] Nenhum menu primário “Usuários”  
- [ ] Lead só sob CRM (se plano/flag permitir)  
- [ ] Comissão de campo vs regra CRM com labels distintos  
- [ ] Dashboard ≠ Relatórios  

**Aprovação deste glossário é pré-requisito para textos da 8.2.2.**
