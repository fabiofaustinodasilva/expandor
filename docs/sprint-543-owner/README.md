# Sprint 5.4.3 — Branding Global da Plataforma (Expandor)

## Objetivo

Identidade visual global controlada pelo **Platform Owner**. Visitantes veem a marca Expandor no login; clientes autenticados veem o branding da própria empresa.

## Separação de níveis

```
Visitante
   │
   ▼
Login  ──────────────► Platform Branding (Expandor)
   │
   ▼
Autenticação
   │
   ▼
Empresa selecionada ─► Tenant Branding (companies/{id}/branding)
```

| Momento | Fonte |
|---------|--------|
| Antes do login | `platform/branding` + `platform_brands` |
| Depois do login | `companies/{id}/branding` + `brands` |
| Empresa sem brand | Fallback: logo/cores da plataforma + nome da empresa |

## Configuração (Platform Owner)

**Caminho:** Platform Owner → Configurações da Plataforma → Branding (`/platform/branding`)

| Campo | Uso |
|-------|-----|
| Logo principal | Tela de login |
| Logo reduzida | Favicon / telas pequenas / mobile |
| Favicon | Aba do browser (fallback: logo reduzida) |
| Nome da plataforma | Ex.: Expandor |
| Cores (primária, secundária, destaque) | CSS do login |

Upload via `MediaUploadService::storePlatform()` — MIME real, 5MB, WEBP, thumbnail, path `platform/branding/`.

## Storage

```
platform/
  branding/
    {uuid}.webp
    thumbs/{uuid}.webp
```

Não misturar com `companies/{id}/branding`.

## UI

- Card **Identidade da Plataforma** no dashboard do Owner
- Link **Branding** no menu da plataforma
- Login: logo + “Bem-vindo ao {nome}” + “Acesse sua conta”

## Fallback

Sem cadastro do Owner: nome padrão `Expandor`, cores padrão do sistema, sem quebrar a tela.

## Isolamento

Tenant **não** acessa `/platform/branding` (403). Só Platform Admin com `platform.access`.

## Arquivos principais

- `database/migrations/2026_08_03_250001_create_platform_brands_table.php`
- `app/Domains/Platform/Models/PlatformBrand.php`
- `app/Domains/Platform/Services/PlatformBrandingService.php`
- `app/Http/Controllers/Web/Platform/PlatformBrandingController.php`
- `app/Domains/Branding/Services/BrandingService.php` (resolução guest vs tenant)
- `app/Domains/Media/Services/MediaUploadService.php` (`storePlatform`)
- `resources/views/platform/branding/edit.blade.php`
- `resources/views/auth/login.blade.php`
- `tests/Feature/Platform/PlatformBrandingSprint543Test.php`

## Testes

```bash
.\.tools\php\php.exe artisan test --filter=PlatformBrandingSprint543Test
```

Cobertura:

- Owner envia logo Expandor → `platform/branding/`
- Login mostra logo antes da autenticação
- Tenant mostra logo própria após login
- Empresa sem branding usa fallback de plataforma
- Tenant não altera branding da plataforma
- Fallback sem configuração do Owner

## Prints (manual)

Adicionar nesta pasta após validação no browser:

1. `owner-branding.png` — tela Owner Branding
2. `login-expandor.png` — login com logo Expandor
3. `dashboard-tenant.png` — dashboard com logo da empresa

## Critério de aceite

Visitante → vê Expandor. Cliente autenticado → vê a própria empresa. Sem misturar os dois níveis de marca.

## Validação final (pré–Sprint 5.5)

Ver [final-validation.md](./final-validation.md) — checklist de produção + testes `PlatformBrandingFallbackTest`, `PlatformBrandingUploadTest`, `TenantBrandingIsolationTest`.
