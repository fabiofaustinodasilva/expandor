# Backup

## Manual

```bash
cd /var/www/expandor
bash deploy/backup.sh
```

Gera:

```
storage/backups/backup-YYYY-MM-DD-HHMM.zip
```

Conteúdo do ZIP:

- `database.sql` (ou `database.sqlite`)  
- `storage-app.tar` (`storage/app` — uploads)  
- `env` (cópia do `.env`)  
- `BACKUP_META.txt`  

## Automático (cron)

Em `deploy/cron.txt` há exemplo às 02:30.  
Recomendado: copiar ZIPs para disco externo / S3 / outro servidor.

## Retenção

```bash
KEEP_DAYS=30 bash deploy/backup.sh
```

Padrão: 14 dias (arquivos antigos em `storage/backups/` são removidos).

## Diretório customizado

```bash
BACKUP_DIR=/var/backups/expandor bash deploy/backup.sh
```
