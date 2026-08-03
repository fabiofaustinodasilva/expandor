# Deploy Expandor — scripts e configs de produção

Pasta voltada a **Ubuntu 24.04** (homologação / produção).  
Não altera o código da aplicação — apenas infraestrutura.

## Conteúdo

| Arquivo | Função |
|---------|--------|
| `install.sh` | Instala stack + prepara Laravel |
| `update.sh` | `git pull` + build + migrate (preserva uploads) |
| `backup.sh` | Dump DB + `storage/app` + `.env` → ZIP |
| `restore.sh` | Restaura ZIP (confirmação obrigatória) |
| `healthcheck.sh` | Banco, Redis, queue, disco, supervisor… |
| `.env.example.production` | Modelo de `.env` seguro |
| `nginx.conf` | HTTPS + PHP-FPM + storage |
| `supervisor.conf` | `queue:work` com autorestart |
| `cron.txt` | `schedule:run` |

## Uso rápido

```bash
# No servidor (exemplo)
cd /var/www
sudo git clone <repo> expandor
cd expandor
sudo bash deploy/install.sh
# editar .env, configurar nginx/supervisor/cron
sudo bash deploy/healthcheck.sh
```

Documentação completa: [`docs/deployment/`](../docs/deployment/).

## Requisitos

- Ubuntu 24.04 LTS  
- PHP **8.4** (compatível com `composer.json` `^8.2`)  
- MariaDB/MySQL, Redis, Nginx, Supervisor, Node/npm, Composer  

## Segurança

- Nunca commitar `.env` real  
- `APP_DEBUG=false` em produção  
- HTTPS obrigatório  
- Backups fora do document root (padrão: `storage/backups/`)  
