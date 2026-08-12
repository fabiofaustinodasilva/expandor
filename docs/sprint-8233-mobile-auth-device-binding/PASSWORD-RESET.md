# PASSWORD-RESET

## App (esta sprint)

O link **Esqueceu sua senha?** abre o fluxo **web** atual:

`{APP_URL}/esqueci-minha-senha`

Preferência: system browser (`Browser.open` se o plugin existir; senão `window.open`).

Não há PasswordBroker no app. Não duplicar e-mail transacional.

## Backend (já 8.2.24)

Reset incrementa `session_version` e apaga **todos** os PATs. Token mobile deixa de autenticar.

## Deep link futuro

`https://{host}/redefinir-senha/{token}` → App Link / Universal Link.

Não implementado nesta sprint (sem association file / entitlements).
