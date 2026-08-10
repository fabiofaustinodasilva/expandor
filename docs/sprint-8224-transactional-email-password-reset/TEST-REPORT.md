# TEST-REPORT

Suite: `tests/Feature/Auth/Sprint8224TransactionalEmailPasswordResetTest.php`

## Cobertura mapeada

| # | Caso | Teste |
|---|------|-------|
| 1 | Login mostra Esqueceu | `test_login_shows_forgot_password_link` |
| 2 | Formulário forgot | `test_forgot_password_form_opens` |
| 3 | E-mail existente notifica | `test_existing_email_receives_reset_notification` |
| 4–5 | Inexistente + anti-enum | `test_unknown_email_*` / `test_response_does_not_enumerate_*` |
| 6 | Token válido | `test_valid_token_opens_reset_form` |
| 7 | Token inválido | `test_invalid_token_fails_reset` |
| 8 | Expirado | `test_expired_token_fails` |
| 9 | Single-use | `test_used_token_cannot_be_reused` |
| 10–12 | Update + auth | `test_password_is_updated_*` |
| 13 | Confirm | `test_password_confirmation_required` |
| 14 | Rate limit | `test_rate_limit_on_password_email` |
| 15–18 | Roles + platform | `test_seller_manager_admin_and_platform_admin_can_recover` |
| 19 | Tenant | `test_tenant_does_not_leak_*` |
| 20–21 | Mail/logs | `test_mail_does_not_contain_*` |
| 22 | SMTP erro | `test_smtp_failure_*` |
| 23 | Mobile | `test_mobile_viewport_*` |
| 24–25 | Login/dashboard | `test_login_and_dashboard_regression_smoke` |
| 26–27 | Maps/routes | `test_maps_and_integrations_routes_unaffected` |

Extras: inactive user, token table cleared.

## Resultado local

- `Sprint8224TransactionalEmailPasswordResetTest`: **21 passed**
- Regressão Login + Sprint8217/8221/8222/8223/82231: **108 passed**
- Sem push / sem merge
