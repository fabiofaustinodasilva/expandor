# APK de QA — EXP Vendedor (Windows)

Um comando. Não use `npm run build` sozinho para gerar APK de outro celular: o `.env` local com `APP_URL=http://127.0.0.1:8000` seria bakeado no app.

## 1. PowerShell

```powershell
cd C:\Users\Fabio Faustino\Desktop\GeoSales-CRM
```

## 2. Java

O Gradle 8.11 **não roda no JBR 25** da Android Studio. O Capacitor 7 compila com **Java 21**.

Use **JDK 21** (não Java 8, não JBR 25, JDK 17 não basta).

O comando `android:qa` procura:

- `EXP_JAVA_HOME` se definido
- `C:\Program Files\Microsoft\jdk-21*`
- `C:\Program Files\Microsoft\jdk-17*` (só se o projeto não exigir source 21)
- JBR da Android Studio **somente se** a versão for 17–21

```powershell
winget install --id Microsoft.OpenJDK.21 -e --accept-package-agreements --accept-source-agreements
```

## 3. Um comando

```powershell
npm run android:qa
```

Isso define API/Web de produção, faz `npm run build`, `npx cap sync android`, verifica `runtime-config.js`, roda `assembleDebug` e inspeciona a APK.

## 4. Onde está a APK

- `android/app/build/outputs/apk/debug/app-debug.apk`
- Cópia: `dist/exp-vendedor-8.2.34-qa.apk`

Instale esse arquivo no Android de QA (Xiaomi ou outro). Debug-signed. Sem signing de loja nesta sprint.

## 5. Conferir

Sobre o app: `EXP Vendedor · 8.2.34` e `QA · <git hash>`. Sem URL da API na tela.

Desenvolvimento local continua com `npm run build` (modo `development`) e pode usar `APP_URL` local.
