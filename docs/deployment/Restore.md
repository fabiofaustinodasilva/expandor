# Restore

## Atenção

O restore **sobrescreve** banco e uploads. Sempre confirme o arquivo correto.

## Comando

```bash
cd /var/www/expandor
sudo bash deploy/restore.sh /var/www/expandor/storage/backups/backup-YYYY-MM-DD-HHMM.zip
```

Digite `RESTORE` para confirmar.

## Restaurar também o `.env`

```bash
sudo RESTORE_ENV=1 bash deploy/restore.sh /caminho/backup-....zip
```

Por padrão o `.env` **atual** do servidor é mantido (só banco + `storage/app`).

## Pós-restore

```bash
php artisan storage:link --force
php artisan optimize
sudo supervisorctl restart expandor-worker:*
bash deploy/healthcheck.sh
```

## Teste de restore

Periodicamente restaure em **homologação** a partir de um backup de produção para validar o processo.
