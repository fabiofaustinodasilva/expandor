# NETWORK-UX

Estados (não modal):

| Estado | UI |
|--------|-----|
| ONLINE | ● Online |
| OFFLINE | ⚠ Offline · N ações pendentes |
| SINCRONIZANDO | Sincronizando… |
| ATENÇÃO | 1 ação precisa de atenção |

Startup: splash → sessão Keychain → config pública (branding/features) → dados mínimos → mapa.  
Offline: shell + cache + fila; sem tiles.

Presence ping em **foreground** ~2 min (igual `TouchUserPresence`). Sem heartbeat agressivo.

Logout: revoga token, limpa Keychain, limpa fila **sensível**; cache de território pode ficar se política permitir (MVP: limpar).
