# LocationService

Interface única:

- `getCurrentPosition()`
- `permissionStatus()`
- erros normalizados: `permission_denied`, `unavailable`, `timeout`, `position_error`
- mensagem UX: "Não foi possível acessar sua localização."

Web: `navigator.geolocation`.  
Capacitor: `@capacitor/geolocation` via `Capacitor.Plugins.Geolocation`, foreground / current position only.

Não há `watchPosition`, background tracking nem espalhamento de `navigator` no restante do app.

GPS é opcional: cadastro manual de ponto continua permitido.
