# ANDROID

## Objetivo desta sprint

Adicionar a plataforma Capacitor Android **se o ambiente permitir**. Sem keystore, sem AAB/APK de loja, sem signing.

## appId / name

- ApplicationId: `br.com.expandor.app`
- App name: Expandor

## Ambiente (máquina de desenvolvimento)

- OS: Windows 10/11
- Node: ver `TEST-REPORT.md` (instalação LTS via winget se ausente no PATH)
- Android SDK / Android Studio: **não assumir presente**
- Java/Gradle: só após `npx cap add android`

## Comandos

```
npx cap add android
npx cap sync android
```

Build debug (somente se SDK existir):

```
cd android
.\gradlew assembleDebug
```

## Git

Versionar `android/` (fonte nativa). Ignorar:

- `android/.gradle/`
- `android/app/build/`
- `android/local.properties`
- `*.keystore` / `*.jks` / APK/AAB

## Status

- `npx cap add android` — **OK** (projeto nativo em `android/`).
- `applicationId` / namespace: `br.com.expandor.app`
- `assembleDebug` — **não rodou**: JDK 17+ e Android SDK ausentes nesta máquina Windows (há só Java 8 no PATH, `JAVA_HOME` unset). Não falsificado.
