# Sprint 8.1.9.1 — Blindagem Total de Unicidade

## Objetivo

Eliminar definitivamente qualquer possibilidade de criação de usuários ou empresas duplicadas, validando **antes** de criar checkout, pagamento, empresa ou usuário.

## Mensagens UX (obrigatórias)

| Campo | Mensagem |
|-------|----------|
| E-mail | Este e-mail já possui uma conta cadastrada no Expandor. Utilize outro e-mail administrativo ou faça login. |
| CPF/CNPJ | Já existe uma empresa cadastrada com este CPF/CNPJ. |

Nunca expor erro SQL ao usuário.

## Ordem de validação

1. E-mail (tabela `users`, global)
2. CPF/CNPJ (tabela `companies`, normalizado)
3. Somente se ambos livres → criar checkout / pagamento / empresa / usuário

## Onde foi reforçado

| Fluxo | Camada |
|-------|--------|
| Assinatura pública / Checkout PIX / Cartão | `StoreCheckoutRequest` + `CreateCheckoutAction` |
| Provisionamento | `ProvisionCompanyAction` |
| Webhook Mercado Pago | `ProcessMercadoPagoPaymentAction` (pré-check antes de Paid/provision) |
| Webhook genérico (Asaas etc.) | `WebhookService::handleConfirmedPayment` |
| Trial | `StartTrialRequest` + `ProvisionTrialCompanyAction` |
| Platform Admin (manual) | `StorePlatformCompanyRequest` + `PlatformCompanyService` |
| APIs / serviço de usuários | `UserService::create` / `update` |

## Banco

Mantidas as constraints UNIQUE da Sprint 8.1.9:

- `users.email` UNIQUE global
- `companies.document` UNIQUE

Validações de aplicação ocorrem **antes** da gravação para evitar erros SQL na UX.

## Compatibilidade

Sem alteração de comportamento de:

- Mercado Pago (preferência, PIX, fetch)
- Fluxo de provisionamento (apenas guard adicional)
- Billing / Tenancy / Login

## Testes

Arquivo: `tests/Feature/Release/Sprint8191TotalUniquenessShieldTest.php`

1. E-mail duplicado no checkout PIX  
2. Documento duplicado no checkout Cartão  
3. E-mail duplicado via webhook (não provisiona)  
4. Documento duplicado via webhook (não provisiona)  
5. Cadastro manual Platform Admin — e-mail duplicado  
6. Cadastro manual Platform Admin — documento duplicado  
7. Camada de serviço bloqueia antes de persistir  
8. PIX sucesso com identidade livre  
9. Cartão sucesso com identidade livre  
10. Platform Admin sucesso com identidade livre  

## Relatório final

- Blindagem centralizada em `RegistrationIntegrityService::assertRegistrationIdentityAvailable`
- Todos os pontos de entrada de cadastro passam pela mesma ordem e-mail → documento
- Webhooks não provisionam em conflito de identidade (resposta amigável / `provisioned: false`)
- Constraints UNIQUE permanecem como última linha de defesa

### Resultado dos testes

| Suite | Resultado |
|-------|-----------|
| Sprint 8.1.9.1 | **10 passed** |
| Regressão 8.1.7 + 8.1.8 + 8.1.9 + 8.1.9.1 | **28 passed** |

### Arquivos alterados (código)

- `app/Domains/Security/Services/RegistrationIntegrityService.php`
- `app/Domains/Payments/Actions/CreateCheckoutAction.php`
- `app/Domains/Payments/Actions/ProvisionCompanyAction.php`
- `app/Domains/Payments/Actions/ProcessMercadoPagoPaymentAction.php`
- `app/Domains/Payments/Services/WebhookService.php`
- `app/Domains/Platform/Services/PlatformCompanyService.php`
- `app/Domains/Platform/Requests/StorePlatformCompanyRequest.php`
- `app/Domains/Acquisition/Actions/ProvisionTrialCompanyAction.php`
- `app/Domains/Acquisition/Requests/StartTrialRequest.php`
- `app/Domains/Company/Services/UserService.php`
- `tests/Feature/Release/Sprint8191TotalUniquenessShieldTest.php`
- `docs/sprint-8191-total-uniqueness-shield/README.md`
