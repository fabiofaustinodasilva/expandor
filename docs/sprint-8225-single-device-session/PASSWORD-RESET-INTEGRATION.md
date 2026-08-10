# PASSWORD-RESET-INTEGRATION

Sprint 8.2.24 permanece; esta sprint completa invalidação web:

1. Novo password + `remember_token`
2. `SellerSingleSessionService::invalidateAllSessions` (`session_version++`)
3. Sanctum PATs deletados (já em 8.2.24)
4. Redirect login (sem auto-login)
5. Próximo login faz bind da nova versão

Não enfraquece anti-enumeração, rate limit nem templates de e-mail.
