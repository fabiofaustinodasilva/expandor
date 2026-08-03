# Instalação Ubuntu 24.04

## Pré-requisitos

- Servidor Ubuntu **24.04 LTS** com acesso root/sudo  
- Domínio apontando para o IP (ex.: `app.seudominio.com`)  
- Repositório Git do Expandor acessível  

## Passo a passo

### 1. Clone

```bash
sudo mkdir -p /var/www
cd /var/www
sudo git clone <URL_DO_REPO> expandor
cd expandor
```

### 2. install.sh

```bash
sudo bash deploy/install.sh
```

O script:

- Atualiza o sistema  
- Instala PHP 8.4, Composer, Nginx, MariaDB, Redis, Supervisor, Git, Node/npm  
- Roda `composer install --no-dev`, `npm install`, `npm run build`  
- `key:generate`, `storage:link`, `migrate`, `optimize`  

### 3. Banco de dados

```bash
sudo mysql -e "CREATE DATABASE expandor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'expandor'@'localhost' IDENTIFIED BY 'SENHA_FORTE';"
sudo mysql -e "GRANT ALL ON expandor.* TO 'expandor'@'localhost'; FLUSH PRIVILEGES;"
```

Edite `/var/www/expandor/.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.seudominio.com
DB_DATABASE=expandor
DB_USERNAME=expandor
DB_PASSWORD=SENHA_FORTE
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
```

```bash
cd /var/www/expandor
php artisan migrate --force
php artisan optimize
```

### 4. Nginx

```bash
sudo cp deploy/nginx.conf /etc/nginx/sites-available/expandor
sudo nano /etc/nginx/sites-available/expandor   # server_name + SSL paths
sudo ln -sf /etc/nginx/sites-available/expandor /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

HTTPS (Let's Encrypt):

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d app.seudominio.com
```

### 5. Supervisor (filas)

```bash
sudo cp deploy/supervisor.conf /etc/supervisor/conf.d/expandor-worker.conf
# Ajuste o caminho se não for /var/www/expandor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

### 6. Scheduler

```bash
sudo crontab -u www-data -e
# cole a linha de deploy/cron.txt
```

### 7. Healthcheck

```bash
sudo bash deploy/healthcheck.sh
```

Esperado: `STATUS=OK` (warnings aceitáveis no primeiro boot).

### 8. Seed inicial (opcional)

Somente em ambiente novo, se necessário:

```bash
php artisan db:seed --force
```

**Cuidado:** seeds podem sobrescrever dados — use apenas na primeira instalação.
