# SECURE-STORAGE

Interface: `resources/js/mobile/secure-auth-storage.js`

- `getToken` / `setToken` / `removeToken`
- `getDeviceId` / `ensureDeviceId`
- `clearAuth`

**Nunca** `localStorage` / `sessionStorage` / arquivo texto.

## Drivers (ordem)

1. Memória do processo (cache)
2. `Capacitor.Plugins.SecureStorage` (Keychain / Keystore) se instalado
3. `Capacitor.Plugins.Preferences` se instalado

Nenhuma criptografia caseira.

## MVP nesta máquina

`@capacitor/preferences` / plugin Keychain **não** foram instalados (evitar sync nativo sem SDK). O wrapper já fala com os plugins oficiais quando existirem.

Antes da loja:

1. Instalar plugin maduro Keychain/Keystore (ex. `@aparajita/capacitor-secure-storage`) **ou** no mínimo `@capacitor/preferences`
2. `npx cap sync`
3. Confirmar que o token sobrevive a kill do app

Sem plugin, o token vive só em memória: restart do WebView pede login de novo (seguro, mas ruim de UX).
