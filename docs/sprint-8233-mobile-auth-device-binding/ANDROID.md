# ANDROID

Plataforma Capacitor já criada na 8.2.32 (`android/`).

Auth Bearer + CORS `https://localhost` / `http://localhost` / `capacitor://localhost`.

Nesta máquina de desenvolvimento: sem JDK 17 / Android SDK — `assembleDebug` não foi exigido.

Antes de validar no device:

1. Configurar `CAP_API_URL` para a API alcançável (não `localhost` do emulador; use `10.0.2.2` ou LAN)
2. `npm run build && npx cap sync android`
3. Instalar plugin de storage seguro + Preferences
4. Debug APK apenas — **sem** release / Play Store
