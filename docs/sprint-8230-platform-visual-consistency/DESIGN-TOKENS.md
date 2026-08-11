# DESIGN TOKENS

Fonte de verdade: **ThemeService** (`--primary`, `--bg`, `--border`, …).  
8.2.30 adiciona **aliases** em `client-ui.css` — não substitui o tema.

## Antes → depois

| Token pedido | Equivalente real (antes) | Alias 8.2.30 |
|--------------|--------------------------|--------------|
| `--color-primary` | `--primary` / `--accent` | `var(--primary)` |
| `--color-primary-hover` | `filter: brightness` / sky hover | `color-mix(… 82%, #000)` |
| `--color-background` | `--bg` | `var(--bg)` |
| `--color-surface` | `--bg-elevated` | `var(--bg-elevated)` |
| `--color-surface-muted` | `--bg-soft` | `var(--bg-soft)` |
| `--color-border` | `--border` | `var(--border)` |
| `--color-text` | `--text` / `--text-on-bg` | `var(--text, var(--text-on-bg))` |
| `--color-text-muted` | `--muted` / `--muted-on-bg` | `var(--muted, var(--muted-on-bg))` |
| `--color-success` | `--success` | `var(--success)` |
| `--color-warning` | `--warning` | `var(--warning)` |
| `--color-danger` | `--highlight` | `var(--highlight, #EF4444)` |
| `--color-info` | `--primary` | `var(--primary)` |

## Geometria

| Token | Valor |
|-------|-------|
| `--control-height` | `2.75rem` |
| `--radius-control` | `0.75rem` |
| `--radius-card` | `1rem` |
| `--shadow-elevated` | `0 10px 40px rgba(0,0,0,.35)` |
| `--client-touch` | `44px` (já existia 8.2.3) |
| `--client-radius` | `0.875rem` (já existia) |

## Tipografia (tokens)

`--font-page-title` 1.4rem · `--font-section-title` 1.05rem · `--font-card-title` 1.02rem · `--font-body` 0.95rem · `--font-label` 0.88rem · `--font-helper` 0.85rem · `--font-caption` 0.75rem

## Super Admin

`layouts/platform.blade.php` agora declara `--primary`, `--color-primary`, `--control-height` apontando para o amber existente. Identidade da plataforma **não** foi trocada.
