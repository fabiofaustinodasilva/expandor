# Sprint 5.5 — Análise de arquitetura

## 1. Contexto atual (o que já existe)

### 1.1 Multi-tenant
- `Company` com `status` (`active` / `suspended` / `cancelled`), `segment`, `whatsapp`, `is_system`
- Middleware `tenancy.initialize` + `tenancy.active`
- **Bloqueio operacional:** se `company.status !== active`, todas as rotas autenticadas do tenant falham (`EnsureTenantIsActive`)

### 1.2 Branding Expandor
- Antes do login → Platform Branding
- Depois do login → Tenant Branding (fallback Expandor se sem logo)
- Self-serve **não** deve exigir upload de logo no cadastro

### 1.3 Usuários / roles / permissões
- Roles globais seedadas (`administrator`, `manager`, `seller`, …)
- Admin de empresa nova: `Role::ADMINISTRATOR` + permissões completas do tenant
- Unique de e-mail hoje: **`(company_id, email)`** — não global
- Login busca e-mail **globalmente** (`first()`) → risco se o mesmo e-mail existir em 2 empresas

### 1.4 Assinaturas (já modeladas)
Tabela **`subscriptions`** (não `company_subscriptions`):

| Campo relevante | Uso |
|-----------------|-----|
| `company_id` | Tenant |
| `plan_id` | Plano (Free / Professional / Enterprise) |
| `status` | `active`, **`trial`**, `cancelled`, `past_due` |
| `trial_ends_at` | Fim do trial |
| `starts_at` / `ends_at` | Ciclo |
| `gateway`, `billing_cycle`, `next_billing_at` | Pagamentos futuros |

Jobs/serviços já existentes:
- `ExpireTrialsJob` (diário)
- `BillingAutomationService::expireTrials()` → suspende subscription + **company**

Config: `PAYMENT_TRIAL_DAYS` (default **0**) em `config/payments.php`.

### 1.5 Dois caminhos de criação de empresa (divergentes)

| Caminho | Origem | Trial | Brand | Settings | Admin |
|---------|--------|-------|-------|----------|-------|
| Owner | `PlatformCompanyService::createCompanyWithAdmin` | Não (`active`, `trial_ends_at=null`) | Não | Não | Senha do form |
| Checkout pago | `ProvisionCompanyAction` | Se `trial_days > 0` | Sim (default) | Sim | Senha gerada |

**Self-serve deve padronizar no padrão `ProvisionCompanyAction`**, enriquecido com segmento/WhatsApp e senha escolhida pelo usuário.

### 1.6 Onboarding
- Lazy no primeiro acesso (`OnboardingService`)
- Login redireciona admin para `/setup` se incompleto
- Existe geração de dados demo (cidades, produtos, visitas, comissões)

### 1.7 Login / rotas guest
- Guest: apenas `/login`
- Público marketing: `/plans`, `/checkout*` (sem signup trial)
- **Não existe** `/register` hoje

---

## 2. Fluxo desejado (produto)

```
Login
  └─ [Começar teste grátis]
        │
        ▼
   Formulário mínimo
   (empresa, segmento, responsável, conta)
        │
        ▼
   Provisionamento automático
   (company + subscription trial 2d + admin + defaults)
        │
        ▼
   Auto-login → Setup / Mapa
        │
        ▼
   Após 2 dias → trial expirado (bloqueio)
        │
        ▼
   Owner: visualizar / bloquear / converter
```

### Campos do cadastro

| Grupo | Campo | Obrigatório |
|-------|-------|-------------|
| Empresa | Nome | Sim |
| Empresa | Segmento | Sim |
| Empresa | CNPJ | Não |
| Responsável | Nome | Sim |
| Responsável | WhatsApp | Sim |
| Responsável | E-mail | Sim |
| Conta | Usuário (e-mail login) | Sim — **igual ao e-mail do responsável** (recomendado) |
| Conta | Senha | Sim |

**Não pedir:** logo, documentos extras, endereço completo, configs avançadas.

> Recomendação: unificar “E-mail do responsável” e “Usuário” em um único campo e-mail, evitando ambiguidade no login.

---

## 3. Respostas às 7 perguntas de arquitetura

### 1) Criar tabela nova ou reutilizar?

**Reutilizar `subscriptions`.**

Motivos:
- Já tem `status=trial` e `trial_ends_at`
- Job de expiração já consulta essa tabela
- Dashboard Owner já conta `trial_clients`
- Nova tabela `company_subscriptions` duplicaria domínio de billing e quebraria jobs/métricas

**Opcional (não obrigatório na 5.5):** coluna `source` (`owner` | `checkout` | `self_serve`) em `subscriptions` ou `companies` para filtrar no painel Owner.

### 2) Como criar tenant automaticamente?

**Action dedicada:** `ProvisionTrialCompanyAction` (domínio Acquisition ou Payments), reutilizando a lógica de `ProvisionCompanyAction`:

Em uma transação DB:
1. `Company` (`status=active`, `is_system=false`, `segment`, `whatsapp`, `email`, `document` opcional)
2. `Subscription` (`status=trial`, `plan_id=…`, `trial_ends_at=now()+2 days`)
3. `Brand` mínima (nome da empresa; sem logo → herda Expandor na UI)
4. `CompanySetting` padrão (timezone, locale, currency, map_provider)
5. `User` admin (`Role::ADMINISTRATOR`, senha do formulário)
6. Audit log `acquisition.trial.started`
7. (Opcional) enfileirar demo data

**Não** usar Asaas/checkout (planos free são rejeitados no fluxo pago).

### 3) Como aplicar roles automaticamente?

- Buscar `Role` com slug `administrator` (já seedado globalmente)
- Atribuir `role_id` no `User`
- Permissões vêm do pivot role↔permissions — **sem criar roles por empresa**

### 4) Como controlar expiração?

| Camada | Mecanismo |
|--------|-----------|
| Dados | `subscriptions.status=trial` + `trial_ends_at` |
| Job | `ExpireTrialsJob` diário (já existe) |
| Efeito | Subscription → `past_due` + Company → `suspended` |
| Middleware | `tenancy.active` bloqueia o tenant |
| Config self-serve | Constante/config `acquisition.trial_days = 2` (independente de `PAYMENT_TRIAL_DAYS` do checkout, se necessário) |

**Avaliar na implementação (fase 2):** tela “Trial expirado — fale com vendas / assine” em vez de apenas 403 genérico (melhoria de UX, não muda regra de bloqueio).

### 5) Como limitar trial?

Controles recomendados (camadas):

1. **Throttle** rota signup: N/hora por IP (ex.: 3) + N/dia por IP (ex.: 5)
2. **E-mail único global no self-serve** (validação de negócio, mesmo com unique composto no DB)
3. **Um trial ativo por e-mail / WhatsApp**
4. **Honeypot** no formulário (campo hidden)
5. Owner: bloquear empresa abusiva (`status=suspended`)
6. (Futuro) CAPTCHA se abuso real aparecer

### 6) Como preparar plano pago?

Fluxo futuro sem reescrever tenant:

```
trial → (Owner "converter" OU checkout)
     → subscription.status = active
     → plan_id = Professional/Enterprise
     → trial_ends_at = null
     → company.status = active
     → gateway preenchido se pagamento
```

Manter `source=self_serve` para métricas de funil.

### 7) Como evitar fraude no cadastro?

| Controle | Prioridade |
|----------|------------|
| E-mail único (signup) | Alta |
| Rate limit IP | Alta |
| WhatsApp obrigatório (contato real) | Alta |
| Honeypot | Média |
| Bloqueio Owner | Alta |
| CAPTCHA | Baixa (fase posterior) |
| Verificação e-mail | Média (fase 2 — não bloquear MVP) |

---

## 4. Impacto nos módulos existentes

| Módulo | Impacto | Risco | Mitigação |
|--------|---------|-------|-----------|
| Mapa | Baixo se demo/setup criar território | Tenant vazio = mapa inútil | Demo opcional ou wizard obrigatório suave |
| Visitas | Depende de campanha/imóveis | Idem | Demo data |
| Clientes/moradores | Idem | Idem | Demo / wizard |
| Comissões / estoque / produtos | Precisa catálogo | Sem produtos = path morto | Produtos exemplo opcionais |
| Branding | Positivo | — | Sem logo = Expandor (já funciona) |
| Permissões | Neutro | — | Role admin padrão |
| Layouts | Neutro | — | Botão no login + form guest |
| Payments/Asaas | Isolado | Não misturar trial free com checkout | Action separada |
| Owner panel | Extensão | — | Lista trials + ações |
| Login | Extensão | E-mail duplicado | Validação global no signup |
| Onboarding | Integração | Forçar setup pode frustrar | Manter wizard + “continuar depois” + demo opcional |

**Garantia:** self-serve não altera regras comerciais de mapa/visitas/comissões — só provisiona tenant novo isolado.

---

## 5. Isolamento e segurança

- Cada trial = `company_id` próprio → scopes BelongsToTenant intactos
- Storage continua em `companies/{id}/…`
- Platform branding intacto no login
- Rotas guest novas **fora** de `auth` + **com** throttle
- CSRF normal no form
- Senha: regras Laravel `Password::defaults()` (mínimo alinhado ao Owner create)
- Não criar `is_platform_admin`
- Não conceder `platform.*`

---

## 6. Comportamento após expiração

```
trial_ends_at <= now()
        │
        ▼
ExpireTrialsJob
        │
        ▼
subscription = past_due
company = suspended
        │
        ▼
Próximo request autenticado → tenancy.active → bloqueio
```

**Owner pode:**
- **Bloquear** antes (suspend)
- **Converter** (reativar company + subscription active + plano pago)
- **Visualizar** dados de contato (WhatsApp / e-mail) para follow-up comercial

---

## 7. Produtos exemplo (avaliação)

| Opção | Prós | Contras |
|-------|------|---------|
| A) Sem demo | Cadastro mais rápido | Mapa/visitas vazios |
| B) Demo automático | “Wow” em 2 dias | Mais dados/custo |
| C) Checkbox “Incluir dados de demonstração” | Controle do usuário | +1 campo |

**Recomendação:** **B ou C** — trial de 2 dias sem território/produtos não prova o valor do GeoSales. Preferir reutilizar `GenerateDemoDataAction` de forma assíncrona (job) para não atrasar o redirect pós-cadastro.

---

## 8. Área Owner — visão de trials

Estender lista/dashboard de empresas (não criar CRM paralelo):

| Coluna | Fonte |
|--------|-------|
| Empresa | `companies.name` |
| Responsável | admin user `name` |
| WhatsApp | `companies.whatsapp` |
| E-mail | admin / company email |
| Cadastro | `companies.created_at` |
| Expiração trial | `subscriptions.trial_ends_at` |
| Status | company + subscription (`trial` / `active` / `suspended`) |

Ações:
- Visualizar (já existe show)
- Bloquear (`suspend` existente)
- Converter para cliente (novo: muda plano/status subscription + garante company active)

Filtro: `subscription.status = trial` ou `source = self_serve`.

---

## 9. O que NÃO fazer nesta sprint (quando implementar)

- Não criar `company_subscriptions`
- Não passar trial pelo Asaas
- Não exigir logo/documentos no signup
- Não alterar branding platform/tenant rules
- Não enfraquecer `tenancy.active`
- Não permitir platform admin via self-serve

---

## 10. Conclusão

A base SaaS **já tem ~70% do que o trial precisa** (subscriptions trial, expire job, provision com brand/settings, roles, onboarding/demo).

O gap principal é **produto/aquisição**:

1. UI login + formulário guest  
2. Action de provisionamento trial 2 dias  
3. Anti-abuso  
4. Painel Owner para funil trial → cliente  

**Pronto para proposta de implementação detalhada** → ver `implementation-proposal.md`.  
**Implementação de código somente após aprovação.**
