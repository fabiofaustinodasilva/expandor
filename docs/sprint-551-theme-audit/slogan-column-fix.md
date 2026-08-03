# Fix — coluna `slogan` ausente

## Sintoma

`PUT /platform/branding` → `SQLSTATE[42S22]: Unknown column 'slogan' in 'SET'`.

## Causa

Código da Sprint 5.5.1 já grava `platform_brands.slogan` / `brands.slogan`, mas o banco local ainda não aplicou a migration.

## Correção

```bash
.\.tools\php\php.exe artisan migrate
```

Migration idempotente: `2026_08_03_260001_add_slogan_to_branding_tables.php`.

## Validação

```bash
.\.tools\php\php.exe artisan test --filter=PlatformBrandingSloganTest
```
