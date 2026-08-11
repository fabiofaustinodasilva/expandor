# DESIGN-SYSTEM-AUDIT

## Tokens existentes

| Fonte | Tokens |
|-------|--------|
| `ThemeService::cssVariables` | `--bg`, `--accent`, `--primary`, `--highlight`, `--success`, … |
| `client-ui.css` | Consome ThemeService |
| Mapa (Tailwind CDN) | `slate-*`, `sky-500`, utilitários soltos |

## Problema central

Dois sistemas paralelos:

1. **Brand** (login, CRM, botões `.btn-primary` → `--accent`)  
2. **Mapa campo** (`bg-sky-500`, borders slate hardcoded)

## Tipografia

- Login: heading brand + body ThemeService.
- Mapa: Tailwind default sans.
- Mistura serif/sans: residual em branding; mapa quase só sans.
- Proposta: 1 família sans operacional; display só em marketing/login.

## Spacing / radius

- Sheets: `rounded-t-3xl` / `rounded-xl` inputs.
- Cards CRM: radius variado.
- Proposta mínima: radius `0.75rem` controles; `1rem` cards; `1.5rem` sheets.

## Botões (padrão proposto)

| Tipo | Uso | Visual |
|------|-----|--------|
| Primary | Salvar / Confirmar | `--accent` ou sky unificado **uma** escolha |
| Secondary | Cancelar | border slate / ghost |
| Danger | Excluir | rose |
| Ghost | Toolbar | transparent |
| Icon-only | Fechar / GPS | 44×44 min |

Hoje mapa ignora `--accent` nos CTAs principais.

## Forms

- Labels `text-xs text-slate-400` no mapa.
- CRM forms mais “admin”.
- Unificar: label, required `*`, erro rose, CTA no footer (já no sheet).

## Ícones

- Lucide = padrão.
- Remover emoji progressivamente (📍✅🏠) ou isolar só reward.
- Markers futuros: SVG único, não emoji.

## Design system mínimo Expandor (proposta)

1. CSS variables brand (já existem).  
2. Mapa passa a usar `var(--accent)` / classes mapeadas.  
3. Status colors = `STATUS-COLOR-MATRIX.md`.  
4. Componentes: Sheet, Drawer, Badge status, EmptyState, Toast.  
5. Sem novo framework.
