# GOOGLE-MAPS-CONNECTION

## Estados UX

| Estado | Condição |
|--------|----------|
| NOT AVAILABLE | Plano/flag não permite |
| NOT CONFIGURED | Permite, sem conexão útil |
| CONNECTED | enabled + status connected + key |
| ERROR | Último teste falhou |

## Fluxo

1. Digita API Key Web  
2. Salvar e ativar → testa → se OK: `connected` + enabled  
3. Se falha: persiste encrypted com `error` + mensagem; não ativa  
4. Testar conexão: mesma sonda  
5. Desconectar: limpa credentials, `disconnected`, resolver → Leaflet

## Aviso comercial

UI explica que cobrança Google é da conta GCP da empresa.
