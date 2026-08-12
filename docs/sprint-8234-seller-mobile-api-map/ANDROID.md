# Android / Capacitor

- Plugin: `@capacitor/geolocation` ^7 (compatível com Capacitor 7.4).
- Manifest: `ACCESS_COARSE_LOCATION` + `ACCESS_FINE_LOCATION`. Sem `ACCESS_BACKGROUND_LOCATION`.
- iOS (quando o projeto iOS existir): `NSLocationWhenInUseUsageDescription` = "O Expandor usa sua localização para centralizar o mapa e registrar o ponto da visita. Não rastreamos em segundo plano." Projeto `ios/` ainda não existe neste repo.
- Shell: `npm run build` (web + seller + prepare) e `npx cap sync android`.
- **Obrigatório:** `CAP_API_URL=https://seu-dominio.com` (ou `APP_URL`) no ambiente/`.env` antes do prepare. Sem isso o shell fica com `EXPANDOR_API_BASE=""` e o login no APK falha com "Resposta de login inválida".
- Som: `public/sounds/commission-coins.wav` copiado para `public/capacitor-shell/sounds/`.
- Sem APK release nesta sprint.

`npx cap sync android` concluiu localmente e registrou `@capacitor/geolocation@7.1.8`. Sem APK release. Se outro ambiente não tiver Android SDK/Gradle, o sync pode falhar; plugin e manifest já ficam no repo.

Ver também: `HOTFIX-LOGIN-ENVELOPE.md`.
