# Feature flags & menus por plano

> Sprint 8.2.1 · Proposta de **visibilidade de menu**.  
> **Não altera** permissões seedadas, APIs ou billing nesta fase.  
> Implementação: 8.2.2 (helper de nav + Blade).

## Estado atual (auditoria 8.2.0)

| Mecanismo | Existe? | Esconde menu tenant? |
|-----------|---------|----------------------|
| `FeatureFlag` (platform) | Sim | **Não** |
| `PlanCatalog` features | Sim | **Não** |
| `hasPermission` / roles | Sim | **Sim** (único gate real) |
| `BillingService` limits | Sim | Parcial (ações, não menu) |

### Flags seedadas (`FeatureFlagSeeder`)

| Key | Default |
|-----|---------|
| `ai.enabled` | true |
| `whatsapp.enabled` | true |
| `mobile.enabled` | true |
| `branding.custom_css` | false |
| `billing.self_serve` | true |
| `onboarding.required` | true |

### Features de plano (`PlanCatalog`)

`crm` · `ai` · `whatsapp` · `stock` · `finance` · `api` · `white_label`

---

## Regra oficial de visibilidade

```
visível = permissão_do_usuário
        ∧ feature_flag_plataforma (se aplicável)
        ∧ feature_do_plano_da_empresa (se aplicável)
```

Nunca mostrar menu de módulo não contratado / desligado.

---

## Matriz menu × plano × flag

| Item de menu | Permissão (já existe) | Flag platform | Feature plano | Se OFF |
|--------------|----------------------|---------------|---------------|--------|
| Dashboard | `dashboard.view` | — | — | oculto |
| Mapa | `maps.view` | — | — | oculto |
| Campanhas | `campaigns.view` | — | — | oculto |
| Pontos | `properties.view` | — | — | oculto |
| Visitas / Agenda | `visits.view` | — | — | oculto |
| Clientes | `customers.view` | — | — | oculto |
| Equipe | `users.view` \| `users.manage` | — | — | oculto |
| CRM (Leads/Opp/Metas) | `crm.view` | — | **`crm`** | oculto |
| Comissões (campo) | `commissions.*` | — | **`finance`** sugerido* | oculto se finance off |
| Regras comissão CRM | `crm.manage` | — | `crm` ∧ `finance` | oculto |
| Produtos / Estoque | `commissions.manage` | — | **`stock`** | oculto |
| Relatórios | `reports.view` | — | — (core) | oculto se sem perm |
| Assistente IA | `ai.access` | **`ai.enabled`** | **`ai`** | oculto |
| WhatsApp | `communication.view` | **`whatsapp.enabled`** | **`whatsapp`** | oculto |
| Branding | `branding.manage` | `branding.custom_css` só p/ CSS avançado | **`white_label`** p/ menu | oculto se sem white_label** |
| Integrações / API | `integrations.view` | — | **`api`** | oculto |
| Plano / Assinatura | `billing.view` | `billing.self_serve` p/ upgrade | — | self-serve off = só leitura |
| Sales App / Mobile | `sales_app.access` | **`mobile.enabled`** | — | oculto |
| Marketplace (upgrade) | — | — | — | link marketing; não módulo ops |
| Academia | `training.*` | — | — | oculto sem perm |

\*Hoje comissões são core ISP; se `finance` estiver false em planos básicos, alinhar comercialmente na 8.2.2 — **não mudar seed agora**, só documentar intenção.  
\*\*Branding básico (logo) pode permanecer para admin mesmo sem white_label; CSS custom só com flag.

---

## Helper proposto (implementação futura)

```text
NavVisibility::can('ai') 
  => user->hasPermission('ai.access')
  && FeatureFlagService::isEnabled('ai.enabled', company)
  && company->planHas('ai')
```

Um único ponto usado por: rail, Mais, Config hub, sales-app links.

---

## O que NÃO fazer agora

- Alterar `RolePermissionSeeder`  
- Remover features do plano  
- Mudar API mobile  
- Hard-delete rotas (deep link continua 403 se policy/flag falhar — enforcement no controller na 8.2.2)

---

## Critérios de aceite (8.2.2)

1. Empresa sem `ai` no plano **não vê** Assistente no menu.  
2. Flag `whatsapp.enabled=false` esconde WhatsApp mesmo com permissão.  
3. Plano sem `crm` esconde bloco CRM.  
4. Documentado no README de release quais features cada plano envia.
