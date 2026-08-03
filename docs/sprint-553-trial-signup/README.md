# Sprint 5.5.3 — Cadastro Trial SaaS automático

## Objetivo

Fluxo público para qualquer empresa criar conta de teste Expandor **sem intervenção do Owner**, reutilizando a arquitetura existente.

## Rota pública

| Método | Path | Nome |
|--------|------|------|
| GET | `/cadastro` | `signup.create` |
| POST | `/cadastro` | `signup.store` (`throttle:trial-signup`) |

Aliases mantidos: `/teste-gratis` (`trial.create` / `trial.store`) → mesmo controller.

Login CTA → `/cadastro`.

## Formulário

- Empresa: nome, segmento  
- Administrador: nome, e-mail, WhatsApp/telefone, senha, confirmação  
- Dados iniciais: **Começar vazio** | **Criar ambiente demonstração**  
- Aceite: termos de uso  
- CTA: **Criar minha conta grátis**  
- Honeypot: `website`  

## Após cadastro (automático)

Via `ProvisionTrialCompanyAction` (mesmo padrão de `ProvisionCompanyAction`):

1. `Company` (tenant)  
2. Usuário **Administrator** da própria empresa  
3. `Subscription` `trial`, plano **Professional**, `trial_ends_at` = **+2 dias**  
4. Brand + settings + onboarding run  
5. Demo opcional via `OnboardingService::generateDemo`  
6. Login + redirect → **Setup Wizard** (`setup.show`)  

## Segurança

- E-mail único global (bloqueia múltiplos trials)  
- Throttle IP (`acquisition.signup` per minute/hour)  
- Honeypot anti-bot  
- Senha confirmada + `Password::defaults()`  
- Termos obrigatórios  

## O que não mudou

Login, usuários/tenants existentes, permissões, mapa, clientes, vendas, comissão, estoque.

## Testes

```bash
.\.tools\php\php.exe artisan test --filter=CadastroTrialTest
.\.tools\php\php.exe artisan test --filter=TrialSignupSprint55Test
```

## Arquivos

- `routes/web.php`  
- `app/Http/Controllers/Web/Acquisition/TrialSignupController.php`  
- `app/Domains/Acquisition/Actions/ProvisionTrialCompanyAction.php`  
- `app/Domains/Acquisition/Requests/StartTrialRequest.php`  
- `resources/views/acquisition/trial/create.blade.php`  
- `tests/Feature/Acquisition/CadastroTrialTest.php`  
