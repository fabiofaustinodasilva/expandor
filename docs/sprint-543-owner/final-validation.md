# Sprint 5.4.3 — Validação final (produção SaaS)

Revisão pré–Sprint 5.5 do branding global Expandor.  
**Resultado:** pronto para produção, sem alteração de regras comerciais.

## Checklist

| # | Item | Status |
|---|------|--------|
| 1 | Login sem `platform_brands` → fallback Expandor | OK |
| 2 | Login com logo cadastrada | OK |
| 3 | Favicon no `<head>` | OK |
| 4 | `<title>` usa nome da plataforma | OK |
| 5 | Upload PNG / JPG / WEBP | OK |
| 6 | Remover / substituir logo | OK |
| 7 | Paths: `platform/branding` ≠ `companies/{id}/branding` | OK |
| 8 | Tenant em `/platform/branding` → 403 | OK |
| 9 | Sem cache de branding (mudança imediata) | OK |
| 10 | Tenant com brand próprio mantém logo | OK |
| 11 | Tenant sem brand → fallback plataforma | OK |

## 1. Login

| Cenário | Comportamento |
|---------|---------------|
| Sem registro | Nome/cores padrão Expandor; sem `<link rel="icon">` quebrado |
| Com logo | Imagem via `/storage/platform/branding/...` |
| Favicon | Coluna `favicon` ou fallback `logo_small` → `logo` |
| Título | `Login — {nome}` (`BrandPayload::name()`) |

Resolução: `BrandingService::resolveForRequest()` — visitante (sem auth) sempre usa `PlatformBrandingService::payload()`.

## 2. Upload

- `MediaUploadService::storePlatform()` → `platform/branding/`
- MIME real, limite 5MB, conversão WEBP (best-effort), thumbnail da logo
- Replace remove o arquivo anterior; `remove_logo` limpa DB + storage
- Favicon que apontava para logo antiga é limpo e recalculado

## 3. Segurança

Middleware `platform.admin` + Gate `platform.manageBranding`.  
Usuário tenant (mesmo admin da empresa) recebe **403** em GET/PUT `/platform/branding`.

## 4. Cache

**Não há cache** de `PlatformBrand` / `BrandPayload` (sem `Cache::remember`).  
Cada request lê o banco → alteração no Owner aparece no próximo GET `/login` **sem** `cache:clear`.

Se no futuro houver cache, invalidar em `PlatformBrandingService::update()` após `save()`.

## 5. Tenant

| Empresa | Depois do login |
|---------|-----------------|
| Com `brands` | Logo/cores próprias (`companies/{id}/branding`) |
| Sem `brands` | Logo/cores Expandor + **nome da empresa** |

Login continua mostrando Expandor (nunca a marca do tenant).

## 6. Testes de validação final

```bash
.\.tools\php\php.exe artisan test --filter="PlatformBrandingFallbackTest|PlatformBrandingUploadTest|TenantBrandingIsolationTest"
```

| Classe | Foco |
|--------|------|
| `PlatformBrandingFallbackTest` | Fallback, favicon, título, leitura imediata |
| `PlatformBrandingUploadTest` | PNG/JPG/WEBP, replace/remove, path isolation |
| `TenantBrandingIsolationTest` | Logo tenant, fallback, 403, não muta platform |

Também: `PlatformBrandingSprint543Test` (cobertura da entrega inicial).

## Schema

Tabela `platform_brands` (migration `2026_08_03_250001_create_platform_brands_table`):

`id`, `name`, `logo`, `logo_small`, `favicon`, `colors` (JSON), `updated_by`, timestamps

## Critério de aceite

Visitante → Expandor. Cliente autenticado → empresa. Níveis de marca **nunca** misturados.
