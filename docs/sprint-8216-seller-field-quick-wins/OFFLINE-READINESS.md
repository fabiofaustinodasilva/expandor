# OFFLINE-READINESS — Sprint 8.2.16

Somente impactos futuros para app Capacitor / offline-first. **Não implementado nesta sprint.**

| Ponto | Hoje | Futuro app |
|-------|------|------------|
| Criação de retorno | POST FirstApproach / visita com `follow_up_at` → FollowUp no servidor | Enfileirar payload + reconciliar `FollowUp` por `company_id`/`visit_id`/`scheduled_at` |
| Chip Hoje | Contagem SSR do servidor no load do mapa; bump otimista no JS | Preferir contagem da fila local + sync; evitar depender só do DOM |
| Agenda `?day=today` | Query server-side em `scheduled_at` | Filtro local sobre FollowUps sincronizados |
| Validação required_if | FormRequest Laravel | Espelhar regra no client offline; servidor continua source of truth |
| Mais / Apresentar | Rotas web filtradas | Deep links nativos para deck + Agenda; não copiar nav admin |
| `operational-map.js` | Estado no DOM + fetch | Extrair regras de retorno para serviço compartilhado (não duplicar) |

Evitar novas dependências de sessão visual do browser, reload completo ou regras só no frontend.
