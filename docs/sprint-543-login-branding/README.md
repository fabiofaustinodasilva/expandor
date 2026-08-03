# Sprint 5.4.3 — Login Branding (logo Expandor)

## Objetivo

Valorizar a marca Expandor na entrada do sistema, sem alterar autenticação nem multi-tenant.

## Layout

```
[Logo grande — centralizada]
Expandor
Bem-vindo ao Expandor
Acesse sua conta
Usuário
Senha
Lembrar-me
Entrar
```

| Elemento | Fonte |
|----------|--------|
| Logo grande | `PlatformBrand.logo` → `/storage/platform/branding/...` |
| Nome | `platform_brands.name` (fallback `Expandor`) |
| Cores | Platform Branding (CSS variables) |
| Fallback sem logo | Wordmark textual `Expandor` (`data-platform-fallback`) |

## Comportamento

- Visitante sempre vê **platform branding** (não tenant).
- Logo maior: até ~96px de altura (72px no mobile).
- Card centralizado, espaçamento profissional, responsivo.
- Form POST `/login` inalterado (`email`, `password`, `remember`).

## Arquivos

- `resources/views/auth/login.blade.php`
- `tests/Feature/Auth/LoginPlatformBrandingRenderTest.php`

## Testes

```bash
.\.tools\php\php.exe artisan test --filter=LoginPlatformBrandingRenderTest
```

Cobertura:

- Logo grande renderizada a partir do Platform Branding
- Fallback wordmark sem logo
- Contrato do formulário de autenticação preservado
- Nome customizado da plataforma no título e no hero

## Critério de aceite

Primeira impressão = marca Expandor. Tenant branding só após autenticação.
