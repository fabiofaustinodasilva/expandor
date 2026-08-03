# Sprint 5.4.5 — Contraste automático do Branding

## Problema

Ao definir a cor secundária como branca (para destacar logo escura), textos do login ficavam claros sobre fundo claro — ilegíveis.

## Solução SaaS

`BrandingContrastService` calcula luminância relativa (WCAG) da cor de fundo e devolve tokens de texto:

| Token | Uso |
|-------|-----|
| `text_primary` | Títulos / texto principal |
| `text_secondary` | Texto de apoio |
| `muted_text` | Labels, descrições |
| `button_text` | Texto sobre botão (primary/accent) |

Fundo claro → texto escuro. Fundo escuro → texto claro.

## Integração

`ThemeService::cssVariables()` aplica automaticamente:

- `--text`, `--muted`, `--text-secondary` a partir de `bg_elevated` (cards / login / rail)
- `--text-on-bg`, `--muted-on-bg` a partir de `bg` (página)
- `--button-text` a partir da cor primária
- `--border` ajustado ao contraste da superfície
- classe `brand-contrast-light` | `brand-contrast-dark`

Aplicado em: login, layout operacional (rail/dashboard), `app`, `sales-app`.

## O que não mudou

- Banco / schema de branding  
- Uploads / logos  
- Multi-tenant  
- Resolução platform vs tenant  

## Testes

```bash
.\.tools\php\php.exe artisan test --filter=BrandingContrastSprint545Test
```

- Fundo branco → texto escuro  
- Fundo preto/escuro → texto claro  
- Secondary branca no login → tokens corretos  
- Branding padrão escuro continua legível  

## Arquivos

- `app/Domains/Branding/Services/BrandingContrastService.php`
- `app/Domains/Branding/Services/ThemeService.php`
- `resources/views/auth/login.blade.php`
- `resources/views/layouts/operational.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/sales-app.blade.php`
- `tests/Feature/Branding/BrandingContrastSprint545Test.php`
