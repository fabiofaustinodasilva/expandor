# APP-READINESS

## Antes do Capacitor

| Dependência | Risco | Notas |
|-------------|-------|-------|
| Session cookie | Alto | `SameSite`/`Secure` para WebView |
| Tailwind CDN | Alto | Empacotar CSS build |
| Lucide CDN | Médio | Bundle local |
| Leaflet CDN | Médio | Bundle local |
| `sessionStorage` reward | Médio | Ok em WebView; testar |
| `navigator.geolocation` | Alto | Permissões nativas |
| `navigator.onLine` + offline queue | Médio | Já parcial |
| Hover UI | Médio | Precisa `:active` |
| Layout desktop Equipe/CRM | Alto | Telas não-mapa |
| Deep link password reset | Médio | Doc 8.2.24 Capacitor |
| Downloads / popups | Baixo | Pouco uso no campo |

## Já preparado

- Presence em `users.last_seen_at` (não depende SESSION_DRIVER).  
- Sessão única seller.  
- mapFetch 401 → login.

## Não fazer agora

Instalar Capacitor nesta sprint (proibido pelo escopo).
