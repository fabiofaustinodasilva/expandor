# TYPOGRAPHY

## Famílias

| Superfície | Família |
|------------|---------|
| CRM operacional | ThemeService `fontFamily` (sans) |
| Login / branding | heading font do brand + body sans |
| Mapa | Tailwind default sans (CDN) |
| Super Admin | `"Segoe UI", Tahoma, Geneva, Verdana, sans-serif` |

Sem serif no CRM. Display/serif permanece só em marketing/login quando o brand já define heading.

## Hierarquia operacional

| Nível | Token / classe | Tamanho |
|-------|----------------|---------|
| Page title | `.client-page-header__title` | `--font-page-title` (1.4rem) |
| Section title | `.client-section-card__title` | ~1.02–1.05rem |
| Card title | headings em cards | ≤1.05rem |
| Body | layout / `.table` | ~0.92–0.95rem |
| Label | `.form-group label` | 0.88rem |
| Helper | `.header-meta` | 0.9rem |
| Caption | `.badge`, eyebrow | 0.72–0.75rem |

Títulos enormes em telas operacionais: evitados. Page header usa 1.4rem, não display.

## O que não mudou

Fontes do ThemeService / branding. Sem novo webfont.
