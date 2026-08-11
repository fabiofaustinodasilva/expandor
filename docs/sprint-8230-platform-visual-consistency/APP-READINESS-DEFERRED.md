# APP-READINESS-DEFERRED

Não iniciar Capacitor nesta sprint.

| Item | Risco | Ação |
|------|-------|------|
| Tailwind CDN (`cdn.tailwindcss.com`) | Alto em WebView | Empacotar CSS no App Readiness |
| Lucide CDN (`unpkg.com/lucide@0.469.0`) | Médio | Bundle local |
| Leaflet / Google Mutant | Médio | Bundle local |
| `layouts.app` legado | Baixo | Unificar rail quando o layout residual sair |
| “Casas visitadas hoje” | Copy | Glossário + testes de mapa juntos |
| Fallback JS “Residência” | Copy | só com mudança em `operational-map.js` |
| Marketplace/onboarding emojis | Baixo | fora do CRM operacional |

Substituição trivial feita agora: nenhum CDN foi removido (exigiria refactor do mapa). Documentado para 8.2.31+.
