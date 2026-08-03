# Sprint 5.5 — Proposta de implementação (após aprovação)

Este documento descreve **como** implementar, em fases. Não executar até a análise ser aprovada.

## Fases sugeridas

### Fase 5.5.1 — Fundação (backend trial)
- Config `acquisition.trial_days = 2`, `acquisition.plan_slug = professional` (ou `free` — decidir na aprovação)
- `ProvisionTrialCompanyAction`
- Validação: nome, segmento, nome responsável, WhatsApp, e-mail único global (signup), senha
- Throttle `throttle:trial-signup`
- Testes: cria company+subscription trial+admin; e-mail duplicado; isolamento

### Fase 5.5.2 — UX guest
- Botão no login: “Começar teste grátis”
- Página/form `GET/POST /teste-gratis` (guest)
- Auto-login + redirect setup/mapa
- Mensagens de erro amigáveis
- Honeypot
- Testes de renderização + submit feliz

### Fase 5.5.3 — Demo + expiração UX
- Job opcional `SeedTrialDemoDataJob`
- Banner “X horas restantes no teste”
- Tela amigável pós-expiração (upgrade / contato)
- Garantir `ExpireTrialsJob` cobre self-serve

### Fase 5.5.4 — Owner funil
- Filtro/lista “Trials”
- Colunas: empresa, responsável, WhatsApp, cadastro, expiração, status
- Ação **Converter para cliente** (plano + active)
- Métricas: trials ativos, conversões, expirados

## Contratos de API/rotas (propostos)

| Método | Rota | Auth | Descrição |
|--------|------|------|-----------|
| GET | `/teste-gratis` | guest | Formulário |
| POST | `/teste-gratis` | guest + throttle | Criar trial |
| GET | `/platform/companies?filter=trial` | Owner | Lista |
| POST | `/platform/companies/{id}/convert` | Owner | Converter |

## Modelo de dados (sem nova tabela)

```
companies
  + segment, whatsapp, email, document?
  + status = active (durante trial)
  + (opcional) acquisition_source = self_serve

subscriptions  (REUTILIZAR)
  status = trial
  plan_id = <plano trial>
  trial_ends_at = now() + 2 days
  starts_at = now()

users
  role = administrator
  email = e-mail do formulário (único no signup)
  company_id = nova empresa
```

## Decisões pendentes de aprovação

Marque na aprovação:

- [ ] Plano do trial: **Free** ou **Professional** (limites diferentes)
- [ ] E-mail único: **global no signup** (recomendado)
- [ ] Demo data: **automática** / **checkbox** / **nenhuma**
- [ ] Pós-cadastro: ir para **Setup wizard** ou **Mapa**
- [ ] Converter cliente: só Owner ou também self-checkout Asaas depois

## Critérios de aceite (quando implementar)

1. Visitante cria trial em &lt; 2 minutos sem Owner  
2. Login com a conta nova acessa o operacional  
3. Branding Expandor no login; tenant sem logo herda Expandor  
4. Outra empresa não vê dados do trial  
5. Após 2 dias, acesso bloqueado  
6. Owner lista, bloqueia e converte  
7. Mapa/visitas/comissões/branding/permissões existentes não regrediram  
8. Cadastro abusivo limitado por throttle + e-mail único  

## Estimativa de risco

| Risco | Severidade | Mitigação |
|-------|------------|-----------|
| Trial vazio (sem valor) | Alta | Demo ou wizard |
| E-mail duplicado vs login global | Alta | Unique no signup |
| Abuso de milhares de trials | Alta | Throttle + Owner block |
| Drift Provision vs Owner create | Média | Action única self-serve |
| UX 403 seco na expiração | Média | Tela trial expirado |

## Ordem de testes obrigatórios

1. Signup feliz → admin + trial 2d  
2. E-mail já usado → 422  
3. Throttle → 429  
4. Tenant isolation  
5. Expire job suspende company  
6. Owner convert reativa  
7. Regression smoke: mapa, visitas, comissões, branding  

---

**Gate:** nenhuma linha de código de feature até aprovação explícita deste pacote `docs/sprint-55/`.
