# Sprint 5.4.1 — Upload SaaS centralizado

## Objetivo
Centralizar uploads multi-tenant em um único serviço, integrar com o Manager operacional e remover dependência do fluxo antigo de arquivo.

## Serviço
`App\Domains\Media\Services\MediaUploadService`

- Paths: `storage/app/public/companies/{company_id}/{branding|profiles|products}/`
- Limite: **5MB**
- MIME real (`finfo`) + bloqueio de executáveis
- Aceitos: JPG/JPEG/PNG/WEBP; SVG (logos); ICO (favicon)
- Otimização: converte raster para **WEBP** quando GD disponível
- Thumbnails: perfil e produto (e logo)

## Migrado
| Fluxo | Categoria | UI |
|-------|-----------|-----|
| Branding (logo, mark, favicon, login) | `branding` | Configurações → Branding (layout operacional) |
| Meu Perfil (foto) | `profiles` | Configurações → Meu perfil |
| Produtos (imagem) | `products` | Produtos / Estoque |

## UX
Componente `x-media-upload`:
- preview antes de salvar
- substituir imagem
- remover imagem (`remove_*`)
- mensagens de erro claras

## Migration
`2026_08_03_240001_add_media_image_fields`
- `products.image`, `products.image_thumb`
- `users.photo_thumb`

## Testes
`tests/Feature/Media/MediaUploadServiceTest.php`
- PNG / JPG / WEBP
- arquivo inválido
- limite de tamanho
- isolamento entre empresas
- substituição e remoção
- branding no Manager operacional

## Não altera
Regras de mapa, clientes, agenda, venda, comissão, estoque e dashboard.
