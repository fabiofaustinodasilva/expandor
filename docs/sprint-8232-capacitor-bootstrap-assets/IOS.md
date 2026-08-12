# IOS

## Limitação desta máquina

Desenvolvimento em **Windows**. Não há Xcode. **`npx cap add ios` / build iOS não ocorreu e não deve ser alegado.**

## O que foi feito

- Pacote `@capacitor/ios` no `package.json` (preparação).
- `capacitor.config.ts` compartilhado (`appId` / `appName` / `webDir`).
- Documentação de requisitos.

## O que falta (macOS + Xcode)

```
npx cap add ios
npx cap sync ios
npx cap open ios
```

Info.plist futuro (não nesta sprint):

- `NSLocationWhenInUseUsageDescription`
- display name Expandor
- URL scheme / associated domains (deep links 8.2.31)

## Git futuro

Versionar `ios/App` fonte. Ignorar Pods, DerivedData, certificados `.p12` / `.mobileprovision`.
