# AUDIT — EXP Vendedor visual polish

Escopo: shell Capacitor (`prepare-capacitor-shell.mjs`, `exp-vendedor-shell.css`, `bootstrap-shell.js`). Sem API, banco ou regras.

## Shell
- 6 abas: Mapa, Agenda, Clientes, Resultado, Comissão, Mais.
- CSS já isolado com `body:not(.client-ui)` — não cortar Agenda/Clientes web.
- Tokens existentes: `--exp-blue`, `--exp-orange`, `--success`, `--warning`, `--danger`, `--surface`, `--touch: 48px`.

## Congelado (não tocado)
Login/sessão/Preferences, GPS/bbox/markers significado, first-approach payload, `scope=all`, due_day 5–30, deck fullscreen, handoff, complete sale.

## Decisões
- GPS: estado normal neutro (card); Apresentar produtos permanece CTA comercial laranja.
- Agenda: agrupamento visual Hoje/Amanhã/Próximos/Atrasados no cliente; request continua `scope=all`.
- Resultado: sem métrica de conversão inventada.
- Comissão: valores do backend; verde só em `badge--paid`.
- Rede: banner âmbar + “Tentar novamente”; não trata como sessão expirada.
- Sem nova lib de ícones/skeleton.
