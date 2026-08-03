# Atualização

## Objetivo

Atualizar o Expandor **sem apagar** `storage/` (uploads) nem o `.env`.

## Comando

```bash
cd /var/www/expandor
sudo bash deploy/update.sh
```

Branch específica:

```bash
sudo BRANCH=main bash deploy/update.sh
```

## O que o script faz

1. `php artisan down`  
2. `git pull --ff-only`  
3. `composer install --no-dev`  
4. `npm ci/install` + `npm run build`  
5. `php artisan migrate --force`  
6. `optimize:clear` + `optimize`  
7. `queue:restart` (+ supervisor)  
8. `php artisan up`  

## Boas práticas

1. Rodar `bash deploy/backup.sh` **antes** do update  
2. Validar em homologação  
3. Rodar `bash deploy/healthcheck.sh` depois  
4. Evitar `git reset --hard` / limpeza manual de `storage`  

## Rollback rápido

1. Restaurar backup: ver [Restore](./Restore.md)  
2. Ou `git checkout` da tag/commit anterior + `composer install` + `npm run build` + `optimize`  
