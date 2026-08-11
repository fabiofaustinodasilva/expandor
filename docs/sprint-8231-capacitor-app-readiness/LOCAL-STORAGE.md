# LOCAL-STORAGE

| Camada | Serve | Não serve |
|--------|-------|-----------|
| localStorage | flags, tips, fila **pequena** atual | fila de vendas, cache de mapa, PII |
| sessionStorage | reward 8.2.23 (aba) | persistência app |
| Capacitor Preferences | settings | operacional |
| IndexedDB | cache web | app nativo misto |
| **SQLite (`@capacitor-community/sqlite`)** | pontos, visitas pending, fila, agenda cache | — |

**Recomendação app:** SQLite. Justificativa: queries, transação, fila, volume de território, sobrevive a kill do WebView. localStorage tem cota e é limpo com dados do site.

Web seller continua localStorage até o app existir.

## Segurança local

Não guardar senha, API keys Google, PAT em localStorage.  
Token → Keychain/Keystore (`@capacitor/preferences` **não** é suficiente para PAT).  
Fila pode ter telefone de cliente → criptografar DB ou limitar campos na fila.
