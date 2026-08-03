# Sprint 5.4.2 — Correção definitiva do Upload

## Causa encontrada

O upload **salvava no disco e no banco**, mas a imagem **não aparecia no navegador** por dois problemas de entrega pública:

1. **`public/storage` ausente** — sem `storage:link` (junction/symlink), URLs `/storage/...` retornavam 404.
2. **URL absoluta errada** — `filesystems.disks.public.url` usava `APP_URL=http://localhost` (sem porta). O app roda em `http://127.0.0.1:8000`, então o browser pedia o host errado.

Fluxo quebrava em: **Storage → URL pública → Imagem exibida**.

## Correções

| Item | Mudança |
|------|---------|
| Link público | Criado `public/storage` → `storage/app/public` |
| URLs | Relativas `/storage/...` (independente de host/porta) |
| Disk `public` | `throw => true` + checagem de `exists` após write |
| Otimização | Best-effort: se WEBP/thumb falhar, mantém original |
| Validação | Mensagens claras (5MB, formato, erro de save) |
| Brand URLs | Via `MediaUploadService::url()` |

## Campos no banco (já existentes)

Não renomeamos colunas (evita migração desnecessária):

| Domínio | Colunas |
|---------|---------|
| Branding | `brands.logo`, `logo_mark`, `favicon`, `login_image` |
| Perfil | `users.photo`, `photo_thumb` |
| Produto | `products.image`, `image_thumb` |

## Arquivos alterados

- `config/filesystems.php`
- `app/Domains/Media/Services/MediaUploadService.php`
- `app/Domains/Media/DTOs/MediaUploadResult.php`
- `app/Domains/Branding/Models/Brand.php`
- `app/Domains/Branding/Requests/BrandValidationRules.php`
- `app/Http/Controllers/Web/Branding/BrandingController.php`
- `.env` (`APP_URL=http://127.0.0.1:8000`)
- `tests/Feature/Media/UploadFixSprint542Test.php`
- `scripts/sprint542_live_upload.php` (smoke local)

## Testes

- `UploadFixSprint542Test` — PNG/JPG/JPEG/WEBP, EXE/PHP/5MB, tenant, replace, HTTP branding/perfil/produto
- Suíte completa após correção

## Validação no navegador (Manager operacional)

Confirmado:

- Branding: logo no formulário + rail + preview
- Perfil: foto exibida
- Produtos: thumbnail no catálogo
- HTTP `/storage/companies/{id}/...` → **200**

## Setup obrigatório em novos ambientes

```bash
php artisan storage:link
```

Em Windows, se symlink falhar, use junction apontando `public/storage` → `storage/app/public`.
