# Sprint 5.5.1 — Auditoria visual SaaS + Correção Login

## Problema

O campo **nome** da plataforma foi preenchido com o slogan (`MAPEIE - ABORDE - REGISTRE - ANALISE - VENDA`). O login renderizava:

1. Nome como título  
2. “Bem-vindo ao {nome}”

Resultado: slogan duplicado e sem nome de produto.

## Correção

Separação clara de identidade:

| Campo | Origem | Uso no login |
|-------|--------|--------------|
| **Nome** | Platform Branding `name` | “Bem-vindo ao {nome}” (+ wordmark se não houver logo) |
| **Slogan** | Platform Branding `slogan` (opcional) | Linha abaixo do logo; se vazio, **não renderiza** |
| **Logo** | Platform Branding | Hero visual |

Layout alvo:

```
[LOGO]
{slogan}                    ← só se existir
Bem-vindo ao Expandor
Acesse sua conta
…
Começar teste grátis
```

Regras:

- Nunca repetir slogan como nome da plataforma  
- Nome vem só de Platform Branding (fallback **Expandor**)  
- Slogan é campo separado  
- Tenants: `brands.slogan` disponível no branding da empresa  

## Schema

Migration `2026_08_03_260001_add_slogan_to_branding_tables` (idempotente):

```sql
ALTER TABLE platform_brands ADD slogan VARCHAR(255) NULL;
ALTER TABLE brands ADD slogan VARCHAR(255) NULL;
```

Se o Owner receber `Unknown column 'slogan'`, o migrate local ainda não rodou:

```bash
.\.tools\php\php.exe artisan migrate
```

A migration verifica `Schema::hasColumn` antes de criar — segura para reexecução.

## Auditoria visual (login)

- Logo maior / hero centralizado  
- Contraste automático (Sprint 5.4.5) — secondary branca, primary clara/escura  
- Campos e botão com tokens `--text`, `--button-text`, `--border`  
- Mensagens de erro com fundo suave e `--accent-2`  
- Responsivo ≤480px  

## Dados existentes

Se o slogan estiver em `platform_brands.name`, o Owner deve:

1. Nome da plataforma → `Expandor`  
2. Slogan → texto da tagline  

Não há rewrite automático (evita alterar nomes reais de tenants/plataforma).

## O que não mudou

- Autenticação / permissões  
- Tenants / uploads / trial  
- Mapa / clientes / vendas  

## Testes

```bash
.\.tools\php\php.exe artisan migrate
.\.tools\php\php.exe artisan test --filter=PlatformBrandingSloganTest
.\.tools\php\php.exe artisan test --filter=PlatformBrandingSprint551Test
.\.tools\php\php.exe artisan test --filter=BrandingContrastSprint545Test
```

- Colunas `slogan` existem  
- Owner salva e recupera Nome + Slogan  
- Login sem duplicação  
- Slogan vazio / fallback Expandor  
- Contraste com secondary branca  

## Arquivos principais

- `database/migrations/2026_08_03_260001_add_slogan_to_branding_tables.php`  
- `app/Domains/Branding/DTOs/BrandPayload.php`  
- `app/Domains/Platform/Services/PlatformBrandingService.php`  
- `resources/views/auth/login.blade.php`  
- `resources/views/platform/branding/edit.blade.php`  
- `resources/views/branding/edit.blade.php`  
- `tests/Feature/Platform/PlatformBrandingSloganTest.php`  
- `tests/Feature/Platform/PlatformBrandingSprint551Test.php`  
