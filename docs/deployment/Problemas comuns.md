# Problemas comuns

## 500 Internal Server Error

1. `storage/logs/laravel.log`  
2. Permissões: `chown -R www-data:www-data storage bootstrap/cache`  
3. `php artisan optimize:clear`  
4. `APP_KEY` presente no `.env`  

## Página branca / assets 404

1. Rodar `npm run build`  
2. Verificar `public/build`  
3. Nginx `root` deve apontar para `.../public`  

## Uploads não abrem

1. `php artisan storage:link`  
2. Nginx `location /storage/` apontando para `storage/app/public/`  
3. Permissão de escrita em `storage/app`  

## Filas não processam

1. `QUEUE_CONNECTION=redis` no `.env`  
2. `supervisorctl status`  
3. Log: `storage/logs/worker.log`  
4. Redis: `redis-cli ping`  

## Scheduler não roda

1. Crontab `www-data` com `schedule:run` (ver `deploy/cron.txt`)  
2. `sudo crontab -u www-data -l`  

## Migrate falha no update

1. Backup recente?  
2. Verificar erro SQL no log  
3. Não forçar rollback destrutivo sem restore  

## Disco cheio

1. `df -h`  
2. Limpar `storage/logs` antigos  
3. Limpar `storage/backups` com `KEEP_DAYS`  

## PHP-FPM sock errado

Ajuste em `deploy/nginx.conf`:

```
unix:/run/php/php8.4-fpm.sock
```

Confira: `ls /run/php/`

## healthcheck STATUS=ERROR

Execute com atenção às linhas `[ERROR]` e corrija na ordem: `.env` → banco → storage link → disco.
