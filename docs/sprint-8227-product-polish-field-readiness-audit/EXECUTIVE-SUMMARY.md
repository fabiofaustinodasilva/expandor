# EXECUTIVE SUMMARY

## Veredicto

O Expandor está **operacionalmente utilizável em campo**, mas o acabamento ainda mistura dois “sistemas visuais” (brand ThemeService vs mapa Tailwind `sky-500`) e há um **bug de timezone P0** que distorce horários da Equipe (~+3h se o operador espera BRT).

## Top 10 problemas

| # | Problema | Severidade | Área |
|---|----------|------------|------|
| 1 | `config/app.php` timezone = **UTC** hardcoded; UI BR formata “como local” | **P0 / BLOCKER** | Equipe / horários |
| 2 | Markers = bolinhas 14px; pouca leitura “residência” | P1 | Mapa |
| 3 | Glossário: ponto / residência / casa / cliente misturados | P1 | Campo + lists |
| 4 | Design dual: `--accent` vs `bg-sky-500` no mapa | P1 | Design system |
| 5 | Tailwind + Lucide + Leaflet via **CDN** em layout operacional | P1 | Prod / app |
| 6 | Equipe: “último acesso” vs login vs atividade pouco claros | P1 | Equipe |
| 7 | Emojis misturados com Lucide no mapa (📍✅🏠🪙) | P2 | Ícones |
| 8 | Tipografia/espaçamento inconsistentes entre CRM e mapa | P2 | Polish |
| 9 | Empty/loading/error sem padrão único | P2 | UX |
| 10 | Capacitor: cookie session + window APIs + hover | P2 | App readiness |

## O que NÃO é blocker para campo esta semana

- Comissão / reward / sessão única / password reset / Google Maps provider (já validados).
- Map Operation Sheet 8.2.26 + hotfix footer.

## Próximo passo recomendado

1. Aprovar correção **timezone** (slice P0).  
2. Aprovar padrão de **house pin** (slice P1 mapa).  
3. Glossário + CTAs mapa alinhados ao brand (P1).  
4. Só depois Capacitor (P2).
