# DEPLOYMENT (produção)

## Variável obrigatória

```env
APP_TIMEZONE=America/Sao_Paulo
```

Mapeia para `config('app.display_timezone')`.  
`config('app.timezone')` permanece **UTC** no código (storage).

Já documentado em `.env.example` e `deploy/.env.example.production`.  
**Não** commitar `.env` real.

## Cache

```bash
php artisan optimize:clear
php artisan config:cache
```

Nenhum restart de PHP-FPM/nginx é exigido só por esta variável se o config cache for regenerado.

## Não fazer

- Alterar `@@global.time_zone` / `@@session.time_zone` do MySQL sem diagnóstico
- Migration de dados históricos
- Setar `APP_TIMEZONE` em `config('app.timezone')` sem migration
