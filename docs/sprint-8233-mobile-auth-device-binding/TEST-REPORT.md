# TEST-REPORT

Ver `Sprint8233MobileAuthDeviceBindingTest` + regressões.

| # | Caso | Onde |
|---|---|---|
| 1–5 | login Seller, token, ability | backend |
| 6–7 | /me + logout | backend |
| 8–10 | device_id + binding | backend |
| 11 | app A → app B | backend |
| 12 | web → app | backend |
| 13 | app → web | backend |
| 14 | session_replaced 401+code | backend |
| 15–16 | outro Seller / tenant | backend |
| 17 | password reset invalida | backend |
| 18–19 | header ausente / device errado | backend |
| 20 | rate limit + audit sem token | backend |
| 21–30 | storage, apiFetch, login UI, CORS/CSP | estrutural |

Regressões: Sprint8232, 8231, 8230, 8225, 8224, Login, Auth, Maps, SalesApp, PilotSeller, MobileApi.
