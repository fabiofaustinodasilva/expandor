# ACCESSIBILITY-AUDIT

## Pontos positivos

- Sheets com `role="dialog"` / `aria-modal` (8.2.26).  
- Marcas `R`/`×` além da cor.  
- Empty hint `aria-live`.

## Gaps

| Item | Severidade |
|------|------------|
| Contraste sky-on-slate em alguns chips | Média |
| Foco trap incompleto em modais legados | Média |
| Hover sem equivalente teclado/touch | Média |
| Emoji como único sinal (reward) | Baixa |
| Tabelas sem caption/summary | Baixa |
| Skip link / landmarks no mapa | Baixa |

## Meta P1

Focus ring visível + ESC fecha sheets + contraste WCAG AA em status badges.
