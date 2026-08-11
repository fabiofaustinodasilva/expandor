# FIELD-BLOCKERS

Checklist para vendedores reais nesta semana.

## BLOCKER

| ID | Item | Evidência | Ação sugerida (após aprovação) |
|----|------|-----------|--------------------------------|
| B1 | Horários Equipe ~3h adiantados vs BRT | `config/app.php` → `UTC`; `TeamPresenceActivityService::accessLabel` | `APP_TIMEZONE=America/Sao_Paulo` + display consistente |
| B2 | Confusão “hoje/ontem” perto da meia-noite BR | `isToday()` sobre instante UTC | Mesma correção timezone |

## HIGH

| ID | Item | Notas |
|----|------|-------|
| H1 | Markers genéricos (círculos) | Difícil ler status em zoom baixo |
| H2 | Glossário inconsistente no drawer (“Residência”, “casa”) | Treino de vendedor novo |
| H3 | CDN Tailwind em produção | Console warning / dependência externa |
| H4 | GPS/permissão negada | Copy já melhorou (8.2.26); validar no dispositivo real |

## MEDIUM

| ID | Item |
|----|------|
| M1 | Tips ainda citam “Voltar ao mapa” (deck apresentação) |
| M2 | Hover-only em vários controles do mapa |
| M3 | Empty hint 1× ok; status line “Nenhum ponto” ok |

## LOW

| ID | Item |
|----|------|
| L1 | Emojis no day brief / reward |
| L2 | Dual CTA sky vs brand accent |

## Fluxo de campo (status)

| Passo | Status |
|-------|--------|
| Login | OK |
| Sessão única | OK (8.2.25) |
| GPS / Minha localização | OK (opcional) |
| Mapa / markers | OK funcional; polish P1 |
| Criar ponto / visita / venda | OK (8.2.26) |
| Comissão reward | OK |
| Equipe horários | **BLOCKER visual/confiança** |
| Offline | Parcial (queue JS); documentar limites |

**Não corrigir nesta sprint sem aprovação.**
