# Sprint 5.5 — Notas de implementação

## Arquivos principais

| Área | Path |
|------|------|
| Config | `config/acquisition.php` |
| Provision | `app/Domains/Acquisition/Actions/ProvisionTrialCompanyAction.php` |
| Converter | `app/Domains/Acquisition/Actions/ConvertTrialCompanyAction.php` |
| Request | `app/Domains/Acquisition/Requests/StartTrialRequest.php` |
| HTTP | `app/Http/Controllers/Web/Acquisition/TrialSignupController.php` |
| View | `resources/views/acquisition/trial/create.blade.php` |
| Login CTA | `resources/views/auth/login.blade.php` |
| Testes | `tests/Feature/Acquisition/TrialSignupSprint55Test.php` |

## Segurança

- E-mail único global no signup (além do unique por company no DB)
- Honeypot `website`
- Throttle `trial-signup`
- Sem `is_platform_admin`
- Isolamento tenant via `company_id`

## Expiração

Job diário existente → subscription `past_due` + company `suspended` → `tenancy.active` bloqueia operação.

## Demo data

Se checkbox marcado: produtos, propriedades, visitas, clientes, vendedor demo (via onboarding existente).  
Se desmarcado: ambiente limpo + onboarding inicializado.
