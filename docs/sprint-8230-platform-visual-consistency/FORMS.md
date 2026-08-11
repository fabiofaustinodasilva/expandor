# FORMS

Padrão operacional (`layouts/operational.blade.php`):

| Elemento | Regra |
|----------|-------|
| Label | bloco, 0.88rem, `--muted` |
| Input / select / textarea | `.form-control`, radius 0.75rem, padding 0.75rem |
| Focus | outline 2px `--primary` (`:focus-visible`) |
| Disabled | nativo + `.btn:disabled` |
| Helper | `.header-meta` |
| Erro | `@error` / `.alert-error` — **validações inalteradas** |
| Required | HTML `required` existente |

Auth (login/reset) mantém radius 0.65rem e `--accent` — branding, não CRM.

Mapa: inputs do sheet 8.2.26 intactos.

Não houve mudança de Request/validation.
